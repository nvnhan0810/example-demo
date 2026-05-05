import pg from "pg";

export type PgPool = pg.Pool;

export function createPgPool(args: {
  connectionString: string;
  max?: number;
}): PgPool {
  return new pg.Pool({
    connectionString: args.connectionString,
    max: args.max ?? 10,
  });
}

