# CLAUDE.md (EventHub)

This file documents the monorepo boundaries and the expected directory layout for Claude/AI agents.

## Monorepo layout

- `core-api/` - Laravel 11 (main backend)
- `frontend/` - Next.js App Router
- `payment-service/` - Payment simulator microservice
- `notification-service/` - Notification worker microservice

## AI boundaries

- Business logic belongs in `core-api/app/Services`.
- Data access belongs in `core-api/app/Repositories`.
- Controllers should be thin and call Services.

## How to run core-api locally

### With Docker (recommended)
1. Start dependencies:
   - `docker compose up -d redis mysql`
2. Build and start core-api:
   - `docker compose up -d core-api --build`
3. API should be reachable at:
   - `http://localhost:8000`

### Without Docker
You must provide DB credentials in `core-api/.env` matching your local MySQL host/port.

## Useful core-api artisan commands

- `php artisan route:list`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan test`

