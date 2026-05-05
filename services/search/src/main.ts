import { Client } from "@elastic/elasticsearch";
import Fastify from "fastify";
import { Kafka, logLevel } from "kafkajs";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3002"),
    ELASTIC_URL: z.string().min(1),
    KAFKA_BROKERS: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "search";
const TOPIC_PRODUCT = "catalog.product.v1";
const INDEX = "products";

const es = new Client({ node: env.ELASTIC_URL });

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

async function ensureIndex() {
  const exists = await es.indices.exists({ index: INDEX });
  if (!exists) {
    await es.indices.create({
      index: INDEX,
      mappings: {
        properties: {
          product_id: { type: "keyword" },
          sku: { type: "keyword" },
          name: { type: "text" },
          description: { type: "text" },
          price_cents: { type: "integer" },
          currency: { type: "keyword" },
          status: { type: "keyword" },
          occurred_at: { type: "date" },
        },
      },
    });
  }
}

async function main() {
  await ensureIndex();

  const kafka = createKafka(env.KAFKA_BROKERS.split(","));
  await ensureTopics(kafka, [TOPIC_PRODUCT]);

  const consumer = kafka.consumer({ groupId: `${SERVICE}-group` });
  await consumer.connect();
  await consumer.subscribe({ topic: TOPIC_PRODUCT, fromBeginning: true });
  await consumer.run({
    eachMessage: async ({ message }) => {
      if (!message.value) return;
      const evt = JSON.parse(message.value.toString()) as {
        event_type: string;
        occurred_at: string;
        payload: any;
      };
      if (evt.event_type !== "ProductPublished") return;
      const p = evt.payload as {
        product_id: string;
        sku: string;
        name: string;
        description: string;
        price_cents: number;
        currency: string;
        status: string;
      };

      await es.index({
        index: INDEX,
        id: p.product_id,
        document: { ...p, occurred_at: evt.occurred_at },
        refresh: true,
      });
    },
  });

  const app = Fastify({ logger: true });
  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  app.get("/search", async (req) => {
    const query = z.object({ q: z.string().default("") }).parse(req.query);
    const q = query.q.trim();
    if (!q) return { items: [] };

    const res = await es.search({
      index: INDEX,
      query: {
        multi_match: {
          query: q,
          fields: ["name^2", "description"],
        },
      },
      size: 20,
    });

    const items = (res.hits.hits ?? []).map((h: any) => h._source);
    return { items };
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

