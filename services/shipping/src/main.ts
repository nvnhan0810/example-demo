import { randomUUID } from "node:crypto";
import Fastify from "fastify";
import { Kafka, logLevel } from "kafkajs";
import pg from "pg";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3006"),
    DATABASE_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "shipping";
const TOPIC_ORDERS = "orders.order.v1";
const TOPIC_SHIPPING = "shipping.shipment.v1";

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
    CREATE TABLE IF NOT EXISTS shipments (
      id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
      order_id uuid NOT NULL UNIQUE,
      status text NOT NULL,
      tracking_code text NOT NULL,
      created_at timestamptz NOT NULL DEFAULT now(),
      updated_at timestamptz NOT NULL DEFAULT now()
    );
  `);
}

function trackingCode(orderId: string) {
  return `TRK-${orderId.slice(0, 8).toUpperCase()}`;
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
  await ensureTopics(kafka, [TOPIC_ORDERS, TOPIC_SHIPPING]);
  const producer = kafka.producer();
  await producer.connect();

  const consumer = kafka.consumer({ groupId: `${SERVICE}-group` });
  await consumer.connect();
  await consumer.subscribe({ topic: TOPIC_ORDERS, fromBeginning: true });

  await consumer.run({
    eachMessage: async ({ message }) => {
      if (!message.value) return;
      const evt = JSON.parse(message.value.toString()) as { event_id: string; event_type: string; payload: any };
      if (evt.event_type !== "OrderPaid") return;
      const orderId = evt.payload.order_id as string;

      const inserted = await pool.query<{ id: string }>(
        `INSERT INTO shipments (order_id, status, tracking_code)
         VALUES ($1, 'CREATED', $2)
         ON CONFLICT (order_id) DO UPDATE SET updated_at=now()
         RETURNING id`,
        [orderId, trackingCode(orderId)],
      );
      const shipmentId = inserted.rows[0]!.id;

      const out = newEvent({
        producer: SERVICE,
        event_type: "ShipmentCreated",
        causation_id: evt.event_id,
        payload: { order_id: orderId, shipment_id: shipmentId, tracking_code: trackingCode(orderId) },
      });
      await producer.send({ topic: TOPIC_SHIPPING, messages: [{ key: orderId, value: JSON.stringify(out) }] });
    },
  });

  const app = Fastify({ logger: true });
  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  app.get("/shipments/:orderId", async (req, reply) => {
    const params = z.object({ orderId: z.string().uuid() }).parse(req.params);
    const s = await pool.query(`SELECT * FROM shipments WHERE order_id=$1`, [params.orderId]);
    if (s.rowCount === 0) {
      reply.code(404);
      return { message: "Not found" };
    }
    return s.rows[0];
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

