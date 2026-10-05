import { randomUUID } from "node:crypto";
import Fastify from "fastify";
import { Kafka, logLevel, type EachMessagePayload } from "kafkajs";
import pg from "pg";
import Redis from "ioredis";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3003"),
    DATABASE_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
    REDIS_URL: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "orders";
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
const redis = new Redis(env.REDIS_URL);

async function sleep(ms: number) {
  await new Promise((r) => setTimeout(r, ms));
}

async function migrate() {
  await pool.query(`CREATE EXTENSION IF NOT EXISTS pgcrypto;`);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS orders (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      customer_id text NOT NULL,
      status text NOT NULL,
      total_cents integer NOT NULL,
      currency text NOT NULL DEFAULT 'VND',
      created_at timestamptz NOT NULL DEFAULT now(),
      updated_at timestamptz NOT NULL DEFAULT now()
    );
  `);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS order_items (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      order_id uuid NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
      sku text NOT NULL,
      qty integer NOT NULL,
      price_cents integer NOT NULL
    );
  `);
}

type OrderStatus = "PENDING_INVENTORY" | "PENDING_PAYMENT" | "PAID" | "CANCELLED";

async function setOrderStatus(orderId: string, status: OrderStatus) {
  await pool.query(`UPDATE orders SET status=$2, updated_at=now() WHERE id=$1`, [orderId, status]);
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
  await consumer.subscribe({ topic: TOPIC_INVENTORY, fromBeginning: true });
  await consumer.subscribe({ topic: TOPIC_PAYMENTS, fromBeginning: true });

  await consumer.run({
    eachMessage: async ({ topic, message }: EachMessagePayload) => {
      if (!message.value) return;
      const evt = JSON.parse(message.value.toString()) as { event_id: string; event_type: string; payload: any };

      // idempotency (very simple): drop if processed
      const key = `orders:inbox:${evt.event_id}`;
      const ok = await redis.set(key, "1", "EX", 60 * 60, "NX");
      if (!ok) return;

      if (topic === TOPIC_INVENTORY) {
        if (evt.event_type === "InventoryReserved") {
          await setOrderStatus(evt.payload.order_id, "PENDING_PAYMENT");
        }
        if (evt.event_type === "InventoryReserveFailed") {
          await setOrderStatus(evt.payload.order_id, "CANCELLED");
          await producer.send({
            topic: TOPIC_ORDERS,
            messages: [
              {
                key: evt.payload.order_id,
                value: JSON.stringify(
                  newEvent({
                    producer: SERVICE,
                    event_type: "OrderCancelled",
                    causation_id: evt.event_id,
                    payload: { order_id: evt.payload.order_id, reason: evt.payload.reason ?? "inventory_failed" },
                  }),
                ),
              },
            ],
          });
        }
      }

      if (topic === TOPIC_PAYMENTS) {
        if (evt.event_type === "PaymentSucceeded") {
          await setOrderStatus(evt.payload.order_id, "PAID");
          await producer.send({
            topic: TOPIC_ORDERS,
            messages: [
              {
                key: evt.payload.order_id,
                value: JSON.stringify(
                  newEvent({
                    producer: SERVICE,
                    event_type: "OrderPaid",
                    causation_id: evt.event_id,
                    payload: { order_id: evt.payload.order_id, payment_id: evt.payload.payment_id },
                  }),
                ),
              },
            ],
          });
        }
        if (evt.event_type === "PaymentFailed") {
          await setOrderStatus(evt.payload.order_id, "CANCELLED");
          await producer.send({
            topic: TOPIC_ORDERS,
            messages: [
              {
                key: evt.payload.order_id,
                value: JSON.stringify(
                  newEvent({
                    producer: SERVICE,
                    event_type: "OrderCancelled",
                    causation_id: evt.event_id,
                    payload: { order_id: evt.payload.order_id, reason: "payment_failed" },
                  }),
                ),
              },
            ],
          });
        }
      }
    },
  });

  const app = Fastify({ logger: true });
  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  // minimal checkout: client provides sku/qty/price; demo only
  app.post("/checkout", async (req, reply) => {
    const body = z
      .object({
        customer_id: z.string().min(1).default("demo-customer"),
        items: z.array(
          z.object({
            sku: z.string().min(1),
            qty: z.number().int().positive(),
            price_cents: z.number().int().nonnegative(),
          }),
        ),
      })
      .parse(req.body);

    const total = body.items.reduce((s, it) => s + it.qty * it.price_cents, 0);

    const client = await pool.connect();
    try {
      await client.query("BEGIN");
      const inserted = await client.query<{ id: string }>(
        `INSERT INTO orders (customer_id, status, total_cents, currency) VALUES ($1, $2, $3, 'VND') RETURNING id`,
        [body.customer_id, "PENDING_INVENTORY", total],
      );
      const orderId = inserted.rows[0]!.id;
      for (const it of body.items) {
        await client.query(`INSERT INTO order_items (order_id, sku, qty, price_cents) VALUES ($1, $2, $3, $4)`, [
          orderId,
          it.sku,
          it.qty,
          it.price_cents,
        ]);
      }
      await client.query("COMMIT");

      const evt = newEvent({
        producer: SERVICE,
        event_type: "OrderCreated",
        payload: { order_id: orderId, customer_id: body.customer_id, items: body.items, total_cents: total, currency: "VND" },
      });
      await producer.send({ topic: TOPIC_ORDERS, messages: [{ key: orderId, value: JSON.stringify(evt) }] });

      reply.code(201);
      return { order_id: orderId, status: "PENDING_INVENTORY" };
    } catch (err) {
      await client.query("ROLLBACK");
      throw err;
    } finally {
      client.release();
    }
  });

  app.get("/orders/:id", async (req, reply) => {
    const params = z.object({ id: z.string().uuid() }).parse(req.params);
    const o = await pool.query(`SELECT * FROM orders WHERE id=$1`, [params.id]);
    if (o.rowCount === 0) {
      reply.code(404);
      return { message: "Not found" };
    }
    const items = await pool.query(`SELECT sku, qty, price_cents FROM order_items WHERE order_id=$1`, [params.id]);
    return { ...o.rows[0], items: items.rows };
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

