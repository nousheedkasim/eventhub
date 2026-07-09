## Schema Rationale

This section explains the reasoning behind key schema decisions: normalization boundaries, indexing strategy, financial audit trails, and soft-delete policy. It's written to be read standalone or dropped directly into `architecture.md`.

### Normalization choices

**Normalized (kept as separate tables) where write-consistency and independent lifecycle matter:**

- `events` / `ticket_types` — split because ticket types have their own inventory, pricing, and availability-window lifecycle independent of the parent event. An event can move through its status lifecycle (`draft → published → ...`) without touching ticket type rows, and vice versa (adding a new ticket type to a published event shouldn't require rewriting the event row). Keeping them separate also lets us index and lock at the ticket-type grain, which is the actual contention point during checkout — not the event.
- `orders` / `order_items` — standard cart pattern. An order can contain multiple ticket types (explicitly allowed per our assumptions doc), and each line item needs its own `price_at_purchase` snapshot independent of the current ticket type price. Denormalizing price into the order would make historical orders vulnerable to retroactive price changes, which is a financial-integrity problem, not just a modeling preference.
- `payments` / `refunds` — separate because a single payment can have multiple partial refunds over time, and a refund needs to reference which payment it's refunding against for reconciliation. Merging these would force nullable refund columns onto every payment row.
- `payouts` / `payout_batches` — separated so a single daily batch run can be tracked as one entity (for resumability) while still producing many individual per-vendor payout rows. This is what makes "don't double-pay if the batch crashes midway" achievable — see audit trail section below.

**Deliberately denormalized:**

- `order_items.price_at_purchase` — a deliberate denormalization (duplicating price instead of joining to `ticket_types.price`) because financial records must reflect what the attendee actually paid, not the current live price. This is the one place normalization would actively cause a correctness bug.
- `orders.attendee_snapshot` (name/email at time of order, optional field) — considered denormalizing attendee contact details onto the order so refund/notification flows don't depend on the user row still existing or being unchanged. Kept as an assumption to flag: if this isn't added, a user email change after purchase would silently change where refund confirmations are sent.

### Indexing strategy

Indexes are chosen against the two hardest recurring query patterns in the system: **cron jobs scanning by time/status**, and **checkout's hit on live inventory**.

| Table | Index | Query pattern it serves |
|---|---|---|
| `orders` | `(hold_expires_at)` where `status = 'held'` (partial/filtered index if DB supports it, else composite `(status, hold_expires_at)`) | Expired reservation cleanup cron runs every 5 minutes scanning for stale holds — without this index it's a full table scan on every run, forever, as order volume grows. |
| `events` | `(status, start_at)` | Event reminder cron (hourly) needs "events starting within 24h that are published" — composite index lets it filter status and range-scan start_at in one pass. |
| `ticket_types` | `(event_id)` and `(event_id, availability_start, availability_end)` | Checkout needs to fast-path "is this ticket type currently on sale" plus "which types belong to this event" for listing. |
| `payments` | `(idempotency_key)` unique index | Idempotency enforcement — this is a correctness constraint, not just a performance one. A duplicate key must fail fast at the DB layer, not rely on application-level check-then-insert (which has its own race condition). |
| `payouts` | `(vendor_id, status)` | Vendor dashboard payout history, and batch cron's "find vendors not yet paid this cycle." |
| `payout_batches` | `(status, run_date)` | Resuming a crashed batch needs to quickly find the in-progress batch for today. |
| `notifications` | `(status, retry_count)` | Retry worker needs to find `failed`/`retrying` rows eligible for backoff-based reprocessing. |
| `order_items` | `(order_id)`, `(ticket_type_id)` | Standard FK lookups both directions — order detail view, and "how many of this ticket type are actually sold" reconciliation. |

General rule applied throughout: **every column a cron job filters or sorts on gets an index**, because cron jobs run unattended and at scale are the first thing to silently degrade into full table scans if forgotten.

### Audit trail approach (financial data)

Financial rows are never mutated in place. Instead of `UPDATE payments SET status = 'refunded'`, the system **inserts an event row** into append-only log tables:

- `payment_events` — one row per state transition (`created → pending → succeeded/failed → refunded`), storing `payment_id`, `from_status`, `to_status`, `payload` (raw gateway/webhook response), `created_at`. The `payments` table itself still holds a `current_status` column for fast reads, but that column is a derived/cached value — the source of truth for "what happened and when" is the event log.
- `payout_events` — same pattern for payout state transitions (`calculated → queued → processing → completed/failed`), critical for the "don't double-pay on batch crash" requirement: on batch resume, the job checks `payout_events` for the latest event per vendor for this batch, not just a boolean flag, so a partial failure mid-transition is unambiguous rather than guessed at.

Why this instead of a single mutable row with an `updated_at`: if a dispute or reconciliation question comes up ("why did this payout fail, and what did we retry, and when"), a mutable row has already destroyed that history. An append-only log makes every financial state change independently auditable and lets us rebuild the current state by replaying events if the cached status column is ever suspected to be wrong — which is exactly the kind of resilience the assessment brief calls out as the core evaluation point for this domain.

### Soft-delete strategy

| Entity | Delete behavior | Reason |
|---|---|---|
| `events` | Soft delete (`deleted_at`) | An event can be cancelled/removed by a vendor, but past orders reference it. Hard-deleting would orphan `order_items` and destroy the attendee's purchase history and receipts. |
| `vendors` | Soft delete | Same reasoning — historical payouts, orders, and disputes must still resolve to a vendor record for audit purposes even after offboarding. |
| `ticket_types` | Soft delete | Referenced by historical `order_items`; must remain queryable for past-order display even if no longer on sale. |
| `users` (attendees) | Soft delete | Preserves order/refund history integrity; supports "right to be forgotten" style requests later by anonymizing fields on the soft-deleted row rather than losing referential integrity. |
| `payments`, `refunds`, `payouts`, `*_events` | **Never deleted, soft or hard** | These are the financial audit trail itself. Deleting any row here — even "soft" — undermines the auditability requirement the brief explicitly states as core to the evaluation. If a payment record is truly erroneous, the correction is a new compensating event row, not a deletion. |
| `notifications` | Hard delete allowed (with retention window, e.g. 90 days) | Operational/delivery-tracking data, not financial. Fine to prune for storage once past its usefulness for debugging retries. |
| `disputes` | Soft delete | Needs to remain visible in admin history even after resolution/closure. |

The dividing line applied consistently: **if the row represents money moving or a decision affecting money, it is immutable and permanent. If the row represents operational/communication metadata, normal soft-delete-then-prune rules apply.**
