import { randomUUID } from "node:crypto";

export type EventEnvelope<TPayload> = {
  event_id: string;
  event_type: string;
  event_version: number;
  occurred_at: string;
  producer: string;
  correlation_id?: string;
  causation_id?: string;
  payload: TPayload;
};

export function nowIso(): string {
  return new Date().toISOString();
}

export function newEvent<TPayload>(args: {
  event_type: string;
  event_version?: number;
  producer: string;
  payload: TPayload;
  correlation_id?: string;
  causation_id?: string;
}): EventEnvelope<TPayload> {
  return {
    event_id: randomUUID(),
    event_type: args.event_type,
    event_version: args.event_version ?? 1,
    occurred_at: nowIso(),
    producer: args.producer,
    correlation_id: args.correlation_id,
    causation_id: args.causation_id,
    payload: args.payload,
  };
}

