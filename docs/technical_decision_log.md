# Technical Decision Log: EventHub

## 📑 Document Overview
This Technical Decision Log (TDL) acts as an Architecture Decision Record (ADR) tracking the high-impact engineering choices made for the EventHub ecosystem. Every architectural choice has been weighed against the constraints of a high-concurrency, multi-vendor marketplace and a compressed 5-day delivery roadmap. This document provides visibility into the structural justifications, alternative patterns rejected, and long-term technical trade-offs accepted.

---

## TDL-001: Core API Architecture (Service-Repository Pattern)
* **Status:** Approved
* **Context:** The main Laravel 11 application must remain deeply maintainable, scalable, and isolated from framework-specific coupling. Standard Laravel applications often suffer from "Fat Controllers" or "Fat Models," blending HTTP layer handling with heavy financial and ticket-booking rules.
* **Decision:** We are adopting a strict, unidirectional **Controller ➔ Service ➔ Repository ➔ Model** architecture.
  * **Controllers:** Exclusively handle HTTP requests, input validation parsing, and returning uniform JSON signatures.
  * **Services:** Form the domain boundary. All business logic (e.g., verifying if a customer can request a refund) must reside here.
  * **Repositories:** Completely abstract Eloquent database queries.
* **Consequences & Trade-offs:** This configuration dramatically increases the file count and initial setup boilerplate. However, it completely separates the core business domain logic from framework components, making unit testing financial transactions straightforward and keeping the code clean for AI automation.

---

## TDL-002: Concurrency & Ticket Oversell Prevention Engine
* **Status:** Approved
* **Context:** High-demand ticket checkout flows create heavy database race conditions. If multiple parallel execution threads read an identical `remaining_inventory` number before writing an update, the platform will oversell events, creating major legal and accounting liability.
* **Decision:** Implement a dual-layered concurrency defense:
  1. An aggressive **Redis Distributed Lock** protecting a 15-minute temporary checkout hold (`ticket_reservations`).
  2. Database row-level pessimistic locking (`SELECT ... FOR UPDATE`) inside the core settlement transaction block to re-verify absolute stock states right before moving an order status to `paid`.
* **Alternatives Considered:** Database Optimistic Locking (`version` columns). Rejected because high-concurrency contention causes heavy exception rates and drops customer checkouts unnecessarily under extreme burst traffic.
* **Consequences & Trade-offs:** This introduces Redis as a critical system dependency. If the Redis cluster crashes, ticket holds fail. We accept this infrastructure trade-off because memory caching is the only performant way to guard a high-volume ticketing pool.

---

## TDL-003: Monetary Precision & Arithmetic Formatting
* **Status:** Approved
* **Context:** Storing and computing ledger balances, platform fees, and item prices using standard native database `FLOAT` or `DOUBLE` datatypes introduces binary floating-point rounding anomalies. Over high transaction volumes, fractional pennies drift, throwing off auditing calculations.
* **Decision:** All monetary metrics across the entire platform ecosystem (Core API, database tables, and JSON inputs/outputs) are represented and processed strictly as **integers mapping the absolute lowest fractional unit (Cents)**. A $50.00 ticket is represented and saved exclusively as `5000`. Database columns will strictly utilize the `BIGINT` data type.
* **Consequences & Trade-offs:** Frontend visualization components must format these integers back to decimals (e.g., dividing by 100) for human display. However, it completely avoids float-rounding bugs across all backend computations.

---

## TDL-004: Decoupled Multi-Service Strategy (Microservices vs. Monolith)
* **Status:** Approved
* **Context:** The specification explicitly mandates distinct spaces for processing Payments and triggering background Notifications. Running these heavy tasks inside the main application thread blocks web requests and harms responsiveness.
* **Decision:** We are decoupling the system into distinct operational spaces:
  * **Core API:** Laravel 11 for the central marketplace domain brain.
  * **Payment Service:** An isolated simulator communicating synchronously via secure HTTP shared secret headers.
  * **Notification Worker:** An asynchronous background process driven by a Redis queue message broker.
* **Consequences & Trade-offs:** Splitting services increases networking overhead and requires handling distributed failure loops (like writing exponential backoff retry logic for missing webhook responses). We accept this because decoupling keeps our main application thread highly responsive.

---

## 🛠️ "With More Time" Section (Pragmatic Tech Debt Roadmap)
Given the strict 5-day timeline, the following pragmatic compromises were accepted:
1. **Monorepo Directory Isolation:** The services are co-located within a single Git repository for deployment speed, though they are written to allow an instant split into independent infrastructure codebases later.
2. **Simplified Authentication Mesh:** Inter-service verification utilizes hardcoded shared cryptographic secrets inside environment configurations rather than a dedicated OAuth2/OIDC server setup.

