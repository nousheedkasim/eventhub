# EventHub — Working Flow (Assessment Points)

## Flow 1: Vendor Onboarding
1. Vendor registers → POST /api/register (type: vendor)
2. Vendor creates profile → POST /api/v1/vendors
3. KYC submitted → kyc_status: pending
4. Admin approves/rejects → POST /api/v1/vendors/{id}/approve or /reject
5. Notification sent → vendor_approval email
6. Vendor adds bank details → PUT /api/v1/vendors/{id}

## Flow 2: Event Creation
1. Vendor creates event → POST /api/v1/events
2. Adds ticket types → POST /api/v1/ticket-type (GA, VIP, Early Bird, Group)
3. Each type has: price, inventory, available_from/until
4. Public listing → GET /api/v1/events (no auth)
5. Attendee views detail → GET /api/v1/events/{id}

## Flow 3: Ticket Purchase
1. Attendee selects tickets + quantity (frontend cart)
2. Checkout → POST /api/v1/orders
   - Redis lock acquired per ticket type
   - DB row lock (SELECT FOR UPDATE)
   - 15-min hold created
   - Inventory decremented
   - Early-bird discount (>=14 days = 10% off)
   - Group bundle discount (>=4 tickets = 20% off)
   - Audit trail logged
3. Payment → POST /api/v1/payments
   - Idempotency key sent
   - StripeSimulator or PayPalSimulator processes
   - Webhook callback: POST /api/v1/webhooks/payment
   - Order: held → paid
4. Confirmation email → notification service
5. Webhook to vendor → order_webhook

## Flow 4: Expired Hold Cleanup
1. Cron every minute: orders:cleanup-expired-holds
2. Finds held orders past expiry
3. Releases inventory, marks expired
4. Waitlist notified if tickets freed

## Flow 5: Refund Processing
1. Attendee requests → POST /api/v1/refunds
2. Payment service applies policy:
   - >48h before event → full refund
   - 24-48h → 50% refund
   - <24h → rejected
3. Core API: order → refunded, inventory released
4. Audit trail logged

## Flow 6: Payout Processing
1. Daily cron: payouts:process-batches (00:00)
2. Finds verified vendors with completed orders
3. Calculates: gross - commission = net
4. Enforces $50 minimum threshold
5. Creates payout_batch + payout records
6. Payment service settles
7. Notification → payout_notification email

## Flow 7: Vendor Analytics
1. Dashboard: total events, sales, revenue, pending payouts
2. Per-event breakdown table (event, orders, revenue)
3. Payout page: gross, fees, paid out, available balance

## Flow 8: Admin Operations
1. Platform metrics: vendors, events, orders, revenue
2. Pending vendor approvals (approve/reject)
3. Dispute queue with resolve/reject modal
4. Vendor list with KYC status

## Flow 9: Notification System
1. Core API publishes to Redis queues
2. Bull workers consume (email + webhook)
3. 4 email types, 2 webhook types
4. 5 retries, exponential backoff
5. Dead-letter persisted to JSON files

## Flow 10: Background Jobs
| Job | Schedule |
|---|---|
| Expired hold cleanup | Every minute |
| Payout batches | Daily 00:00 |
| Event reminders | Hourly |
| Sales reports | Daily 01:00 |
| Waitlist processing | Every 5 min |

## Flow 11: Database
- 20 migrations covering all tables
- Audit trails: order_events, payment_events, refund_events
- Soft deletes: events, vendors, ticket_types

## Flow 12: Documentation
- CLAUDE.md (488 lines)
- 3 agent skills (backend, payment, notification)
- Requirement analysis, architecture + ERD, decision log (8 entries)
- API docs (615 lines), development plan, docker-compose

## Flow 13: Unit Tests
- OrderServiceTest: 5 tests
- PayoutServiceTest: 7 tests
- InventoryServiceTest: 10 tests

## Flow 14: Frontend URL Structure
- `/` — Home page (attendee default)
- `/events` — Public event browsing
- `/events/[id]` — Event detail + purchase
- `/orders` — Attendee order history
- `/attendee` — Attendee portal
- `/vendor` — Vendor login/register
- `/vendor/dashboard` — Vendor dashboard + analytics
- `/admin` — Redirects to /admin/vendors
- `/admin/login` — Admin login
- `/admin/vendors` — Vendor management/approvals
- `/admin/disputes` — Dispute queue
- `/admin/metrics` — Platform metrics

## Remaining
- Video walkthrough (15-20 min) — only pending item
