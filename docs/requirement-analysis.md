# Requirement Analysis Document: EventHub

## 📑 Document Overview
This document serves as the foundation for the scoping, engineering patterns, and risk mitigation strategies of the EventHub platform. EventHub is a multi-vendor event ticketing and payout platform designed around three primary stakeholders: Vendors, Attendees, and Platform Admins. 

Because EventHub handles sensitive financial operations, transactional integrity, data auditability, and concurrency control are the highest priorities of this technical architecture. This analysis breaks down high-level business goals into concrete developer requirements, establishes a pragmatic 5-day MVP roadmap, maps critical race conditions, and documents explicit architectural assumptions to eliminate engineering ambiguity.

---

## 1. User Stories by Stakeholder Persona

### 🏢 Vendors (Event Organizers)
* **KYC Onboarding:** As a Vendor, I want to submit my profile, bank payout details, and KYC information so that platform admins can verify my account for payouts.
* **Event Lifecycle Management:** As a Verified Vendor, I want to create, edit, and update events with a specific start/end datetime, timezone, and operational status (Draft → Published → Ongoing → Completed → Cancelled).
* **Tiered Ticketing Architecture:** As a Vendor, I want to attach multiple ticket types (Early Bird, VIP, General Admission, Group Bundles) to an event, each with its own inventory, custom price points, and restricted availability windows.
* **Sales Performance Analytics:** As a Vendor, I want to access a dashboard showing tickets sold, real-time revenue breakdowns per event, and my historical settlement distributions.
* **Settlement & Payout Requests:** As a Vendor, I want the system to calculate my net earnings after platform commission deductions so I can track and request manual or automated bank payouts once I clear the minimum threshold.

### 🎟️ Attendees (Buyers)
* **Event Discovery:** As an Attendee, I want to browse and search a list of published events, drill down into event details, and see real-time ticket availability.
* **Guaranteed Inventory Checkout:** As an Attendee, I want selected tickets locked exclusively in my cart for 15 minutes during checkout so that they cannot be bought by someone else while I enter my payment details.
* **Order Management History:** As an Attendee, I want an authenticated dashboard to view my historical purchases, invoice items, and payment receipts.
* **Seamless Event Admission:** As an Attendee, I want to receive a digital ticket featuring a secure QR code so it can be scanned at the venue door for instant check-in.

### 👑 Platform Admins
* **Vendor Compliance Review:** As an Admin, I want a dedicated moderation queue to review, approve, or reject pending vendor applications based on their compliance data.
* **Global Commission Regulation:** As an Admin, I want to configure percentage-based platform commission models globally or per vendor to govern marketplace revenue.
* **Macro-Platform Surveillance:** As an Admin, I want a centralized command dashboard highlighting system-wide metrics like gross merchandise value (GMV), active listing volumes, and total vendor sign-ups.
* **Dispute Arbitration Queue:** As an Admin, I want to review escalated refund requests from attendees and legally execute reversals or mediation payouts.

---

## 2. Priority Matrix (5-Day MVP Scope)

To meet the strict 5-day delivery window, features are strictly partitioned to guarantee a highly stable, auditable core system over a broader, shallower codebase.

| Feature Set | Priority | Target Day | Inclusion Rationale |
| :--- | :--- | :--- | :--- |
| **Laravel 11 Core API Scaffolding** | Must-Have | Day 2 | Essential backend container, token-based authorization, and basic CRUD. |
| **Database Migrations & Core Schema** | Must-Have | Day 2 | Forms the concrete relational ledger required for financial auditability. |
| **Distributed Ticket Locking Engine** | Must-Have | Day 3 | Mitigates severe high-concurrency race conditions and overselling. |
| **Payment Service Simulation** | Must-Have | Day 3 | Simulates payment gateways with idempotency and webhook feedback loops. |
| **Queue-Driven Event Notifications** | Must-Have | Day 4 | Handled out-of-band via background workers using exponential backoff. |
| **Cron Processing (Cleanup & Payouts)** | Must-Have | Day 4/5 | Critical logic for clearing expired carts and processing batch settlements. |
| **Functional Next.js Client Views** | Must-Have | Day 4/5 | Unpolished layout demonstrating end-to-end data integration across all roles. |
| **Unit Tests for Financial & Inventory Core** | Must-Have | Day 3/5 | Validates complex checkout, pricing calculation, and inventory invariants. |
| **Ticket Transfers Engine** | Nice-to-Have | Beyond MVP | Non-critical business expansion; deferred to optimize transactional safety. |
| **Pixel-Perfect Responsive Styling** | Nice-to-Have | Beyond MVP | UI is explicitly requested to be functional to validate payloads over visuals. |

---

## 3. Critical Edge Cases & Technical Risks

### ⚡ Race Conditions & Ticket Overselling
* **The Risk:** Under extreme peak traffic, thousands of parallel database transaction threads read the same remaining inventory count simultaneously, executing multiple successful charges for a single physical seat.
* **The Mitigation:** Implement a multi-layered barrier. First, enforce atomic inventory reductions using **Redis-based Distributed Locking** combined with a 15-minute transactional lease tracking table (`ticket_reservations`). Second, utilize database row-level pessimistic locking (`SELECT ... FOR UPDATE`) inside the core payment reservation transaction to verify absolute ticket type inventory states before changing order records to a paid state.

### 🕒 Timezone Synchronization & Drift
* **The Risk:** Vendors create events using local relative datetimes (e.g., 8:00 PM EST), while attendees search across disparate global regions. Storing raw relative strings results in scheduling anomalies, incorrect expiration metrics, and broken cron trigger timelines.
* **The Mitigation:** The entire database, container ecosystem, and runtime application frameworks operate strictly in `UTC`. The `events` schema explicitly captures both a `start_time` timestamp (normalized into UTC) and a string field identifying the native localized timezone parameter (e.g., `America/New_York`). This preserves the localized scheduling context for frontend translation layers while shielding backend logic from time calculation drifts.

### 🪙 Floating-Point Precision Loss in Financials
* **The Risk:** Storing ledger balances, multi-tier platform fees, and refund totals as standard `FLOAT` or `DOUBLE` datatypes introduces binary base-2 rounding flaws, leaking penny increments across high-volume transactions.
* **The Mitigation:** All currency and fee metrics inside both the Core API and the Payment microservice are explicitly structured and processed as **integers representing the absolute lowest minor fractional unit** (e.g., cents, where \$10.50 is strictly represented as `1050`). Databases use the `BIGINT` datatype to guarantee precise integer arithmetic across currency bounds.

### 💥 Unhandled Webhook Failures & Missing States
* **The Risk:** If the payment microservice dispatches an un-retried success webhook confirming an order right as the Core API experiences momentary downtime or network interruption, the money is captured, but the attendee's order remains permanently stuck as "pending".
* **The Mitigation:** Webhooks must operate on an isolated queue infrastructure requiring an HTTP status `200` confirmation receipt. If a timeout or non-200 state occurs, the worker triggers an **exponential backoff policy** (1s, 4s, 16s, 64s) up to 5 times. After complete exhaustion, the event payload drops into a Dead Letter Queue (DLQ) paired with automated logging for manual team reconciliation.

### 🛑 Double Payout & Replay Attack Vulnerabilities
* **The Risk:** If a network failure occurs midway through the automated nightly vendor payout routine, restarting the cron task could inadvertently duplicate bank transfers to vendors whose settlements were already processed.
* **The Mitigation:** Every single financial movement (payment, refund, payout) is linked to a unique, deterministically derived **Idempotency Key**. The payment microservice enforces a database unique constraint on this token. If an operation arrives featuring an existing key, the payment engine intercepts it, drops the duplicate request execution, and immediately returns the previously computed processing response safely.

---

## 4. Ambiguities & Explicit Working Assumptions

1.  **KYC Validation Rigor:** The system tracks vendor onboarding via status cycles (`pending` → `verified` → `rejected`), but will simulate internal document collection. For this MVP, automated programmatic transition logic is assumed to verify vendors immediately upon profile completion so testing isn't blocked.
2.  **Single-Currency Marketplace Boundary:** The multi-vendor domain rules do not specify multi-currency conversions. To ensure data stability, it is assumed the platform operates entirely within a unified currency schema (e.g., USD cents), eliminating the need for real-time exchange rate dependencies.
3.  **Group Ticket Bundle Allocations:** Group bundle ticket specifications (e.g., "buy 4 get discount") deduct inventory dynamically from the central resource base. It is assumed that purchasing 1 group bundle counts as 4 distinct capacity deductions against the global `ticket_type` inventory limit.
4.  **Simulated Gateway Sandbox:** The platform does not incorporate real Stripe or PayPal API credentials. It is assumed that dedicated simulator client classes (`StripeSimulator`, `PayPalSimulator`) will mock real-world HTTP networking delays and introduce configurable failure rates to test payment exception handlers.
5.  **Soft-Deletion Financial Preservation:** To protect transactional auditing records, soft-deletes apply strictly to top-level structural definitions (`events`, `ticket_types`). All ledger, transaction, payment, and inventory allocation records remain permanently hard-persisted in the database to guarantee total financial compliance.