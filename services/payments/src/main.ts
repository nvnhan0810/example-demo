import { randomUUID } from "node:crypto";
import Fastify from "fastify";
import { Kafka, logLevel, type EachMessagePayload } from "kafkajs";
import pg from "pg";
import Redis from "ioredis";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3005"),
    DATABASE_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
    REDIS_URL: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "payments";
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
    CREATE TABLE IF NOT EXISTS payments (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      order_id uuid NOT NULL UNIQUE,
      status text NOT NULL,
      amount_cents integer NOT NULL DEFAULT 0,
      currency text NOT NULL DEFAULT 'VND',
      created_at timestamptz NOT NULL DEFAULT now(),
      updated_at timestamptz NOT NULL DEFAULT now()
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
  await ensureTopics(kafka, [TOPIC_INVENTORY, TOPIC_PAYMENTS]);
  const producer = kafka.producer();
  await producer.connect();

  const consumer = kafka.consumer({ groupId: `${SERVICE}-group` });
  await consumer.connect();
  await consumer.subscribe({ topic: TOPIC_INVENTORY, fromBeginning: true });

  await consumer.run({
    eachMessage: async ({ message }: EachMessagePayload) => {
      if (!message.value) return;
      const evt = JSON.parse(message.value.toString()) as { event_id: string; event_type: string; payload: any };
      if (evt.event_type !== "InventoryReserved") return;

      const orderId = evt.payload.order_id as string;

      // idempotency: if payment already exists, do nothing
      const key = `payments:order:${orderId}`;
      const ok = await redis.set(key, "1", "EX", 60 * 60, "NX");
      if (!ok) return;

      const inserted = await pool.query<{ id: string }>(
        `INSERT INTO payments (order_id, status) VALUES ($1, 'PENDING') RETURNING id`,
        [orderId],
      );
      const paymentId = inserted.rows[0]!.id;

      // For demo: auto-succeed after short delay.
      setTimeout(async () => {
        try {
          await pool.query(`UPDATE payments SET status='SUCCEEDED', updated_at=now() WHERE id=$1`, [paymentId]);
          const out = newEvent({
            producer: SERVICE,
            event_type: "PaymentSucceeded",
            causation_id: evt.event_id,
            payload: { order_id: orderId, payment_id: paymentId },
          });
          await producer.send({ topic: TOPIC_PAYMENTS, messages: [{ key: orderId, value: JSON.stringify(out) }] });
        } catch (err) {
          // eslint-disable-next-line no-console
          console.error(err);
        }
      }, 500);
    },
  });

  const app = Fastify({ logger: true });
  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  app.get("/payments/:orderId", async (req, reply) => {
    const params = z.object({ orderId: z.string().uuid() }).parse(req.params);
    const p = await pool.query(`SELECT * FROM payments WHERE order_id=$1`, [params.orderId]);
    if (p.rowCount === 0) {
      reply.code(404);
      return { message: "Not found" };
    }
    return p.rows[0];
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

