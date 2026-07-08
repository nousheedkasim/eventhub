# System Architecture Specification: EventHub

## 📑 Document Overview
This document establishes the technical blueprint, service topology, global data communication contracts, and relational storage designs for the EventHub ecosystem. EventHub is engineered as a decoupled, multi-service platform optimized for handling high-volume concurrent ticket allocations and ironclad financial auditing. 

This document serves as the single source-of-truth for both human engineering teams and autonomous AI agents to ensure strict compliance with performance boundaries, schema normalization, and global JSON signatures.

---

## 1. High-Level System Topology & Communication

EventHub operates across an isolated, hybrid service network. The decoupled tiers utilize distinct communication fabrics depending on their synchronous or asynchronous consistency requirements.

                ┌────────────────────────────────────────┐
                │            Next.js Frontend            │
                │  (Vendor, Attendee, & Admin Portals)   │
                └───────────────────┬────────────────────┘
                                    │ REST API (/api/v1) [Sync HTTP]
                                    ▼
                ┌────────────────────────────────────────┐
                │          Laravel 11 Core API           │
                │    (Layered Architecture: C->S->R)     │
                └───────────┬────────────────────┬───────┘
                            │                    │
               Sync HTTP    │                    │ Async Queue
              (Bearer Token)│                    │ (Redis / Horizon)
                            ▼                    ▼
    ┌─────────────────────────────────────┐  ┌─────────────────────────────────────┐
    │         Payment Microservice        │  │      Notification Microservice      │
    │  (Stripe/PayPal Gateways + Hooks)   │  │  (Email Logs, Webhooks + Retries)   │
    └─────────────────────────────────────┘  └─────────────────────────────────────┘
                            
 

 

### 🔁 Inter-Service Communication Tiers
1. **Frontend to Core API (Synchronous REST):** The Next.js UI communicates with the Laravel 11 Core API via stateful HTTP REST protocols over TLS. Public browsing is open, while authenticated domains enforce bearer tokens issued via Laravel Sanctum.
2. **Core API to Payment Microservice (Synchronous HTTP with Circuit Breaking):** Critical authorization tasks (card verification, capture, reverse tokens) occur over synchronous server-to-server HTTP requests. Authorization headers contain cryptographic shared-secret tokens. Timeout limits are constrained to 5.0 seconds to prevent long-running downstream thread blockages.
3. **Core API to Notification Microservice (Asynchronous Messaging):** Non-blocking external actions—such as sending purchase-receipt emails or firing outbound vendor data webhooks—are dispatched out-of-band. The Core API serializes payloads into a Redis data cache, managed via Laravel Horizon queues, where asynchronous worker processes consume them reliably.

---

## 2. Global Unified API Contract Specification

Every API endpoint under the `/api/v1` namespace must strictly enforce and respond with the uniform platform JSON envelope.

```json
{
  "success": boolean,
  "data": object | array | null,
  "message": "Human-readable descriptive context string"
}
🏢 Endpoint 1: Create Event
Route: POST /api/v1/events

Access Control: Authenticated; Vendor Only (Verified Status Required)

Request Payload Pattern:

JSON
{
  "title": "Global Developers Summit 2026",
  "description": "The definitive conference for modern system design.",
  "start_time": "2026-09-15T09:00:00Z",
  "end_time": "2026-09-17T17:00:00Z",
  "timezone": "America/New_York",
  "ticket_tiers": [
    {
      "name": "Early Bird",
      "capacity": 150,
      "price_cents": 29900
    },
    {
      "name": "VIP Pass",
      "capacity": 50,
      "price_cents": 89900
    }
  ]
}
Success Response Signature (201 Created):

JSON
{
  "success": true,
  "data": {
    "event_uuid": "8b5f3a09-e854-4694-81d3-3453df6244ab",
    "status": "draft",
    "created_at": "2026-07-08T14:50:00Z"
  },
  "message": "Event blueprint initialized successfully as draft."
}
🎟️ Endpoint 2: Reserve Tickets / Initiate Lock
Route: POST /api/v1/orders/checkout

Access Control: Authenticated; Attendee Only

Request Payload Pattern:

JSON
{
  "ticket_type_id": 412,
  "quantity": 2
}
Success Response Signature (200 OK):

JSON
{
  "success": true,
  "data": {
    "reservation_token": "res_v1_9a8b7c6d5e4f3g2h1",
    "expires_at": "2026-07-08T15:05:00Z",
    "total_price_cents": 59800,
    "currency": "USD"
  },
  "message": "Inventory successfully locked for 15 minutes. Proceed to transaction confirmation."
}
🪙 Endpoint 3: Process Time-Based Refund Request
Route: POST /api/v1/refunds

Access Control: Authenticated; Attendee or Platform Admin

Request Payload Pattern:

JSON
{
  "order_uuid": "ord_77392-19203-8821",
  "reason": "Accidental medical scheduling conflict"
}
Success Response Signature (200 OK):

JSON
{
  "success": true,
  "data": {
    "refund_uuid": "ref_00192-bc82-9911",
    "order_status": "refunded",
    "amount_refunded_cents": 59800,
    "penalty_applied_cents": 0,
    "refund_tier_applied": "100_percent_policy"
  },
  "message": "Cancellation request cleared within the 48-hour threshold. Full ledger reversal complete."
}
👑 Endpoint 4: Execute Vendor Settlements (Batch Payout)
Route: POST /api/v1/payouts/batch

Access Control: Authenticated; Platform Admin Only

Request Payload Pattern:

JSON
{
  "execution_date": "2026-07-08",
  "vendor_ids": [12, 15, 22]
}
Success Response Signature (202 Accepted):

JSON
{
  "success": true,
  "data": {
    "batch_job_uuid": "job_99120-ef82-3341",
    "total_vendors_queued": 3,
    "status": "processing"
  },
  "message": "Batch payout instruction locked. Asynchronous financial settlement threads activated."
}
3. Database Blueprint & ERD (MySQL 8.0 Optimized)
To guarantee complete ledger accuracy and multi-tenant data safety, the storage layer uses the MySQL InnoDB engine with explicit foreign key constraints and integer pricing definitions to prevent rounding drift.

Relational Entity Model (Mermaid.js)
Code snippet
erDiagram
    USERS {
        bigint_unsigned id PK
        string name
        string email UK
        string password
        string role "admin|vendor|attendee"
        timestamp created_at
    }
    VENDORS {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        string company_name
        string kyc_status "pending|verified|rejected"
        bigint balance_cents "Signed to allow negative reversals"
        timestamp created_at
    }
    EVENTS {
        bigint_unsigned id PK
        bigint_unsigned vendor_id FK
        string title
        text description
        timestamp start_time
        timestamp end_time
        string timezone
        string status "draft|published|completed|cancelled"
        timestamp deleted_at "SoftDelete Index"
    }
    TICKET_TYPES {
        bigint_unsigned id PK
        bigint_unsigned event_id FK
        string name "VIP|General"
        bigint_unsigned price_cents
        integer_unsigned total_capacity
        integer_unsigned remaining_inventory
        timestamp deleted_at "SoftDelete"
    }
    TICKET_RESERVATIONS {
        bigint_unsigned id PK
        bigint_unsigned ticket_type_id FK
        bigint_unsigned user_id FK
        integer_unsigned quantity
        string status "locked|released|converted"
        timestamp expires_at
    }
    WAITLISTS {
        bigint_unsigned id PK
        bigint_unsigned ticket_type_id FK
        bigint_unsigned user_id FK
        integer_unsigned priority_index
        timestamp created_at
    }
    ORDERS {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        bigint_unsigned event_id FK
        bigint_unsigned total_amount_cents
        bigint_unsigned platform_fee_cents
        string status "pending|paid|failed|refunded"
        timestamp created_at
    }
    ORDER_ITEMS {
        bigint_unsigned id PK
        bigint_unsigned order_id FK
        bigint_unsigned ticket_type_id FK
        integer_unsigned quantity
        bigint_unsigned price_cents
    }
    PAYMENTS {
        bigint_unsigned id PK
        bigint_unsigned order_id FK
        string idempotency_key UK
        string gateway_transaction_id
        bigint_unsigned amount_cents
        string status "success|failed"
        timestamp created_at
    }
    REFUNDS {
        bigint_unsigned id PK
        bigint_unsigned order_id FK
        string idempotency_key UK
        bigint_unsigned amount_cents
        bigint_unsigned penalty_cents
        timestamp created_at
    }
    PAYOUTS {
        bigint_unsigned id PK
        bigint_unsigned vendor_id FK
        string idempotency_key UK
        bigint_unsigned amount_cents
        string status "queued|completed|failed"
        timestamp created_at
    }

    USERS ||--o│ VENDORS : "extends_as"
    USERS ||--o临 TICKET_RESERVATIONS : "creates"
    USERS ||--o临 WAITLISTS : "joins"
    USERS ||--o临 ORDERS : "places"
    VENDORS ||--o临 EVENTS : "owns"
    VENDORS ||--o临 PAYOUTS : "receives"
    EVENTS ||--o临 TICKET_TYPES : "contains"
    EVENTS ||--o临 ORDERS : "collects"
    TICKET_TYPES ||--o临 TICKET_RESERVATIONS : "leases"
    TICKET_TYPES ||--o临 WAITLISTS : "tracks"
    TICKET_TYPES ||--o临 ORDER_ITEMS : "populates"
    ORDERS ||--o临 ORDER_ITEMS : "details"
    ORDERS ||--o│ PAYMENTS : "funds"
    ORDERS ||--o│ REFUNDS : "reverses"
🧠 Strategic Database Engineering Principles
1. MySQL Indexing Strategy & Concurrency Guarding
Composite Query B-Tree Indexes: An explicit index is generated on events(status, start_time) to optimize the frontend catalog queries. For soft deletes, structural tables combine the scope into ticket_types(event_id, deleted_at).

Locking Optimization: All write queries modifying ticket quantities explicitly use MySQL InnoDB row-level locking via SELECT ... FOR UPDATE blocks within active transactions to avoid race conditions.

Foreign Key Isolation: Indexes are systematically placed on all relational IDs to optimize execution times during complex multi-table JOIN operations.

2. Immutable Ledger & Financial Audit Trails
The schema enforces an append-only transaction design:

Tables like ORDERS, PAYMENTS, REFUNDS, and PAYOUTS strictly capture immutable events. Balance updates are never performed via raw variable adjustments; instead, a new ledger item is saved to trace changes perfectly.

3. Soft vs. Hard Deletion Boundaries
Soft Deletions (deleted_at nullable timestamps): Applied exclusively to structural items like EVENTS and TICKET_TYPES so that existing accounting receipts and customer orders can safely resolve historical references.

Hard Deletions (DELETE operations): Reserved for transient tables like TICKET_RESERVATIONS once a user's 15-minute lease expires, preventing index bloat within MySQL.

4. Automated Background Process Design
To decouple long-running state tracking tasks from the main HTTP response loop, two automated cron routines are engineered natively into the background architecture.

⏱️ Worker 1: 15-Minute Inventory Release Engine & Waitlist Processor
Execution Frequency: Runs every 60 seconds via the application task framework (schedule:run).

Operational Flow: 1. The worker queries the ticket_reservations data store to pull all allocation instances where expires_at is less than the current execution timestamp and the operational condition remains marked as locked.
2. For each matching record, an isolated, atomic transaction block opens.
3. The target record status is modified to released.
4. The engine performs an atomic SQL calculation incrementing the matching ticket_types.remaining_inventory capacity count by the released allocation value.
5. The processor checks the waitlists table for the matching ticket_type_id sorted sequentially by priority_index.
6. If a waitlisted customer exists, a background event payload is instantly pushed to the Notification Microservice to notify them that a ticket slot has freed up.
7. The transient reservation row is hard-deleted from the transaction lease table to keep database reads lightweight.

🌙 Worker 2: Nightly Payout Batch Processing Engine
Execution Frequency: Executes once daily at 00:00 UTC.

Operational Flow:

The processor targets the events table to aggregate all rows whose active event schedules (end_time) have officially passed and whose platform condition is not yet marked as completed.

The system initiates an audit routine mapping every successful order linked to that individual event identifier, summing total gross revenue collected.

It applies the platform's commission percentage rules to determine the platform fee cuts.

The leftover net earnings are calculated and appended to the vendor's target structural ledger tracking balances.

It generates a collection of payment requests inside the database, setting their statuses to queued.

Individual background queue tasks are fired off to the isolated Payment Microservice to trigger external bank transfers. Each payload features a Deterministic Idempotency Key derived from the vendor, transaction value, and processing date string (e.g., payout_v1_vendor12_20260708) to eliminate any risk of double-processing.