# Setup Instructions (EventHub)

## 1) Prerequisites

- Docker Desktop (for MySQL + Redis)
- PHP 8.2+ and Composer (for local development if you run outside Docker)
- Node.js (only for the Next.js frontend)

## 2) Start infrastructure

```bash
docker compose up -d mysql redis
```

## 3) Start the core API

Recommended (Docker):

```bash
docker compose up -d core-api --build
```

Then open:

- Core API: `http://localhost:8000`

## 4) Database migrations + seeders

Run these inside the `core-api` container (recommended):

```bash
docker compose exec core-api php artisan migrate --force
docker compose exec core-api php artisan db:seed --force
```

## 5) Common environment variables

Inside `core-api`, Docker expects:

- `DB_HOST=mysql`
- `DB_PORT=3306`
- `DB_DATABASE=eventhub_core`
- `DB_USERNAME=root`
- `DB_PASSWORD=root`
- `REDIS_HOST=redis`

When running on Windows host without Docker, `DB_HOST` must point to your local MySQL host (usually `127.0.0.1`).

