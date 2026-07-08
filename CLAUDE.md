# Project Structure Reference: EventHub Monorepo

This document outlines the official multi-service monorepo structure for the EventHub platform. All engineers and AI development agents must strictly follow these boundary lines when introducing new components, services, or data layers.

## 📂 Monorepo Layout Map

```text
eventhub-monorepo/
├── CLAUDE.md                           # Root AI context & project roadmap file
├── docker-compose.yml                  # Local development environment coordinator
├── README.md                           # Top-level walkthrough and setup map
│
├── .agent-skills/                      # AI Agent workspace definitions
│   ├── backend-skill.json
│   ├── payment-skill.json
│   └── notification-skill.json
│
├── core-api/                           # 1. MAIN APPLICATION (Laravel 11)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       └── V1/             # All API endpoints versioned under v1
│   │   │   │           ├── EventController.php
│   │   │   │           ├── CheckoutController.php
│   │   │   │           ├── RefundController.php
│   │   │   │           └── PayoutController.php
│   │   │   └── Middleware/
│   │   │
│   │   ├── Services/                   # STRICT LAYER: All Business Logic
│   │   │   ├── EventService.php
│   │   │   ├── InventoryService.php    # Handles Distributed Locking & Holds
│   │   │   ├── OrderProcessingService.php
│   │   │   └── PayoutCalculationService.php
│   │   │
│   │   ├── Repositories/               # STRICT LAYER: Data Access Abstraction
│   │   │   ├── EventRepository.php
│   │   │   ├── OrderRepository.php
│   │   │   └── TicketRepository.php
│   │   │
│   │   └── Models/                     # Pure Eloquent Data Relations
│   │       ├── User.php
│   │       ├── Event.php
│   │       ├── Order.php
│   │       ├── TicketReservation.php
│   │       └── Waitlist.php
│   │
│   ├── bootstrap/
│   │   └── app.php                     # Laravel 11 unified route & exception configuration
│   │
│   ├── config/
│   │   └── horizon.php                 # Redis Queue dashboard config
│   │
│   ├── database/
│   │   ├── migrations/                 # MySQL 8.0 schema migration files
│   │   └── seeders/                    # Realistic platform initial data seeders
│   │
│   ├── routes/
│   │   └── api.php                     # Contains all routes grouped under /api/v1
│   │
│   └── tests/
│       ├── Unit/                       # MANDATORY: Financial and lock isolation edge cases
│       │   ├── OrderProcessingTest.php
│       │   ├── PayoutCalculationTest.php
│       │   └── TicketInventoryTest.php
│       └── Feature/
│
├── frontend/                           # 2. FRONTEND PORTAL (Next.js App Router)
│   ├── src/
│   │   ├── app/                        # View mappings for roles
│   │   │   ├── admin/                  # Admin dashboard routes
│   │   │   ├── vendor/                 # Vendor panel routes
│   │   │   ├── attendee/               # Ticket discovery & user histories
│   │   │   └── layout.tsx
│   │   │
│   │   ├── components/                 # Component Library items (shadcn/ui layouts)
│   │   │   └── ui/
│   │   │
│   │   ├── context/                    # Shared global application states
│   │   └── lib/
│   │       └── api-client.ts           # Interceptor-wrapped Axios instance for bearer auth
│   │
│   ├── package.json
│   └── tailwind.config.js
│
├── payment-service/                    # 3. PAYMENT MICROSERVICE (Slim Laravel/Lumen)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── PaymentSimulationController.php # Stripe & PayPal success/failure simulator
│   │   └── Services/
│   │       └── IdempotencyManager.php   # Redis token processing verification engine
│   └── routes/
│       └── api.php                     # Private internal service hook endpoints
│
└── notification-service/               # 4. NOTIFICATION MICROSERVICE (Laravel Worker / Node)
    ├── app/
    │   ├── Jobs/                       # Redis-bound queue event payload handlers
    │   │   ├── SendOrderConfirmationEmail.php
    │   │   ├── SendEventReminderEmail.php
    │   │   └── DispatchVendorWebhook.php
    │   └── Services/
    │       └── WebhookRetryEngine.php  # Handles exponential backoff logic (1s, 4s, etc.)
    └── storage/
        └── logs/                       # Fallback directory logging outbound communications