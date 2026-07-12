# EventHub (Monorepo)

This repository contains the EventHub platform in a multi-service monorepo layout:

- **core-api**: Laravel 11 core marketplace API
- **frontend**: Next.js frontend (App Router)
- **payment-service**: Payment simulator microservice
- **notification-service**: Notification worker microservice

## Local dependencies
- Redis (used by both concurrency locking and queues)

## Start Redis
```bash
docker compose up -d redis
```

Then scaffold/run each service as described in `CLAUDE.md`.

## Walkthrough video link

https://drive.google.com/file/d/1F87qYHkBYFLoxlL08QAPWSJu9j_r76-F/view?usp=drive_link

