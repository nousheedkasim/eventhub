# TODO - EventHub Monorepo Scaffolding

## Step 1: Repo inspection
- [x] Read CLAUDE.md and docs to understand target monorepo structure
- [x] Verify current filesystem contents (directories: core-api, frontend, payment-service, notification-service)

## Step 2: Scaffold missing folders
- [x] Create `core-api/` skeleton (Laravel 11) matching CLAUDE.md tree
- [x] Create `frontend/` skeleton (Next.js) matching CLAUDE.md tree
- [ ] Create `payment-service/` skeleton (Laravel/Lumen) matching CLAUDE.md tree
- [ ] Create `notification-service/` skeleton matching CLAUDE.md tree

## Step 3: Add minimal runnable configs
- [x] Add root `docker-compose.yml` (only if missing)
- [ ] Add minimal `README.md` if missing

## Step 4: Validate
- [ ] Run basic checks (directory existence, no syntax errors in configs)

