import React, { useMemo, useState } from "react";

type Product = {
  id: string;
  sku: string;
  name: string;
  description: string;
  price_cents: number;
  currency: string;
  status: string;
};

type Order = {
  id: string;
  customer_id: string;
  status: string;
  total_cents: number;
  currency: string;
  items: { sku: string; qty: number; price_cents: number }[];
};

type Shipment = {
  id: string;
  order_id: string;
  status: string;
  tracking_code: string;
};

async function api<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`/api${path}`, {
    ...init,
    headers: { "content-type": "application/json", ...(init?.headers ?? {}) },
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`${res.status} ${res.statusText}: ${text}`);
  }
  if (res.status === 204) return null as T;
  return (await res.json()) as T;
}

function moneyVnd(cents: number) {
  return `${(cents / 100).toLocaleString("vi-VN")} ₫`;
}

export function App() {
  const [sku, setSku] = useState("SKU-1");
  const [stock, setStock] = useState(100);

  const [newSku, setNewSku] = useState("SKU-1");
  const [newName, setNewName] = useState("T-Shirt");
  const [newDesc, setNewDesc] = useState("Nice shirt");
  const [newPrice, setNewPrice] = useState(99000);

  const [products, setProducts] = useState<Product[]>([]);
  const [searchQ, setSearchQ] = useState("shirt");
  const [searchItems, setSearchItems] = useState<any[]>([]);

  const [checkoutQty, setCheckoutQty] = useState(1);
  const [orderId, setOrderId] = useState("");
  const [order, setOrder] = useState<Order | null>(null);
  const [shipment, setShipment] = useState<Shipment | null>(null);

  const [msg, setMsg] = useState<string>("");
  const [err, setErr] = useState<string>("");

  const currentItemPriceCents = useMemo(() => newPrice, [newPrice]);

  async function run<T>(fn: () => Promise<T>) {
    setErr("");
    try {
      const r = await fn();
      setMsg("OK");
      return r;
    } catch (e: any) {
      setMsg("");
      setErr(e?.message ?? String(e));
      throw e;
    }
  }

  return (
    <div style={{ fontFamily: "system-ui, -apple-system, Segoe UI, Roboto", padding: 20, maxWidth: 980, margin: "0 auto" }}>
      <h2 style={{ margin: 0 }}>eCommerce Demo UI</h2>
      <p style={{ marginTop: 6, color: "#555" }}>
        UI này gọi Gateway qua <code>/api</code> (Vite proxy). Chạy Docker rồi mở <code>http://localhost:5173</code>.
      </p>

      {msg ? <div style={{ background: "#e6ffed", border: "1px solid #b7ebc6", padding: 10, borderRadius: 8 }}>{msg}</div> : null}
      {err ? (
        <div style={{ background: "#fff1f0", border: "1px solid #ffa39e", padding: 10, borderRadius: 8, whiteSpace: "pre-wrap" }}>
          {err}
        </div>
      ) : null}

      <hr style={{ margin: "18px 0" }} />

      <section style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
        <div style={{ border: "1px solid #eee", borderRadius: 10, padding: 14 }}>
          <h3 style={{ marginTop: 0 }}>Inventory</h3>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <label>SKU</label>
            <input value={sku} onChange={(e) => setSku(e.target.value)} />
            <label>Available</label>
            <input type="number" value={stock} onChange={(e) => setStock(Number(e.target.value))} />
            <button
              onClick={() =>
                run(() =>
                  api("/admin/stock", {
                    method: "POST",
                    body: JSON.stringify({ sku, available: stock }),
                  }),
                )
              }
            >
              Set stock
            </button>
            <button
              onClick={() =>
                run(async () => {
                  const st = await api<{ sku: string; available: number; reserved: number }>(`/stock/${encodeURIComponent(sku)}`);
                  setMsg(`Stock ${st.sku}: available=${st.available}, reserved=${st.reserved}`);
                })
              }
            >
              Refresh
            </button>
          </div>
        </div>

        <div style={{ border: "1px solid #eee", borderRadius: 10, padding: 14 }}>
          <h3 style={{ marginTop: 0 }}>Catalog</h3>
          <div style={{ display: "grid", gridTemplateColumns: "80px 1fr", gap: 8, alignItems: "center" }}>
            <label>SKU</label>
            <input value={newSku} onChange={(e) => setNewSku(e.target.value)} />
            <label>Name</label>
            <input value={newName} onChange={(e) => setNewName(e.target.value)} />
            <label>Desc</label>
            <input value={newDesc} onChange={(e) => setNewDesc(e.target.value)} />
            <label>Price</label>
            <input type="number" value={newPrice} onChange={(e) => setNewPrice(Number(e.target.value))} />
          </div>
          <div style={{ display: "flex", gap: 8, marginTop: 10 }}>
            <button
              onClick={() =>
                run(async () => {
                  const r = await api<{ id: string }>("/admin/products", {
                    method: "POST",
                    body: JSON.stringify({
                      sku: newSku,
                      name: newName,
                      description: newDesc,
                      price_cents: newPrice,
                      currency: "VND",
                    }),
                  });
                  setMsg(`Created product_id=${r.id}`);
                })
              }
            >
              Create draft
            </button>
            <button onClick={() => run(async () => setProducts((await api<{ items: Product[] }>("/products")).items))}>Load products</button>
          </div>
          <div style={{ marginTop: 10, maxHeight: 220, overflow: "auto" }}>
            {products.map((p) => (
              <div key={p.id} style={{ display: "flex", gap: 8, alignItems: "center", padding: "6px 0", borderBottom: "1px solid #f3f3f3" }}>
                <code style={{ width: 220, overflow: "hidden", textOverflow: "ellipsis" }}>{p.id}</code>
                <div style={{ flex: 1 }}>
                  <b>{p.name}</b> <span style={{ color: "#666" }}>({p.sku})</span>
                  <div style={{ color: "#666" }}>{moneyVnd(p.price_cents)}</div>
                </div>
                <span style={{ fontSize: 12, padding: "2px 8px", border: "1px solid #ddd", borderRadius: 999 }}>{p.status}</span>
                <button onClick={() => run(() => api(`/admin/products/${p.id}/publish`, { method: "PATCH" }))}>Publish</button>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section style={{ marginTop: 16, border: "1px solid #eee", borderRadius: 10, padding: 14 }}>
        <h3 style={{ marginTop: 0 }}>Search</h3>
        <div style={{ display: "flex", gap: 8 }}>
          <input style={{ flex: 1 }} value={searchQ} onChange={(e) => setSearchQ(e.target.value)} />
          <button
            onClick={() =>
              run(async () => {
                const r = await api<{ items: any[] }>(`/search?q=${encodeURIComponent(searchQ)}`);
                setSearchItems(r.items);
              })
            }
          >
            Search
          </button>
        </div>
        <div style={{ marginTop: 10 }}>
          {searchItems.map((x: any) => (
            <div key={x.product_id} style={{ padding: "6px 0", borderBottom: "1px solid #f3f3f3" }}>
              <b>{x.name}</b> <span style={{ color: "#666" }}>({x.sku})</span> — {moneyVnd(x.price_cents)} —{" "}
              <span style={{ color: "#666" }}>{x.status}</span>
            </div>
          ))}
        </div>
      </section>

      <section style={{ marginTop: 16, border: "1px solid #eee", borderRadius: 10, padding: 14 }}>
        <h3 style={{ marginTop: 0 }}>Checkout (Saga)</h3>
        <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
          <label>SKU</label>
          <input value={newSku} onChange={(e) => setNewSku(e.target.value)} />
          <label>Qty</label>
          <input type="number" value={checkoutQty} onChange={(e) => setCheckoutQty(Number(e.target.value))} />
          <button
            onClick={() =>
              run(async () => {
                const r = await api<{ order_id: string; status: string }>("/checkout", {
                  method: "POST",
                  body: JSON.stringify({
                    customer_id: "ui-demo",
                    items: [{ sku: newSku, qty: checkoutQty, price_cents: currentItemPriceCents }],
                  }),
                });
                setOrderId(r.order_id);
                setMsg(`Checkout created order_id=${r.order_id} (${r.status})`);
              })
            }
          >
            Checkout
          </button>
        </div>

        <div style={{ display: "flex", gap: 8, alignItems: "center", marginTop: 10 }}>
          <label>Order ID</label>
          <input style={{ flex: 1 }} value={orderId} onChange={(e) => setOrderId(e.target.value)} />
          <button
            onClick={() =>
              run(async () => {
                const o = await api<Order>(`/orders/${orderId}`);
                setOrder(o);
                try {
                  const s = await api<Shipment>(`/shipments/${orderId}`);
                  setShipment(s);
                } catch {
                  setShipment(null);
                }
              })
            }
          >
            Refresh status
          </button>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12, marginTop: 10 }}>
          <div style={{ background: "#fafafa", border: "1px solid #eee", borderRadius: 10, padding: 12 }}>
            <b>Order</b>
            <pre style={{ margin: 0, whiteSpace: "pre-wrap" }}>{order ? JSON.stringify(order, null, 2) : "—"}</pre>
          </div>
          <div style={{ background: "#fafafa", border: "1px solid #eee", borderRadius: 10, padding: 12 }}>
            <b>Shipment</b>
            <pre style={{ margin: 0, whiteSpace: "pre-wrap" }}>{shipment ? JSON.stringify(shipment, null, 2) : "—"}</pre>
          </div>
        </div>
      </section>

      <p style={{ marginTop: 18, color: "#777" }}>
        Tips: nếu search chưa thấy product, hãy publish product rồi đợi 1–2 giây để Search consume Kafka event.
      </p>
    </div>
  );
}

