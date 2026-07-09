# Technical Team Lead — Take-Home Assessment

**Position:** Technical Team Lead
**Company:** NEXT Ventures
**Domain:** EventHub — Multi-Vendor Event Ticketing & Payout Platform
**Time Allocation:** 5 days
**Submission:** Private Git repository (GitHub/GitLab) + video walkthrough link

---

## Welcome

Thank you for investing time in this assessment. It reflects the kind of work a Technical Team Lead at FundedNext does daily: analyzing requirements under ambiguity, making architectural decisions across multiple services, writing production-quality code, and enabling other engineers (and AI tools) to contribute effectively.

This is not a trick test. There are no hidden gotchas. Evaluation covers how you think, prioritize, communicate, and build — not whether you can produce a feature-complete SaaS product in five days.

> Read this entire document before writing a single line of code.

---

## Suggested Timeline

| Day | Focus | Deliverables |
|---|---|---|
| **Day 1** | Read requirements. Analyze. Plan. | Requirement analysis document, technical decision log (initial), system architecture document with ERD |
| **Day 2** | Set up project scaffolding. Database migrations. Core API (main application). | Project structure, `CLAUDE.md`, docker-compose or setup instructions, database schema, basic CRUD endpoints |
| **Day 3** | Order processing with locking, payment microservice, inter-service communication. | Order flow end-to-end, payment simulation, webhook handling, unit tests for financial logic |
| **Day 4** | Notification microservice, background jobs, frontend (functional, not polished). | Notification queue + retry, cron jobs, vendor dashboard, attendee purchase flow |
| **Day 5** | Testing, documentation, AI workflow artifacts, video walkthrough. | Agent skills, seed data, API docs, final decision log, presentation video |

> **Important:** A well-planned, partially-implemented system with excellent documentation beats a "complete" system with no tests, no docs, and no architectural narrative. Prioritize ruthlessly.

---

## The Domain: EventHub

EventHub is a multi-vendor event ticketing and payout platform. Three stakeholder types interact with the system:

- **Vendors** (event organizers) — create events, configure ticket types, track sales, receive payouts.
- **Attendees** — browse events, purchase tickets, manage their orders, check in at events.
- **Platform Admins** — approve vendors, resolve disputes, configure commission rates, monitor platform health.

The platform handles money. Every financial operation must be **auditable, idempotent, and resilient to failure**. This is the core of what is being evaluated.

---

## Technical Requirements

### 1. Main Application — Core API

**Stack:** Laravel 11, PHP 8.2+

The central service. All other services communicate through it or are orchestrated by it.

**Functional scope:**
- **Event management** — CRUD operations, scheduling (start/end datetime with timezone), capacity management per ticket type, event status lifecycle (`draft → published → ongoing → completed → cancelled`).
- **Ticket types** — Early bird (time-limited pricing), VIP, general admission, group bundles (buy 4 get discount). Each type has its own inventory, pricing, and availability window.
- **Order processing** — Cart/checkout flow with distributed locking to prevent overselling. When an attendee begins checkout, tickets are held for 15 minutes. If payment is not completed, the hold expires and tickets return to inventory.
- **Attendee management** — Registration, order history, ticket transfers (optional, nice-to-have), QR-code-based check-in.
- **Vendor onboarding** — Registration, profile management, KYC status tracking (`pending → verified → rejected`), bank/payout account details.
- **Payout management** — Vendor settlement calculation with configurable platform commission (percentage-based). Minimum payout threshold. Payout request and approval flow.
- **Admin endpoints** — Platform-wide analytics (total sales, active events, vendor count), vendor approval/rejection, dispute resolution (attendee requests refund, admin mediates).
- **Authentication & authorization** — API token-based auth. Role-based access: admin, vendor, attendee. A vendor must not access another vendor's data. An attendee must not access admin routes.
- **API versioning** — All routes under `/api/v1`.

**Architectural constraints:**
- Layered architecture: **Controller → Service → Repository → Model**. Business logic lives in services, not controllers. Data access is abstracted through repositories.
- Consistent API response format across all endpoints:
  ```json
  { "success": true, "data": {}, "message": "..." }
  ```
- Proper exception handling and structured logging.

**Unit tests required for:**
- Order processing (ticket hold, expiry, concurrent purchase attempts)
- Payout calculation (commission deduction, minimum threshold enforcement)
- Ticket inventory management (capacity limits, oversell prevention)

---

### 2. Frontend

**Stack:** Next.js 14 (recommended) or any modern framework you are comfortable with.

Not evaluating pixel-perfect design — evaluating whether you can build a functional UI that demonstrates the data flow between frontend and backend.

**Required views:**
- **Vendor dashboard** — Create/edit events, view sales per event, payout history and status, basic analytics (tickets sold, revenue).
- **Attendee pages** — Event listing/discovery, event detail with ticket type selection, checkout flow, order history.
- **Admin panel** — Pending vendor approvals, platform metrics overview, basic dispute/refund queue.

Use a component library (Ant Design, shadcn/ui, Material UI — your choice). Demonstrate state management, API integration, and error handling.

---

### 3. Payment Microservice

**Stack:** Laravel (recommended) or Node.js

A separate service that abstracts payment processing away from the main application.

**Requirements:**
- Simulate at least **2 payment gateways** (e.g., "StripeSimulator" and "PayPalSimulator"). Each should have configurable success/failure rates for testing.
- **Payment creation** — Main app sends order details; payment service initiates processing and returns a pending status.
- **Webhook simulation** — After a short delay (or immediately), the payment service calls back to the main app with a payment status update (success/failure).
- **Refund processing** — Full and partial refunds. Time-based refund policy:
  - Full refund if requested more than 48 hours before event
  - 50% refund if 24–48 hours
  - No refund under 24 hours
- **Payout execution** — Receives payout batch from main app, processes vendor settlements, reports results back.
- **Idempotency** — Duplicate payment requests with the same idempotency key must not create duplicate charges.
- **Authentication** — Shared secret or token-based auth between main app and payment service. No payment endpoint is publicly accessible.

---

### 4. Notification Microservice

**Stack:** Node.js (recommended) or Python

A queue-driven service responsible for all outbound communications.

**Requirements:**
- **Email notifications** (simulated — log to file or console, do not require actual SMTP):
  - Order confirmation (to attendee)
  - Event reminder (24 hours before event, to attendees with tickets)
  - Payout completed (to vendor)
  - Vendor approval/rejection (to vendor)
- **Webhook delivery to vendors** — Vendors can register a webhook URL. The service delivers event payloads (new order, event sold out, payout sent) to that URL.
- **Queue-driven** — Uses Redis or RabbitMQ. The main app publishes notification jobs; this service consumes them.
- **Retry logic** — Failed deliveries retry with exponential backoff (e.g., 1s, 4s, 16s, 64s). Max 5 retries. Dead-letter after exhaustion.
- **Delivery tracking** — Record delivery status (sent, failed, retrying) per notification.

---

### 5. Background Processes & Cron Jobs

These run within the main application or as separate scheduled commands.

| Job | Schedule | Description |
|---|---|---|
| **Payout batch processing** | Daily | Calculate pending vendor settlements. Deduct platform commission. Queue payouts to payment service. |
| **Event reminders** | Hourly check | Find events starting within 24 hours. Queue reminder notifications for ticket holders who have not been reminded yet. |
| **Expired reservation cleanup** | Every 5 minutes | Release ticket holds older than 15 minutes. Return inventory. Mark orders as expired. |
| **Sales report generation** | Daily | Aggregate daily sales per vendor and platform-wide. Store for dashboard consumption. |
| **Waitlist processing** | On ticket release | When cancellations or expired holds free up tickets, notify waitlisted attendees in order. |

> Each job must handle failure gracefully. If the payout batch fails midway, it should not double-pay vendors already processed.

---

### 6. Database Design

Design the schema from scratch. You must provide:

- An **ERD diagram** (any tool — dbdiagram.io, draw.io, Mermaid, hand-drawn scan — only the content matters).
- **Migration files** in code (Laravel migrations or equivalent).
- **Documentation** explaining:
  - Why you normalized or denormalized specific tables.
  - Your indexing strategy (which columns, why, expected query patterns).
  - How you handle audit trails on financial transactions (orders, payments, refunds, payouts).
  - Soft delete strategy — what gets soft-deleted vs hard-deleted and why.

---

## What You Must Deliver

### A. Planning & Architecture (Before Coding)

These documents should exist in your repository. They demonstrate how you think before you build.

**1. Requirement Analysis Document**
- Break the requirements above into user stories or functional specifications.
- Identify ambiguities in these requirements and document the assumptions you made.
- Create a priority matrix: what is must-have for your submission vs. nice-to-have if time allows.
- Identify risks: what is the hardest part of this project? What could go wrong? What would you flag to a product manager before starting?

**2. System Architecture Document**
- High-level diagram showing all services, their communication protocols, data flow direction, and authentication mechanisms.
- API contract design for key endpoints (at minimum: create event, purchase ticket, process refund, calculate payout). Include request/response shapes.
- Database ERD with written explanations of key relationships.
- Background job design: what triggers each job, what happens on failure, how you prevent duplicate processing.
- Authentication and authorization strategy across all services.

**3. Technical Decision Log**
- For each significant technology or library choice, document why you chose it and what alternatives you considered.
- Document trade-offs you made due to the time constraint.
- Include a "with more time" section describing what you would improve, add, or redesign.

### B. AI Workflow Artifacts

This is **non-negotiable**. At FundedNext, AI-augmented development is part of the daily workflow. Need to see that you can structure a codebase for both human and AI developers.

**1. `CLAUDE.md` (or equivalent project instruction file)**
- A root-level file that any developer — or AI coding agent — can read to become productive within 30 minutes.
- Must cover: project structure, how to set up and run all services, architecture overview, coding conventions, available commands, and common development tasks.

**2. Agent Skills / Workflow Definitions**
- Define at least 2–3 scoped agent skills (e.g. a "backend" skill scoped to the main Laravel app, a "payment-service" skill scoped to the payment microservice, a "notifications" skill for the notification service).
- Each skill should define: the service boundary, key files and patterns, how to run tests, and what this service is responsible for.
- Demonstrate how a new developer joining the project would use these skills to contribute effectively without reading the entire codebase.

**3. Development Plan / Task Breakdown**
- Document how you broke the work into phases and what you tackled first and why.
- Include a team delegation plan: if you had 3–4 developers and 2 weeks instead of working solo for 5 days, how would you divide the work? What are the parallelizable streams? What are the dependencies between streams?

### C. Code Deliverables

1. All source code in a single monorepo (recommended) or clearly organized multi-repo with a root README linking them.
2. Working setup instructions. Either a `docker-compose.yml` that brings up all services, or step-by-step local setup instructions that can be followed without guessing. If the reviewer cannot run your project, that is a serious problem.
3. Unit tests for core business logic — specifically order processing, payout calculations, and ticket inventory management. Meaningful tests over high coverage numbers.
4. API documentation — a Postman collection, OpenAPI/Swagger spec, or a thorough README with endpoint documentation. The reviewer must be able to test your API without reading your source code.
5. Seed data — a migration, seeder, or script that populates the database with enough realistic data to demonstrate the system working (vendors, events, tickets, orders, payouts).

### D. Video Walkthrough

Record a 15–20 minute video (screen share with narration). Do not exceed 20 minutes.

Cover the following:
1. **Architecture overview** — Walk through your system design. How do the services communicate? Where does data live?
2. **Key technical decisions** — Pick 2–3 decisions you are most proud of (or found most challenging) and explain your reasoning.
3. **Live demo** — Show the system running. Walk through at least: creating an event as a vendor, purchasing a ticket as an attendee, processing a payout, handling a refund.
4. **AI workflow** — Show your `CLAUDE.md` and agent skills. Demonstrate (or explain) how someone would use them.
5. **Retrospective** — What would you improve? What would you do differently as a tech lead with a team vs. working solo? How would you approach the first sprint if this were a real product?

---

## Evaluation Rubric

Shared intentionally — no hidden criteria. This is exactly how the submission will be scored.

### 1. Requirement Analysis & Product Thinking — 25%

| Score | Description |
|---|---|
| **Excellent** | Identified edge cases not listed in requirements (timezone conflicts, currency handling, concurrent checkout races, refund abuse scenarios). User stories cover all three stakeholders. Priority matrix reflects pragmatic trade-offs. Risk analysis is specific and actionable. |
| **Good** | Requirements are broken down clearly. Most edge cases identified. Reasonable assumptions documented. |
| **Weak** | Surface-level breakdown. No edge case identification. Assumptions not documented — reviewer has to guess what the candidate was thinking. |

### 2. System Architecture & Design — 25%

| Score | Description |
|---|---|
| **Excellent** | Service boundaries are clean and justified. Inter-service auth, error handling, and retry strategies are documented. Database schema handles financial audit trails, soft deletes where appropriate, and indexing is explained. Architecture handles partial failures gracefully (payment service down, notification queue backed up). |
| **Good** | Logical service decomposition. ERD is complete. API contracts are defined. Some failure scenarios considered. |
| **Weak** | Monolithic design crammed into microservices without clear boundaries. No failure handling strategy. Schema is purely CRUD with no thought to querying patterns or audit needs. |

### 3. Code Quality & Engineering — 20%

| Score | Description |
|---|---|
| **Excellent** | Clean, consistent code following the specified patterns. Error handling is thorough. Unit tests cover actual business logic edge cases (not just "create a record and assert it exists"). API responses are consistent. Security basics are covered (input validation, proper auth middleware, no mass-assignment vulnerabilities). Distributed locking is correctly implemented for inventory. |
| **Good** | Code is readable and organized. Tests exist and cover core flows. API is consistent. |
| **Weak** | No tests. Inconsistent patterns across the codebase. Business logic in controllers. SQL injection vectors. Financial operations without idempotency. |

### 4. AI Workflow & Developer Experience — 15%

| Score | Description |
|---|---|
| **Excellent** | `CLAUDE.md` is comprehensive enough that the reviewer can navigate and modify the codebase using AI tools within 30 minutes. Agent skills are well-scoped and demonstrate understanding of service boundaries. Development workflow is structured and reproducible. |
| **Good** | `CLAUDE.md` covers the basics. Skills exist and are functional. |
| **Weak** | No `CLAUDE.md`. No agent skills. Or they exist but are boilerplate that does not actually help anyone navigate the codebase. |

### 5. Technical Leadership Signals — 15%

| Score | Description |
|---|---|
| **Excellent** | Technical decision log shows mature reasoning — trade-offs are articulated, not just "I chose X because it's popular." Prioritization reflects shipping discipline. Video walkthrough communicates complex ideas clearly and concisely. Team delegation plan is realistic (accounts for dependencies, onboarding time, integration points). Documentation quality signals ownership — you would trust this person to run a system in production. |
| **Good** | Decisions are documented. Video is clear. Some thought given to team delegation. |
| **Weak** | No decision log. Video is a disorganized code scroll. No thought given to how anyone else would work on this. |

---

## Submission Instructions

1. Push your code to a private repository on GitHub or GitLab.
2. Grant access to the reviewer (you will receive their username separately).
3. Upload your video to Google Drive, Loom, or YouTube (unlisted) and include the link in your repository README.
4. Use the submission portal to upload your repository link and video link.
5. **Deadline:** 5 calendar days from when you receive this document, end of day in your timezone.

> If you have questions about the requirements, document your interpretation and move forward. Part of what we are evaluating is how you handle ambiguity.

---

## A Final Note

This assessment is designed to be challenging but fair. We do not expect perfection. We expect thoughtfulness. The best submissions are not the ones that implemented every feature — they are the ones where every decision, every line of code, and every document reflects a person who thinks before they build, communicates clearly, and builds systems that others can maintain.

**Good luck.**
