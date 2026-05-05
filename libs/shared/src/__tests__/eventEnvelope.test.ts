import { describe, expect, it } from "vitest";
import { newEvent } from "../messaging/eventEnvelope.ts";

describe("event envelope", () => {
  it("creates a valid envelope", () => {
    const evt = newEvent({
      producer: "test",
      event_type: "SomethingHappened",
      payload: { a: 1 },
    });

    expect(evt.event_id).toBeTypeOf("string");
    expect(evt.event_type).toBe("SomethingHappened");
    expect(evt.event_version).toBe(1);
    expect(evt.producer).toBe("test");
    expect(evt.payload).toEqual({ a: 1 });
  });
});

