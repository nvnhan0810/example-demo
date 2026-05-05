import { randomUUID } from "node:crypto";
import Fastify from "fastify";
import { Kafka, logLevel } from "kafkajs";
import pg from "pg";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3001"),
    DATABASE_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "catalog";
const TOPIC_PRODUCT = "catalog.product.v1";

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

function newEvent<T>(event_type: string, payload: T) {
  return {
    event_id: randomUUID(),
    event_type,
    event_version: 1,
    occurred_at: new Date().toISOString(),
    producer: SERVICE,
    payload,
  };
}

const pool = createPgPool(env.DATABASE_URL);

async function migrate() {
  await pool.query(`CREATE EXTENSION IF NOT EXISTS pgcrypto;`);
  await pool.query(`
    CREATE TABLE IF NOT EXISTS products (
      id uuid PRIMARY KEY,
      sku text NOT NULL UNIQUE,
      name text NOT NULL,
      description text NOT NULL DEFAULT '',
      price_cents integer NOT NULL,
      currency text NOT NULL DEFAULT 'VND',
      status text NOT NULL DEFAULT 'DRAFT',
      created_at timestamptz NOT NULL DEFAULT now(),
      updated_at timestamptz NOT NULL DEFAULT now()
    );
  `);
}

async function sleep(ms: number) {
  await new Promise((r) => setTimeout(r, ms));
}

async function main() {
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
  await ensureTopics(kafka, [TOPIC_PRODUCT]);

  const producer = kafka.producer();
  await producer.connect();

  const app = Fastify({ logger: true });

  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  app.post("/admin/products", async (req, reply) => {
    const body = z
      .object({
        id: z.string().uuid().optional(),
        sku: z.string().min(1),
        name: z.string().min(1),
        description: z.string().default(""),
        price_cents: z.number().int().nonnegative(),
        currency: z.string().default("VND"),
      })
      .parse(req.body);

    const { rows } = await pool.query<{ id: string }>(
      `
      INSERT INTO products (id, sku, name, description, price_cents, currency, status)
      VALUES (coalesce($1::uuid, gen_random_uuid()), $2, $3, $4, $5, $6, 'DRAFT')
      RETURNING id;
      `,
      [body.id ?? null, body.sku, body.name, body.description, body.price_cents, body.currency],
    );

    reply.code(201);
    return { id: rows[0]!.id };
  });

  app.patch("/admin/products/:id/publish", async (req, reply) => {
    const params = z.object({ id: z.string().uuid() }).parse(req.params);

    const updated = await pool.query(
      `
      UPDATE products
      SET status='PUBLISHED', updated_at=now()
      WHERE id=$1
      RETURNING id, sku, name, description, price_cents, currency, status;
      `,
      [params.id],
    );

    if (updated.rowCount === 0) {
      reply.code(404);
      return { message: "Not found" };
    }

    const p = updated.rows[0] as {
      id: string;
      sku: string;
      name: string;
      description: string;
      price_cents: number;
      currency: string;
      status: string;
    };

    const wrapped = newEvent("ProductPublished", {
      product_id: p.id,
      sku: p.sku,
      name: p.name,
      description: p.description,
      price_cents: p.price_cents,
      currency: p.currency,
      status: p.status,
    });

    await producer.send({
      topic: TOPIC_PRODUCT,
      messages: [{ key: p.id, value: JSON.stringify(wrapped) }],
    });

    return { ok: true, product_id: p.id };
  });

  app.get("/products/:id", async (req, reply) => {
    const params = z.object({ id: z.string().uuid() }).parse(req.params);
    const { rows } = await pool.query(`SELECT * FROM products WHERE id=$1`, [params.id]);
    if (rows.length === 0) {
      reply.code(404);
      return { message: "Not found" };
    }
    return rows[0];
  });

  app.get("/products", async () => {
    const { rows } = await pool.query(`SELECT * FROM products ORDER BY created_at DESC LIMIT 50`);
    return { items: rows };
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

