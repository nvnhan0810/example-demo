import Fastify from "fastify";
import type { FastifyInstance } from "fastify";

export function createServer(args?: { logger?: boolean }): FastifyInstance {
  return Fastify({
    logger: args?.logger ?? true,
  });
}

