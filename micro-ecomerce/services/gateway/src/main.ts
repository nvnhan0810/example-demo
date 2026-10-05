import Fastify from "fastify";
import { z } from "zod";

const env = z
  .object({
    PORT: z.string().default("3000"),
    CATALOG_URL: z.string().min(1),
    SEARCH_URL: z.string().min(1),
    ORDERS_URL: z.string().min(1),
    INVENTORY_URL: z.string().min(1),
    PAYMENTS_URL: z.string().min(1),
    SHIPPING_URL: z.string().min(1),
  })
  .parse(process.env);

const SERVICE = "gateway";

async function main() {
  const app = Fastify({ logger: true });

  app.get("/health", async () => ({ ok: true, service: SERVICE }));

  // ---- Catalog passthrough ----
  app.post("/admin/products", async (req, reply) => {
    const res = await fetch(`${env.CATALOG_URL}/admin/products`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify(req.body ?? {}),
    });
    reply.code(res.status);
    return await res.json();
  });

  app.patch("/admin/products/:id/publish", async (req, reply) => {
    const id = (req.params as any).id as string;
    const res = await fetch(`${env.CATALOG_URL}/admin/products/${id}/publish`, { method: "PATCH" });
    reply.code(res.status);
    return await res.json();
  });

  app.get("/products", async (req, reply) => {
    const res = await fetch(`${env.CATALOG_URL}/products`);
    reply.code(res.status);
    return await res.json();
  });

  app.get("/products/:id", async (req, reply) => {
    const id = (req.params as any).id as string;
    const res = await fetch(`${env.CATALOG_URL}/products/${id}`);
    reply.code(res.status);
    return await res.json();
  });

  // ---- Search ----
  app.get("/search", async (req, reply) => {
    const q = (req.query as any)?.q ?? "";
    const url = new URL(`${env.SEARCH_URL}/search`);
    url.searchParams.set("q", String(q));
    const res = await fetch(url);
    reply.code(res.status);
    return await res.json();
  });

  // ---- Inventory (admin) ----
  app.post("/admin/stock", async (req, reply) => {
    const res = await fetch(`${env.INVENTORY_URL}/admin/stock`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify(req.body ?? {}),
    });
    reply.code(res.status);
    return res.status === 204 ? null : await res.json();
  });

  app.get("/stock/:sku", async (req, reply) => {
    const sku = (req.params as any).sku as string;
    const res = await fetch(`${env.INVENTORY_URL}/stock/${encodeURIComponent(sku)}`);
    reply.code(res.status);
    return await res.json();
  });

  // ---- Orders ----
  app.post("/checkout", async (req, reply) => {
    const res = await fetch(`${env.ORDERS_URL}/checkout`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify(req.body ?? {}),
    });
    reply.code(res.status);
    return await res.json();
  });

  app.get("/orders/:id", async (req, reply) => {
    const id = (req.params as any).id as string;
    const res = await fetch(`${env.ORDERS_URL}/orders/${id}`);
    reply.code(res.status);
    return await res.json();
  });

  // ---- Shipping ----
  app.get("/shipments/:orderId", async (req, reply) => {
    const orderId = (req.params as any).orderId as string;
    const res = await fetch(`${env.SHIPPING_URL}/shipments/${orderId}`);
    reply.code(res.status);
    return await res.json();
  });

  await app.listen({ port: Number(env.PORT), host: "0.0.0.0" });
}

main().catch((err) => {
  // eslint-disable-next-line no-console
  console.error(err);
  process.exit(1);
});

