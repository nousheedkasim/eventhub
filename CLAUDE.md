# CLAUDE.md (EventHub)

This file documents the EventHub platform for both human developers and AI agents. It provides comprehensive information about project structure, architecture, setup instructions, and development workflow.

## Project Overview

EventHub is a multi-vendor event ticketing and payout platform built as a microservices architecture in a monorepo layout. The platform handles financial operations with strict requirements for auditability, idempotency, and resilience to failure.

### Core Services

- **core-api/** - Laravel 11 (main backend API)
- **frontend/** - Next.js 14 App Router (web application)
- **payment-service/** - Laravel payment simulator microservice
- **notification-service/** - Node.js notification worker microservice

### Infrastructure

- **MySQL 8.0** - Primary database
- **Redis** - Caching, distributed locking, and queue management
- **Docker Compose** - Container orchestration

---

## Monorepo Layout

```
eventhub/
├── core-api/              # Laravel 11 Core API
│   ├── app/
│   │   ├── Console/Commands/    # Background jobs (cleanup, payouts, reminders)
│   │   ├── Http/Controllers/    # API controllers
│   │   ├── Models/              # Eloquent models
│   │   ├── Repositories/        # Data access layer
│   │   └── Services/            # Business logic layer
│   ├── database/
│   │   ├── migrations/          # Database schema migrations
│   │   └── seeders/             # Seed data
│   ├── routes/
│   │   └── api.php              # API routes definition
│   └── tests/                   # Unit and feature tests
├── frontend/             # Next.js 14 Frontend
│   ├── app/
│   │   ├── admin/               # Admin panel pages
│   │   ├── attendee/            # Attendee pages
│   │   ├── vendor/              # Vendor dashboard pages
│   │   └── events/              # Event browsing pages
│   ├── components/
│   │   └── ui/                  # Reusable UI components
│   └── lib/                     # Utilities and API client
├── payment-service/      # Laravel Payment Microservice
│   ├── app/
│   │   ├── Http/Controllers/    # Payment processing endpoints
│   │   └── Jobs/                # Webhook jobs
│   └── database/
│       └── migrations/          # Payment service schema
├── notification-service/ # Node.js Notification Service
│   └── src/
│       ├── queues/              # Bull queue configuration
│       ├── services/            # Email and webhook services
│       ├── workers/             # Queue workers
│       └── server.js            # Express server
├── docs/                 # Documentation
│   ├── ASSESSMENT.md           # Original assessment requirements
│   ├── requirement-analysis.md  # Requirements breakdown
│   ├── system_architecture.md  # System design and ERD
│   ├── technical_decision_log.md # Architecture decisions
│   ├── setup-instructions.md   # Complete setup guide
│   ├── API_DOCUMENTATION.md    # API endpoint documentation
│   ├── development_plan.md     # Development roadmap
│   └── schema-rationale.md    # Database design rationale
├── .agent-skills/        # AI agent skill definitions
│   ├── backend-skill.json
│   ├── payment-skill.json
│   └── notification-skill.json
├── docker-compose.yml    # Multi-service orchestration
└── CLAUDE.md            # This file
```

---

## Architecture Principles

### Layered Architecture (Core API)

The core API follows strict layered architecture:

```
Controller → Service → Repository → Model
```

- **Controllers**: Handle HTTP requests, validation, return JSON responses
- **Services**: Contain all business logic (financial calculations, order processing)
- **Repositories**: Abstract database queries and data access
- **Models**: Eloquent ORM models with relationships

### AI Development Boundaries

- **Business logic** belongs in `core-api/app/Services`
- **Data access** belongs in `core-api/app/Repositories`
- **Controllers** should be thin and delegate to Services
- **Financial operations** must use integer cents (no floating-point)
- **Concurrent operations** must use Redis distributed locking
- **All API responses** follow the format: `{success, data, message}`

---

## Quick Start

### Prerequisites

- Docker Desktop
- PHP 8.2+ and Composer (for local development)
- Node.js 18+ (for frontend development)
- Git

### Start All Services

```bash
# Start infrastructure and all services
docker compose up -d

# Run database migrations
docker compose exec core-api php artisan migrate --force
docker compose exec core-api php artisan db:seed --force

# Services will be available at:
# - Core API: http://localhost:8000
# - Payment Service: http://localhost:8001
# - Notification Service: http://localhost:3002
# - Frontend: http://localhost:3000
```

### Start Individual Services

```bash
# Start infrastructure only
docker compose up -d mysql redis

# Start specific service
docker compose up -d core-api --build
docker compose up -d payment-service --build
docker compose up -d notification-service --build
docker compose up -d frontend --build
```

---

## Service-Specific Setup

### Core API (Laravel 11)

**With Docker:**
```bash
docker compose up -d core-api --build
docker compose exec core-api php artisan migrate
docker compose exec core-api php artisan db:seed
```

**Local Development:**
```bash
cd core-api
cp .env.example .env
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

**Useful Commands:**
```bash
php artisan route:list              # List all API routes
php artisan migrate                  # Run database migrations
php artisan db:seed                  # Seed database with test data
php artisan test                      # Run unit tests
php artisan queue:work                # Start queue worker
php artisan schedule:work             # Start scheduled task runner
php artisan tinker                    # Interactive REPL
```

**Key Background Jobs:**
```bash
php artisan holds:cleanup             # Clean up expired ticket holds (every 5 min)
php artisan sales:generate-reports    # Generate daily sales reports (daily)
php artisan payouts:process-batches   # Process vendor payouts (daily)
php artisan reminders:send-events     # Send event reminders (hourly)
php artisan waitlist:process          # Process waitlist notifications (on ticket release)
```

### Payment Service (Laravel)

**With Docker:**
```bash
docker compose up -d payment-service --build
docker compose exec payment-service php artisan migrate
```

**Local Development:**
```bash
cd payment-service
cp .env.example .env
composer install
php artisan migrate
php artisan serve --port=8001
```

**Environment Variables:**
```env
PAYMENT_SERVICE_SECRET=secure_shared_secret
CORE_API_URL=http://localhost:8000
STRIPE_SUCCESS_RATE=0.9
PAYPAL_SUCCESS_RATE=0.8
```

### Notification Service (Node.js)

**With Docker:**
```bash
docker compose up -d notification-service --build
```

**Local Development:**
```bash
cd notification-service
cp .env.example .env
npm install
npm start
```

**Environment Variables:**
```env
PORT=3002
REDIS_HOST=redis
REDIS_PORT=6379
CORE_API_URL=http://localhost:8000
CORE_API_SECRET=secure_shared_secret
```

### Frontend (Next.js 14)

**With Docker:**
```bash
docker compose up -d frontend --build
```

**Local Development:**
```bash
cd frontend
npm install
npm run dev
```

**Environment Variables:**
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

---

## Database Schema

### Core Tables

- **users** - User accounts (admin, vendor, attendee)
- **vendors** - Vendor profiles and KYC status
- **events** - Event listings
- **ticket_types** - Ticket configurations per event
- **ticket_reservations** - 15-minute ticket holds
- **waitlists** - Ticket waitlist entries
- **orders** - Customer orders
- **order_items** - Order line items
- **payments** - Payment transactions
- **refunds** - Refund records
- **payout_batches** - Batch payout processing
- **payouts** - Individual vendor payouts
- **notifications** - Notification log
- **webhooks** - Vendor webhook registrations
- **disputes** - Dispute records
- **financial_event_logs** - Financial audit trail

### Key Design Decisions

- **Monetary values** stored as integers (cents) to avoid floating-point precision issues
- **Soft deletes** on events and ticket_types to preserve historical data
- **Hard deletes** on ticket_reservations to prevent table bloat
- **Distributed locking** via Redis for concurrent ticket purchases
- **Idempotency keys** on all financial operations

---

## API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected endpoints require Bearer token:
```
Authorization: Bearer {token}
```

### Key Endpoints

**Public:**
- `POST /register` - User registration
- `POST /login` - User login
- `GET /v1/events` - List events
- `GET /v1/events/{id}` - Get event details

**Vendor:**
- `POST /v1/events` - Create event
- `POST /v1/ticket-type` - Create ticket type
- `GET /v1/orders` - View orders
- `POST /v1/webhooks/register` - Register webhook

**Attendee:**
- `POST /v1/orders` - Create order
- `GET /v1/orders` - View order history

**Admin:**
- `POST /v1/vendors/approve` - Approve vendor
- `POST /v1/payout-batches` - Create payout batch
- `POST /v1/disputes/{id}/resolve` - Resolve dispute

**Internal:**
- `POST /v1/webhooks/payment` - Payment callback (shared secret auth)

See `docs/API_DOCUMENTATION.md` for complete API reference.

---

## Testing

### Run Core API Tests
```bash
# Docker
docker compose exec core-api php artisan test

# Local
cd core-api
php artisan test
```

### Key Test Coverage Areas

- **Order Processing**: Concurrent purchase prevention, hold expiry
- **Payout Calculations**: Commission deduction, minimum threshold
- **Inventory Management**: Capacity limits, oversell prevention
- **Idempotency**: Duplicate payment prevention

---

## Development Workflow

### Making Changes to Core API

1. Make code changes in `core-api/`
2. If changing schema: create migration `php artisan make:migration`
3. Run migrations: `php artisan migrate`
4. Write/update tests in `tests/`
5. Run tests: `php artisan test`
6. Rebuild Docker container: `docker compose up -d core-api --build`

### Making Changes to Payment Service

1. Make code changes in `payment-service/`
2. Run migrations if needed
3. Rebuild Docker container: `docker compose up -d payment-service --build`

### Making Changes to Notification Service

1. Make code changes in `notification-service/src/`
2. Rebuild Docker container: `docker compose up -d notification-service --build`

### Making Changes to Frontend

1. Make code changes in `frontend/`
2. Rebuild Docker container: `docker compose up -d frontend --build`

---

## Troubleshooting

### Database Connection Issues
```bash
# Check MySQL container
docker compose ps mysql

# Restart MySQL
docker compose restart mysql

# Reset database
docker compose exec mysql mysql -uroot -proot -e "DROP DATABASE IF EXISTS eventhub_core; CREATE DATABASE eventhub_core;"
docker compose exec core-api php artisan migrate:fresh
```

### Redis Connection Issues
```bash
# Check Redis container
docker compose ps redis

# Test Redis
docker compose exec redis redis-cli ping
# Should return: PONG
```

### Queue Workers Not Processing
```bash
# Start queue worker manually
docker compose exec core-api php artisan queue:work

# Check failed jobs
docker compose exec core-api php artisan queue:failed
```

### Port Conflicts
```bash
# Check what's using ports
netstat -an | grep 8000
netstat -an | grep 3306
netstat -an | grep 6379

# Change ports in docker-compose.yml if needed
```

---

## AI Agent Skills

The project includes AI agent skill definitions in `.agent-skills/`:

- **backend-skill.json** - Core API development boundaries
- **payment-skill.json** - Payment service development boundaries
- **notification-skill.json** - Notification service development boundaries

These skills define service boundaries, key files, and development patterns for AI-assisted development.

---

## Documentation

- **docs/ASSESSMENT.md** - Original technical assessment requirements
- **docs/requirement-analysis.md** - Requirements breakdown and user stories
- **docs/system_architecture.md** - System design, ERD, and communication patterns
- **docs/technical_decision_log.md** - Architecture decisions and trade-offs
- **docs/setup-instructions.md** - Detailed setup guide
- **docs/API_DOCUMENTATION.md** - Complete API endpoint reference
- **docs/development_plan.md** - Development roadmap and team delegation plan
- **docs/schema-rationale.md** - Database design rationale and indexing strategy

---

## Important Notes

### Financial Operations
- All monetary values use integers (cents)
- Financial operations are idempotent
- Audit trails are immutable
- Distributed locking prevents race conditions

### Security
- Inter-service communication uses shared secrets
- API endpoints use Bearer token authentication
- Role-based access control (admin, vendor, attendee)
- Input validation on all endpoints

### Performance
- Redis used for caching and distributed locking
- Database indexes on foreign keys and query patterns
- Queue workers for background processing
- Soft deletes preserve data integrity

---

## Support and Resources

For detailed information on specific aspects:
- Setup: See `docs/setup-instructions.md`
- API: See `docs/API_DOCUMENTATION.md`
- Architecture: See `docs/system_architecture.md`
- Development: See `docs/development_plan.md`

For issues or questions:
1. Check relevant documentation in `docs/`
2. Review troubleshooting section above
3. Check service logs: `docker compose logs [service-name]`

