# Setup Instructions (EventHub)

Complete A-to-Z setup guide for the EventHub multi-service platform.

## 1) Prerequisites

- **Docker Desktop** (for MySQL + Redis + service containers)
- **PHP 8.2+ and Composer** (for local development if running outside Docker)
- **Node.js 18+** (for Next.js frontend - Day 4)
- **Git** (for cloning the repository)

## 2) Clone the Repository

```bash
git clone <repository-url>
cd eventhub
```

## 3) Start Infrastructure Services

Start MySQL and Redis containers:

```bash
docker compose up -d mysql redis
```

Verify services are running:
```bash
docker compose ps
```

Expected output should show `eventhub_mysql` and `eventhub_redis` as "Up".

## 4) Start Core API

### Option A: Docker (Recommended)

```bash
docker compose up -d core-api --build
```

The Core API will be available at: `http://localhost:8000`

**Note:** Environment variables are automatically configured by docker-compose.yml. No manual .env setup needed for Docker.

### Option B: Local Development

If running locally without Docker:

1. Copy environment file:
```bash
cd core-api
cp .env.example .env
```

2. Configure database in `.env` (uncomment MySQL section):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eventhub_core
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
PAYMENT_SERVICE_URL=http://localhost:8001
PAYMENT_SERVICE_SECRET=secure_shared_secret
```

3. Install dependencies:
```bash
composer install
```

4. Run migrations:
```bash
php artisan migrate
```

5. Start server:
```bash
php artisan serve
```

## 5) Start Payment Service

### Option A: Docker (Recommended)

```bash
docker compose up -d payment-service --build
```

The Payment Service will be available at: `http://localhost:8001`

**Note:** Environment variables are automatically configured by docker-compose.yml. No manual .env setup needed for Docker.

### Option B: Local Development

1. Copy environment file:
```bash
cd payment-service
cp .env.example .env
```

2. Configure in `.env` (add payment service variables):
```env
APP_ENV=local
PAYMENT_SERVICE_SECRET=secure_shared_secret
CORE_API_URL=http://localhost:8000
STRIPE_SUCCESS_RATE=0.9
PAYPAL_SUCCESS_RATE=0.8
REDIS_HOST=127.0.0.1
```

3. Install dependencies:
```bash
composer install
```

4. Run migrations:
```bash
php artisan migrate
```

5. Start server:
```bash
php artisan serve --port=8001
```

## 6) Database Setup

Run migrations and seeders inside the Core API:

```bash
# Docker
docker compose exec core-api php artisan migrate --force
docker compose exec core-api php artisan db:seed --force

# Local
cd core-api
php artisan migrate --force
php artisan db:seed --force
```

## 7) Environment Variables Reference

### Core API (.env)
```env
DB_HOST=mysql                    # Docker: mysql, Local: 127.0.0.1
DB_PORT=3306
DB_DATABASE=eventhub_core
DB_USERNAME=root
DB_PASSWORD=root
REDIS_HOST=redis                 # Docker: redis, Local: 127.0.0.1
REDIS_PORT=6379
PAYMENT_SERVICE_URL=http://localhost:8001
PAYMENT_SERVICE_SECRET=secure_shared_secret
```

### Payment Service (.env)
```env
PAYMENT_SERVICE_SECRET=secure_shared_secret
CORE_API_URL=http://localhost:8000
STRIPE_SUCCESS_RATE=0.9
PAYPAL_SUCCESS_RATE=0.8
```

### Docker Compose (docker-compose.yml)
These are set automatically in the Docker environment:
- Core API: `DB_HOST=mysql`, `REDIS_HOST=redis`
- Payment Service: `CORE_API_URL=http://core-api:8000`

## 8) Verify Setup

### Test Core API Health
```bash
curl http://localhost:8000/api/hello
```

Expected response:
```json
{"message":"Hello, World!"}
```

### Test Payment Service Health
```bash
curl http://localhost:8001/api/payments \
  -X POST \
  -H "Authorization: Bearer secure_shared_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id":1,
    "amount":100,
    "currency":"USD",
    "gateway":"stripe",
    "event_date":"2026-08-01T10:00:00Z"
  }'
```

Expected response:
```json
{
  "success": true,
  "status": "pending",
  "payment_reference": "pay_stripe_..."
}
```

### Test Database Connection
```bash
# Docker
docker compose exec core-api php artisan tinker

# Local
cd core-api
php artisan tinker
```

In tinker:
```php
DB::connection()->getPdo();
// Should return: PDO {...}
```

### Test Redis Connection
```bash
# Docker
docker compose exec core-api php artisan tinker

# In tinker
Cache::put('test', 'value', 60);
Cache::get('test');
// Should return: "value"
```

## 9) Run Unit Tests

```bash
# Docker
docker compose exec core-api php artisan test

# Local
cd core-api
php artisan test
```

Expected output: All tests passing (9 tests, 27 assertions)

## 10) Start Queue Workers (Required for Webhooks)

The Payment Service uses queues for webhook callbacks. Start the queue worker:

```bash
# Docker
docker compose exec payment-service php artisan queue:work

# Local (in separate terminal)
cd payment-service
php artisan queue:work
```

## 11) Start Scheduled Tasks (Optional)

Core API has scheduled tasks for expired hold cleanup. Start the scheduler:

```bash
# Docker
docker compose exec core-api php artisan schedule:work

# Local (in separate terminal)
cd core-api
php artisan schedule:work
```

Or use the cron-style scheduler (Linux/Mac):
```bash
# Add to crontab
* * * * * cd /path-to-eventhub/core-api && php artisan schedule:run >> /dev/null 2>&1
```

## 12) Start Notification Service (Day 4)

### Option A: Docker (Recommended)

```bash
docker compose up -d notification-service --build
```

The Notification Service will be available at: `http://localhost:3002`

**Note:** Environment variables are automatically configured by docker-compose.yml. No manual .env setup needed for Docker.

### Option B: Local Development

1. Copy environment file:
```bash
cd notification-service
cp .env.example .env
```

2. Configure in `.env` (update Redis host for local):
```env
PORT=3002
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CORE_API_URL=http://localhost:8000
CORE_API_SECRET=secure_shared_secret
WEBHOOK_TIMEOUT_MS=5000
WEBHOOK_MAX_RETRIES=3
WEBHOOK_RETRY_DELAY_MS=1000
LOG_LEVEL=info
```

3. Install dependencies:
```bash
npm install
```

4. Start server:
```bash
npm start
```

## 13) Start Frontend (Day 4)

### Option A: Docker (Recommended)

```bash
docker compose up -d frontend --build
```

The Frontend will be available at: `http://localhost:3000`

**Note:** Environment variables are automatically configured by docker-compose.yml. No manual .env setup needed for Docker.

### Option B: Local Development

1. Copy environment file:
```bash
cd frontend
cp .env.example .env
```

2. Install dependencies:
```bash
npm install
```

3. Start development server:
```bash
npm run dev
```

The Frontend will be available at: `http://localhost:3000`

## 14) Service URLs Summary

| Service | URL | Purpose |
|---------|-----|---------|
| Core API | http://localhost:8000 | Main marketplace API |
| Payment Service | http://localhost:8001 | Payment processing |
| Notification Service | http://localhost:3002 | Email & webhook notifications |
| Frontend | http://localhost:3000 | Next.js web application |
| MySQL | localhost:3306 | Database |
| Redis | localhost:6379 | Cache & Queues |

## 15) Common Issues & Troubleshooting

### Issue: "Connection refused" to MySQL
**Solution:**
- Ensure MySQL container is running: `docker compose ps mysql`
- Check if port 3306 is available: `netstat -an | grep 3306`
- Verify DB_HOST in .env matches your setup

### Issue: Redis connection failed
**Solution:**
- Ensure Redis container is running: `docker compose ps redis`
- Test Redis directly: `docker compose exec redis redis-cli ping`
- Should return: `PONG`

### Issue: Payment service can't reach core API
**Solution:**
- Docker: Ensure both services are on the same network
- Local: Check CORE_API_URL in payment service .env
- Test connectivity: `curl http://localhost:8000/api/hello` from payment service container

### Issue: Distributed locking not working
**Solution:**
- Verify Redis is running and accessible
- Check Redis configuration in Core API .env
- Test lock acquisition in tinker: `Cache::lock('test', 10)->get()`

### Issue: Webhooks not firing
**Solution:**
- Ensure queue worker is running
- Check payment service logs: `docker compose logs payment-service`
- Verify CORE_API_URL is correct in payment service

### Issue: Migrations fail
**Solution:**
- Drop and recreate database:
```bash
docker compose exec mysql mysql -uroot -proot -e "DROP DATABASE IF EXISTS eventhub_core; CREATE DATABASE eventhub_core;"
docker compose exec core-api php artisan migrate:fresh
```

### Issue: Port conflicts (8000, 8001, 3306, 6379)
**Solution:**
- Check what's using the ports: `netstat -an | grep <port>`
- Stop conflicting services or change ports in docker-compose.yml

## 16) Development Workflow

### Making Changes to Core API
```bash
# After code changes, rebuild container
docker compose up -d core-api --build

# Or for local development, just restart server
php artisan serve
```

### Making Changes to Payment Service
```bash
# Rebuild container
docker compose up -d payment-service --build

# Or restart local server
php artisan serve --port=8001
```

### Making Changes to Notification Service
```bash
# Rebuild container
docker compose up -d notification-service --build

# Or restart local server
cd notification-service
npm start
```

### Making Changes to Frontend
```bash
# Rebuild container
docker compose up -d frontend --build

# Or restart local dev server
cd frontend
npm run dev
```

### Running Tests After Changes
```bash
docker compose exec core-api php artisan test
```

### Viewing Logs
```bash
# Core API logs
docker compose logs -f core-api

# Payment service logs
docker compose logs -f payment-service

# Notification service logs
docker compose logs -f notification-service

# Frontend logs
docker compose logs -f frontend

# All services
docker compose logs -f
```

## 17) Stopping Services

```bash
# Stop all services
docker compose down

# Stop specific service
docker compose stop core-api

# Stop and remove volumes (cleans database)
docker compose down -v
```

## 18) Production Deployment Notes

For production deployment:
- Change `APP_ENV=production` in all .env files
- Set strong `APP_KEY` values: `php artisan key:generate`
- Use real database credentials (not root/root)
- Change `PAYMENT_SERVICE_SECRET` to a cryptographically secure random string
- Configure proper SSL/TLS certificates
- Set up proper queue workers with process managers (Supervisor)
- Configure log rotation
- Set up monitoring and alerting
- Use environment-specific configuration files

