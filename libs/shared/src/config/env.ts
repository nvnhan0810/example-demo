import { z } from "zod";

export function loadEnv<T extends z.ZodTypeAny>(schema: T): z.infer<T> {
  const parsed = schema.safeParse(process.env);
  if (!parsed.success) {
    // keep it short; zod will include details
    throw new Error(`Invalid env: ${parsed.error.message}`);
  }
  return parsed.data;
}

