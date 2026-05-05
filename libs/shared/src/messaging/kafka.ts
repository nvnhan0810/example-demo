import { Kafka, logLevel } from "kafkajs";

export type KafkaDeps = {
  brokers: string[];
  clientId: string;
};

export function createKafka({ brokers, clientId }: KafkaDeps) {
  return new Kafka({
    clientId,
    brokers,
    logLevel: logLevel.NOTHING,
  });
}

export async function ensureTopics(args: {
  kafka: Kafka;
  topics: { topic: string; numPartitions?: number; replicationFactor?: number }[];
}) {
  const admin = args.kafka.admin();
  await admin.connect();
  try {
    await admin.createTopics({
      validateOnly: false,
      waitForLeaders: true,
      topics: args.topics.map((t) => ({
        topic: t.topic,
        numPartitions: t.numPartitions ?? 1,
        replicationFactor: t.replicationFactor ?? 1,
      })),
    });
  } finally {
    await admin.disconnect();
  }
}

