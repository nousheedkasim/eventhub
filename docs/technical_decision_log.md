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

## TDL-005: Notification Queue Technology (Bull on Redis)
* **Status:** Approved
* **Context:** The notification microservice must consume jobs asynchronously with reliable delivery, retry logic, and failure tracking. The assessment requires a queue-driven service with exponential backoff retries.
* **Decision:** We selected **Bull** (backed by Redis) as the queue technology for the Node.js notification worker.
* **Alternatives Considered:**
  * **RabbitMQ (via AMQP):** More feature-rich for complex routing, but adds significant operational overhead and requires a separate broker service. Overkill for a notification fan-out pattern.
  * **Kafka:** Designed for high-throughput event streaming, not suited for simple task queues with retry semantics.
  * **Agenda/MongoDB-based queues:** Adds MongoDB as a dependency. Redis is already present for caching and locking in the core API.
* **Consequences & Trade-offs:** Bull provides built-in support for exponential backoff, job prioritization, delayed jobs, and dead-letter tracking via failed job retention. The trade-off is that Bull requires Redis as a single point of failure — if Redis goes down, notification processing halts. We accept this because Redis is already a critical dependency for the ticketing locking mechanism.

---

## TDL-006: Frontend Framework (Next.js 14 App Router)
* **Status:** Approved
* **Context:** The frontend must demonstrate functional data flow between a web UI and the backend API across three user roles (vendor, attendee, admin). The assessment evaluates functionality, not pixel-perfect design.
* **Decision:** We selected **Next.js 14 with the App Router** as the frontend framework.
* **Alternatives Considered:**
  * **React (Vite):** Simpler setup, but lacks built-in routing, SSR capabilities, and file-based organization that Next.js provides out of the box.
  * **Nuxt.js (Vue):** Viable alternative, but the team has stronger React/TypeScript familiarity.
  * **Remix:** Strong data loading patterns, but smaller ecosystem and less community adoption than Next.js.
* **Consequences & Trade-offs:** Next.js introduces client/server component complexity and a steeper learning curve for developers unfamiliar with the App Router. However, it provides excellent developer experience with file-based routing, built-in API routes (unused here since we have a separate backend), and seamless TypeScript integration. We use Zustand for lightweight client-side state management rather than heavier solutions like Redux.

---

## TDL-007: Authentication Strategy (Laravel Sanctum)
* **Status:** Approved
* **Context:** The platform requires token-based authentication with role-based access control (admin, vendor, attendee). Auth must work across the core API and be consumable by the frontend. Inter-service authentication between core API and payment service also needs a mechanism.
* **Decision:** We adopted a dual approach:
  * **Laravel Sanctum** for API token authentication (user-facing endpoints).
  * **Shared secret middleware** for inter-service communication (core API ↔ payment service).
* **Alternatives Considered:**
  * **JWT (tymon/jwt-auth):** Stateful token validation with refresh tokens. More complex than needed for a server-rendered SPA that communicates with a single API.
  * **OAuth2/OIDC (Passport):** Full OAuth2 server implementation. Significant overhead for a 3-service architecture where inter-service auth can use a simpler shared secret.
  * **API Key authentication:** Too simplistic — doesn't support user sessions or token revocation.
* **Consequences & Trade-offs:** Sanctum tokens are database-backed, allowing revocation and audit trails. The shared secret approach for inter-service communication is pragmatic for this scale but would need to be replaced with mutual TLS or a service mesh in production. We accept this trade-off given the 5-day timeline.

---

## TDL-008: Testing Strategy (PHPUnit + RefreshDatabase)
* **Status:** Approved
* **Context:** The assessment requires unit tests for order processing, payout calculations, and ticket inventory management. Tests must cover business logic edge cases, not just CRUD assertions.
* **Decision:** We adopted **PHPUnit** with Laravel's `RefreshDatabase` trait for test isolation. Tests use real Eloquent models (not mocks) to validate actual database interactions and business logic.
* **Alternatives Considered:**
  * **Pest PHP:** More expressive syntax, but adds a dependency and team members may not be familiar with it.
  * **Mockery-heavy tests:** Isolating services via mocks would test interfaces rather than actual behavior. For financial logic, we need to verify the full transaction flow including database state.
  * **Dusk (browser testing):** Too slow and heavyweight for unit-level business logic tests.
* **Consequences & Trade-offs:** Using `RefreshDatabase` means each test runs within a transaction that is rolled back, ensuring test isolation. The trade-off is slightly slower test execution compared to in-memory database testing, but it guarantees that migrations are tested alongside business logic. We prioritized test quality (meaningful edge case coverage) over coverage percentage.

---

## 🛠️ "With More Time" Section (Pragmatic Tech Debt Roadmap)
Given the strict 5-day timeline, the following pragmatic compromises were accepted:
1. **Monorepo Directory Isolation:** The services are co-located within a single Git repository for deployment speed, though they are written to allow an instant split into independent infrastructure codebases later.
2. **Simplified Authentication Mesh:** Inter-service verification utilizes hardcoded shared cryptographic secrets inside environment configurations rather than a dedicated OAuth2/OIDC server setup.
3. **Frontend Component Library:** Only minimal UI components were created. A production system would adopt shadcn/ui or Ant Design for consistent, accessible components across all views.
4. **Observability Stack:** Currently logs to console/file only. A production deployment would integrate structured logging (ELK/Datadog), distributed tracing (OpenTelemetry), and alerting for failed financial operations.
5. **Database Migrations for Soft Deletes:** Events table soft-deletion was added post-initial design. A full audit of all soft-delete boundaries should be conducted before production to ensure consistent data retention policies.

