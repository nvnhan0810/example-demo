import { randomUUID } from "node:crypto";
import Fastify from "fastify";
import { Kafka, logLevel } from "kafkajs";
import pg from "pg";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3004"),
    DATABASE_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "inventory";
const TOPIC_ORDERS = "orders.order.v1";
const TOPIC_INVENTORY = "inventory.reservation.v1";
const TOPIC_PAYMENTS = "payments.payment.v1";

function createPgPool(connectionString: string) {
  return new pg.Pool({ connectionString, max: 10 });
}

function createKafka(brokers: string[]) {
  return new Kafka({ clientId: SERVICE, brokers, logLevel: logLevel.NOTHING });
}

async function ensureTopics(kafka: Kafka, topics: string[]) {
  const admin = kafka.admin();
  await admin.connect();
  try {
    await admin.createTopics({
      validateOnly: false,
      waitForLeaders: true,
      topics: topics.map((topic) => ({ topic, numPartitions: 1, replicationFactor: 1 })),
    });
  } finally {
    await admin.disconnect();
  }
}

function newEvent<T>(args: { event_type: string; payload: T; causation_id?: string }) {
  return {
    event_id: randomUUID(),
    event_type: args.event_type,
    event_version: 1,
    occurred_at: new Date().toISOString(),
    producer: SERVICE,
    causation_id: args.causation_id,
    payload: args.payload,
  };
}

const pool = createPgPool(env.DATABASE_URL);

async function sleep(ms: number) {
  await new Promise((r) => setTimeout(r, ms));
}

async function migrate() {
  await pool.query(`CREATE EXTENSION IF NOT EXISTS pgcrypto;`);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS stock (
      sku text PRIMARY KEY,
      available integer NOT NULL,
      reserved integer NOT NULL DEFAULT 0,
      updated_at timestamptz NOT NULL DEFAULT now()
    );
  `);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS reservations (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      order_id uuid NOT NULL UNIQUE,
      status text NOT NULL,
      created_at timestamptz NOT NULL DEFAULT now(),
      updated_at timestamptz NOT NULL DEFAULT now()
    );
  `);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS reservation_items (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      reservation_id uuid NOT NULL REFERENCES reservations(id) ON DELETE CASCADE,
      sku text NOT NULL,
      qty integer NOT NULL
    );
  `);
}

async function main() {
  await sleep(1000);
  for (let i = 0; i < 30; i++) {
    try {
      await migrate();
      break;
    } catch (err) {
      if (i === 29) throw err;
      await sleep(500);
    }
  }

  const kafka = createKafka(env.KAFKA_BROKERS.split(","));
  await ensureTopics(kafka, [TOPIC_ORDERS, TOPIC_INVENTORY, TOPIC_PAYMENTS]);
  const producer = kafka.producer();
  await producer.connect();

  const consumer = kafka.consumer({ groupId: `${SERVICE}-group` });
  await consumer.connect();
  await consumer.subscribe({ topic: TOPIC_ORDERS, fromBeginning: true });
  await consumer.subscribe({ topic: TOPIC_PAYMENTS, fromBeginning: true });

  await consumer.run({
    eachMessage: async ({ topic, message }) => {
      if (!message.value) return;
      const evt = JSON.parse(message.value.toString()) as { event_id: string; event_type: string; payload: any };

      if (topic === TOPIC_ORDERS) {
        if (evt.event_type !== "OrderCreated") return;
        const orderId = evt.payload.order_id as string;
        const items = evt.payload.items as { sku: string; qty: number }[];

        const client = await pool.connect();
        try {
          await client.query("BEGIN");

          // already reserved (idempotency)
          const existing = await client.query(`SELECT id, status FROM reservations WHERE order_id=$1`, [orderId]);
          if (existing.rowCount > 0) {
            await client.query("COMMIT");
            return;
          }

          // check and reserve
          for (const it of items) {
            const st = await client.query<{ available: number; reserved: number }>(`SELECT available, reserved FROM stock WHERE sku=$1 FOR UPDATE`, [
              it.sku,
            ]);
            const available = st.rowCount === 0 ? 0 : st.rows[0]!.available;
            if (available < it.qty) {
              await client.query("ROLLBACK");
              const fail = newEvent({
                producer: SERVICE,
                event_type: "InventoryReserveFailed",
                causation_id: evt.event_id,
                payload: { order_id: orderId, reason: `insufficient_stock:${it.sku}` },
              });
              await producer.send({ topic: TOPIC_INVENTORY, messages: [{ key: orderId, value: JSON.stringify(fail) }] });
              return;
            }
          }

          const r = await client.query<{ id: string }>(`INSERT INTO reservations (order_id, status) VALUES ($1, 'RESERVED') RETURNING id`, [orderId]);
          const reservationId = r.rows[0]!.id;

          for (const it of items) {
            await client.query(`INSERT INTO reservation_items (reservation_id, sku, qty) VALUES ($1, $2, $3)`, [reservationId, it.sku, it.qty]);
            await client.query(
              `INSERT INTO stock (sku, available, reserved) VALUES ($1, 0, 0)
               ON CONFLICT (sku) DO NOTHING`,
              [it.sku],
            );
            await client.query(`UPDATE stock SET available=available-$2, reserved=reserved+$2, updated_at=now() WHERE sku=$1`, [it.sku, it.qty]);
          }

          await client.query("COMMIT");

          const ok = newEvent({
            producer: SERVICE,
            event_type: "InventoryReserved",
            causation_id: evt.event_id,
            payload: { order_id: orderId, reservation_id: reservationId },
          });
          await producer.send({ topic: TOPIC_INVENTORY, messages: [{ key: orderId, value: JSON.stringify(ok) }] });
        } catch (err) {
          await client.query("ROLLBACK");
          throw err;
        } finally {
          client.release();
        }
      }

      if (topic === TOPIC_PAYMENTS) {
        if (evt.event_type !== "PaymentFailed" && evt.event_type !== "PaymentSucceeded") return;
        const orderId = evt.payload.order_id as string;

        const client = await pool.connect();
        try {
          await client.query("BEGIN");
          const res = await client.query<{ id: string; status: string }>(`SELECT id, status FROM reservations WHERE order_id=$1 FOR UPDATE`, [orderId]);
          if (res.rowCount === 0) {
            await client.query("COMMIT");
            return;
          }

          const reservationId = res.rows[0]!.id;
          const items = await client.query<{ sku: string; qty: number }>(
            `SELECT sku, qty FROM reservation_items WHERE reservation_id=$1`,
            [reservationId],
          );

          if (evt.event_type === "PaymentFailed") {
            // release back to available
            for (const it of items.rows) {
              await client.query(`UPDATE stock SET available=available+$2, reserved=reserved-$2, updated_at=now() WHERE sku=$1`, [it.sku, it.qty]);
            }
            await client.query(`UPDATE reservations SET status='RELEASED', updated_at=now() WHERE id=$1`, [reservationId]);
          }
          if (evt.event_type === "PaymentSucceeded") {
            // reserved already deducted from available; now just mark completed
            await client.query(`UPDATE reservations SET status='COMPLETED', updated_at=now() WHERE id=$1`, [reservationId]);
          }

          await client.query("COMMIT");
        } catch (err) {
          await client.query("ROLLBACK");
          throw err;
        } finally {
          client.release();
        }
      }
    },
  });

  const app = Fastify({ logger: true });
  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  // admin set stock for demo
  app.post("/admin/stock", async (req, reply) => {
    const body = z.object({ sku: z.string().min(1), available: z.number().int().nonnegative() }).parse(req.body);
    await pool.query(
      `INSERT INTO stock (sku, available, reserved) VALUES ($1, $2, 0)
       ON CONFLICT (sku) DO UPDATE SET available=EXCLUDED.available, updated_at=now()`,
      [body.sku, body.available],
    );
    reply.code(204);
  });

  app.get("/stock/:sku", async (req, reply) => {
    const params = z.object({ sku: z.string().min(1) }).parse(req.params);
    const st = await pool.query(`SELECT sku, available, reserved FROM stock WHERE sku=$1`, [params.sku]);
    if (st.rowCount === 0) {
      reply.code(404);
      return { message: "Not found" };
    }
    return st.rows[0];
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

