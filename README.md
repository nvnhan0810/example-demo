# eCommerce Microservices Demo (DDD/Clean Architecture, Event-Driven, Docker)

Mục tiêu của repo này là **demo eCommerce “đủ bài”** theo hướng **microservice thật** (chạy bằng Docker), áp dụng **DDD + Clean Architecture**, **event-driven** với **Kafka**, dùng **PostgreSQL** (mỗi service 1 database/schema riêng), tích hợp **Redis**, **Elasticsearch**, và có định hướng **TDD** (test pyramid + contract test).

> Đây là tài liệu thiết kế tổng hợp. Bạn có thể triển khai bằng ngôn ngữ/framework tuỳ chọn; README này ưu tiên tính **thực dụng** và **có thể build được trên Docker**.

---

## 1) Phạm vi tính năng (Full demo)

### Khách hàng (Customers)
- Đăng ký/đăng nhập (có thể demo OAuth sau)
- Hồ sơ khách hàng, địa chỉ giao hàng
- Segmentation đơn giản (new/returning, tier)

### Sản phẩm (Catalog)
- CRUD sản phẩm, danh mục, thuộc tính (variant/option)
- Giá bán, hình ảnh, mô tả SEO
- Trạng thái publish/draft

### Tồn kho (Inventory)
- Quản lý SKU, tồn khả dụng (available), giữ chỗ (reserved)
- Nhập/xuất kho (stock movements)
- Policy: “reserve on checkout, deduct on payment success”

### Đơn hàng (Orders)
- Giỏ hàng (Cart) & checkout
- Tạo đơn hàng từ cart, tính tổng tiền, thuế/ship (demo)
- Trạng thái đơn: `PENDING_PAYMENT -> PAID -> PACKING -> SHIPPED -> DELIVERED` (hoặc cancel/refund)

### Giao hàng (Shipping)
- Tạo shipment, tracking, cập nhật trạng thái
- Tính phí ship (demo rule-based)
- Tích hợp giả lập 3PL (webhook/event)

### Thanh toán (Payments) – demo (khuyến nghị có)
- Payment intent/transaction
- Callback giả lập thành công/thất bại
- Idempotency (đặc biệt quan trọng)

### Search/Recommendation – demo
- Index sản phẩm vào Elasticsearch
- Search theo keyword + filter (category, price range)

---

## 2) Kiến trúc tổng thể

### 2.1 Service boundaries (DDD Bounded Contexts)

Khuyến nghị tách service như sau (đủ để “microservice thiệt”, nhưng không quá nát):

- **API Gateway / BFF** (public edge)
  - Auth, rate limit, request routing, aggregation read models (tuỳ)
- **Identity Service** (AuthN/AuthZ)
  - Users, roles, tokens (hoặc tích hợp Keycloak)
- **Customer Service**
  - Customer profile, addresses
- **Catalog Service**
  - Product, category, attributes, variants
- **Inventory Service**
  - Stock, reservations, stock movements
- **Order Service**
  - Carts, orders, order lines, order state machine
- **Payment Service**
  - Payment intents, transactions, refunds (demo)
- **Shipping Service**
  - Shipment, tracking, carrier integration (mock)
- **Search Service**
  - Indexing + query product (read-only, Elastic)
- **Notification Service** (optional)
  - Email/SMS/push (demo) dựa trên events

**Nguyên tắc dữ liệu**: mỗi service sở hữu data của nó (database per service). Giao tiếp đồng bộ chỉ khi cần, còn lại ưu tiên events.

### 2.2 Synchronous vs Asynchronous

- **Sync (HTTP/gRPC)**: lấy dữ liệu “cần ngay” cho request hiện tại (ví dụ: Gateway -> Catalog để render product detail).
- **Async (Kafka events)**: cập nhật trạng thái xuyên service, tránh coupling, đảm bảo eventual consistency.

Ví dụ luồng checkout:
1. Order Service tạo order `PENDING_PAYMENT`, emit `OrderCreated`
2. Inventory Service nhận `OrderCreated` => reserve tồn kho, emit `InventoryReserved` hoặc `InventoryReserveFailed`
3. Payment Service nhận `OrderCreated` (hoặc `InventoryReserved`) => tạo payment, emit `PaymentSucceeded/PaymentFailed`
4. Order Service nhận events => chuyển state `PAID` hoặc `CANCELLED`
5. Shipping Service nhận `OrderPaid` => tạo shipment, emit `ShipmentCreated`

### 2.3 Clean Architecture (mỗi service)

Mẫu thư mục khuyến nghị:

- `src/domain/`
  - Entities/Aggregates, Value Objects, Domain Events, domain services
- `src/application/`
  - Use cases (commands/queries), ports (interfaces), DTOs, policies
- `src/infrastructure/`
  - DB repositories (Postgres), Kafka producers/consumers, Redis, external clients
- `src/interfaces/`
  - HTTP controllers, message handlers, mappers, validators

**Quy tắc**: Domain không phụ thuộc framework. Infrastructure phụ thuộc Application/Domain theo hướng “đi vào trong”.

---

## 3) Kafka vs RabbitMQ – chọn cái nào hợp lý?

### 3.1 Khi nào chọn Kafka (phù hợp cho eCommerce event-driven)
- **Event log** có thể replay (phục hồi, rebuild read model, audit)
- **High throughput**, scale consumers theo partition
- **Stream processing** (Kafka Streams/Flink) nếu sau này cần
- **Event sourcing-lite**: giữ “sự kiện” như nguồn dữ liệu lịch sử

Nhược điểm:
- Vận hành phức tạp hơn RabbitMQ (cluster, partitions, tuning)
- Không phải queue “công việc” truyền thống (dù vẫn làm được)

### 3.2 Khi nào chọn RabbitMQ
- **Work queue / task queue**: job processing, retry, delayed message
- Routing linh hoạt (exchange types), per-message TTL/DLX
- Setup tương đối nhẹ hơn

Nhược điểm:
- Không tối ưu cho “event log” replay lớn, analytics stream
- Consumer scaling khác mô hình Kafka

### 3.3 Khuyến nghị cho demo này
- **Chọn Kafka làm xương sống event bus** (domain events + integration events).
- Nếu cần “job queue” (email sending, image processing), có thể:
  - dùng **Kafka + retry topics + DLQ** (demo được), hoặc
  - thêm RabbitMQ sau (không bắt buộc cho bản demo).

**Kết luận**: eCommerce microservice event-driven + search indexing + audit -> **Kafka hợp lý hơn** làm nền. RabbitMQ phù hợp hơn cho **background jobs**.

---

## 4) Dữ liệu & storage

### 4.1 Postgres (database per service)

Mỗi service có DB riêng, ví dụ:
- `customers_db`
- `catalog_db`
- `inventory_db`
- `orders_db`
- `payments_db`
- `shipping_db`

### 4.2 Redis
Use cases khuyến nghị:
- Cache read model/product detail (Catalog)
- Idempotency keys (Payments/Orders)
- Session/token blacklist (Identity) (tuỳ)
- Distributed lock (cẩn trọng, chỉ demo)

### 4.3 Elasticsearch
Use cases:
- Product search (Search Service)
- Đồng bộ index từ Kafka `ProductPublished`, `ProductUpdated`, `ProductUnpublished`

---

## 5) Event design (Kafka)

### 5.1 Topic naming
- `catalog.product.v1`
- `inventory.reservation.v1`
- `orders.order.v1`
- `payments.payment.v1`
- `shipping.shipment.v1`

### 5.2 Message envelope (khuyến nghị)

Khuyến nghị chuẩn hoá payload theo “event envelope” (gần CloudEvents, nhưng giản lược):

- `event_id` (UUID)
- `event_type` (string, ví dụ `OrderCreated`)
- `event_version` (int, ví dụ `1`)
- `occurred_at` (ISO8601)
- `producer` (service name)
- `correlation_id` (trace theo request/checkout)
- `causation_id` (event gây ra event hiện tại)
- `payload` (business data)

### 5.3 Idempotency & ordering
- **Idempotency**: consumer phải “ăn lại” được event (store processed `event_id` trong table inbox).
- **Ordering**: đặt key = aggregate id (ví dụ `order_id`) để ordering trong 1 partition.

### 5.4 Outbox / Inbox pattern (bắt buộc trong microservice “thiệt”)
- **Transactional Outbox**: khi service ghi DB, đồng thời ghi outbox record trong cùng transaction; background publisher đẩy lên Kafka.
- **Inbox**: consumer ghi nhận event đã xử lý để chống duplicate.

---

## 6) Nghiệp vụ trọng tâm (luồng chuẩn)

### 6.1 Publish sản phẩm -> Search index
1. Catalog cập nhật product `PUBLISHED`
2. Catalog emit `ProductPublished`
3. Search consume => upsert document vào Elasticsearch

### 6.2 Checkout / Order lifecycle
1. Customer tạo cart (Order Service)
2. Checkout => Order Service tạo `OrderCreated` (PENDING_PAYMENT)
3. Inventory reserve => `InventoryReserved`/`InventoryReserveFailed`
4. Payment process => `PaymentSucceeded`/`PaymentFailed`
5. Order update state => `OrderPaid` hoặc `OrderCancelled`
6. Shipping create shipment => `ShipmentCreated`, cập nhật tracking

### 6.3 Failure cases (demo cũng nên có)
- Reserve fail => Order cancelled
- Payment fail => release reservation
- Payment success nhưng event duplicate => idempotency giữ đúng trạng thái

---

## 7) Testing strategy (TDD)

### 7.1 Test pyramid cho mỗi service
- **Unit tests (Domain/Application)**: entity, value object, state machine, policy (nhanh, nhiều)
- **Integration tests (DB, Kafka, Redis)**: repository, outbox publisher/consumer (ít hơn)
- **Contract tests**:
  - **API contract** giữa Gateway và services (OpenAPI)
  - **Event contract** (schema registry/JSON schema) giữa producers-consumers
- **E2E tests**: chạy docker-compose, bắn flows checkout/search (rất ít, nhưng có)

### 7.2 Recommendation kỹ thuật
- Bắt đầu bằng **unit tests** cho domain invariants (giá, trạng thái order, reserve rules)
- Thêm **component tests** cho outbox/inbox để đảm bảo “at-least-once” hoạt động

---

## 8) Security/Observability (demo nhưng “đúng chất”)

- **Auth**: JWT (Identity) + Gateway verify token
- **Authorization**: roles (admin/customer)
- **Tracing**: OpenTelemetry (optional), propagation `correlation_id`
- **Logging**: structured JSON logs, include `correlation_id`
- **Metrics**: Prometheus + Grafana (optional)

---

## 9) Docker Compose (giả lập microservice thật)

### 9.1 Thành phần hạ tầng trong compose

- Postgres (có thể chạy 1 instance nhiều DB hoặc nhiều container; demo nên 1 instance nhiều DB để nhẹ)
- Redis
- Elasticsearch
- Kafka + controller/broker (tuỳ distro)
- Kafka UI (tuỳ chọn)

### 9.2 Gợi ý `docker-compose.yml` (skeleton)

Bạn có thể tạo file theo skeleton dưới đây và thêm các service app sau khi code xong:

```yaml
services:
  postgres:
    image: postgres:16
    environment:
      POSTGRES_PASSWORD: postgres
      POSTGRES_USER: postgres
    ports:
      - "5432:5432"
    volumes:
      - pg_data:/var/lib/postgresql/data

  redis:
    image: redis:7
    ports:
      - "6379:6379"

  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.15.3
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
      - ES_JAVA_OPTS=-Xms512m -Xmx512m
    ports:
      - "9200:9200"

  kafka:
    image: bitnami/kafka:3.7
    environment:
      - KAFKA_CFG_NODE_ID=1
      - KAFKA_CFG_PROCESS_ROLES=controller,broker
      - KAFKA_CFG_CONTROLLER_QUORUM_VOTERS=1@kafka:9093
      - KAFKA_CFG_LISTENERS=PLAINTEXT://:9092,CONTROLLER://:9093
      - KAFKA_CFG_ADVERTISED_LISTENERS=PLAINTEXT://kafka:9092
      - KAFKA_CFG_CONTROLLER_LISTENER_NAMES=CONTROLLER
      - KAFKA_CFG_LISTENER_SECURITY_PROTOCOL_MAP=PLAINTEXT:PLAINTEXT,CONTROLLER:PLAINTEXT
      - ALLOW_PLAINTEXT_LISTENER=yes
    ports:
      - "9092:9092"

  kafka-ui:
    image: provectuslabs/kafka-ui:latest
    ports:
      - "8088:8080"
    environment:
      - KAFKA_CLUSTERS_0_NAME=local
      - KAFKA_CLUSTERS_0_BOOTSTRAPSERVERS=kafka:9092
    depends_on:
      - kafka

  # --- app services (placeholder) ---
  # gateway:
  # customers:
  # catalog:
  # inventory:
  # orders:
  # payments:
  # shipping:
  # search:

volumes:
  pg_data:
```

> Lưu ý: tuỳ distro Kafka bạn chọn mà env thay đổi. Skeleton trên dùng Bitnami (dễ chạy).

---

## 10) Lựa chọn ngôn ngữ/framework (đề xuất)

Bạn có thể chọn bất kỳ, nhưng để “build demo nhanh + DDD/Clean + TDD dễ”:

### Option A (đề xuất): TypeScript + NestJS
- Dễ tổ chức module theo clean architecture
- Ecosystem test (Jest), OpenAPI, validation tốt
- Kafka client, Postgres ORM (TypeORM/Prisma) đều ổn

### Option B: Go
- Nhẹ, nhanh, dễ containerize
- DDD vẫn làm tốt, test nhanh
- Cần tự build nhiều plumbing hơn (DI, validation)

### Option C: Java/Kotlin + Spring Boot
- Rất mạnh cho microservice, test/observability chuẩn
- Hơi “nặng” cho demo nhỏ nhưng rất “enterprise-real”

README này không khóa bạn vào option nào. Khi implement, chỉ cần tuân theo boundaries & patterns phía trên.

---

## 11) API (high-level endpoints)

Gateway (public):
- `GET /products`, `GET /products/:id`
- `POST /cart/items`, `GET /cart`
- `POST /checkout`
- `GET /orders`, `GET /orders/:id`

Catalog (internal/admin):
- `POST /admin/products`
- `PATCH /admin/products/:id/publish`

Inventory:
- `GET /stock/:sku` (admin/internal)

Payments:
- `POST /payments/intents`
- `POST /payments/callback` (mock)

Shipping:
- `POST /shipments`
- `POST /shipments/webhook` (mock carrier)

Search:
- `GET /search?q=...&filters=...`

---

## 12) Roadmap triển khai (để bạn code theo thứ tự hợp lý)

1. Dựng `docker-compose.yml` hạ tầng (Postgres/Redis/Elastic/Kafka)
2. Implement 1 service mẫu (Catalog) theo Clean Architecture + test
3. Implement outbox publisher + consumer mẫu với Kafka
4. Thêm Order + Inventory + Payment, hoàn thiện flow checkout end-to-end
5. Thêm Search indexing bằng events
6. Thêm Gateway + auth basic
7. Viết e2e test chạy trên compose

---

## 13) Tiêu chí “done” cho demo

- Chạy `docker compose up` và có thể:
  - tạo product, publish, search thấy ngay
  - tạo cart, checkout
  - inventory reserve/deduct theo events
  - payment callback đổi trạng thái order
  - shipping tạo shipment & tracking
- Có unit tests cho domain rules + integration test cho outbox/inbox
- Có ít nhất 1 e2e test cho checkout flow

---

## 14) Ghi chú quan trọng (microservice “thiệt”)

- **Không dùng shared database** giữa services
- **Không gọi sync dây chuyền dài** (tránh “distributed monolith”)
- **At-least-once delivery** là mặc định -> idempotency bắt buộc
- **Schema/event versioning**: luôn có `event_version`, và consumer chịu backward compatibility

---

## 15) Demo đã implement (chạy được)

Repo hiện tại đã implement các service sau (Fastify + KafkaJS + Postgres + Redis + Elasticsearch):

- **Gateway**: `localhost:3000`
- **Catalog**: CRUD product (draft) + publish -> emit event
- **Search**: consume event từ Kafka, index vào Elasticsearch, cung cấp API search
- **Inventory**: admin set stock, reserve/release theo events
- **Orders**: checkout -> emit `OrderCreated`, cập nhật trạng thái theo events
- **Payments**: consume `InventoryReserved`, tạo payment và **auto succeed** (demo) -> emit `PaymentSucceeded`
- **Shipping**: consume `OrderPaid`, tạo shipment & tracking

### Chạy hệ thống

```bash
docker compose up -d --build
```

### Ghi chú về “microservice thiệt”

- Mỗi service chạy **container riêng** và có **DB riêng**.
- Docker build đã được chỉnh để **mỗi image chỉ copy đúng source của service đó** (không còn copy toàn bộ `services/*` vào mọi image).

### UI Demo

- Mở **UI** tại `http://localhost:5173`
- UI gọi Gateway qua `/api` (Vite proxy), nên bạn không cần cấu hình CORS.

### Luồng demo nhanh (end-to-end)

1) Seed tồn kho:

```bash
curl -i -X POST localhost:3000/admin/stock \
  -H 'content-type: application/json' \
  -d '{"sku":"SKU-1","available":100}'
```

2) Tạo product & publish (để search):

```bash
PRODUCT_ID=$(curl -s -X POST localhost:3000/admin/products \
  -H 'content-type: application/json' \
  -d '{"sku":"SKU-1","name":"T-Shirt","description":"Nice shirt","price_cents":99000,"currency":"VND"}' \
  | node -e 'let d="";process.stdin.on("data",c=>d+=c).on("end",()=>console.log(JSON.parse(d).id))')

curl -s -X PATCH localhost:3000/admin/products/$PRODUCT_ID/publish
curl -s 'localhost:3000/search?q=shirt'
```

3) Checkout (Orders -> Inventory -> Payments -> Orders -> Shipping):

```bash
ORDER_ID=$(curl -s -X POST localhost:3000/checkout \
  -H 'content-type: application/json' \
  -d '{"customer_id":"c-1","items":[{"sku":"SKU-1","qty":1,"price_cents":99000}]}' \
  | node -e 'let d="";process.stdin.on("data",c=>d+=c).on("end",()=>console.log(JSON.parse(d).order_id))')

sleep 3
curl -s localhost:3000/orders/$ORDER_ID
curl -s localhost:3000/shipments/$ORDER_ID
curl -s localhost:3000/stock/SKU-1
```

### Kafka UI

- `http://localhost:8088`

