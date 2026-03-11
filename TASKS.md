# TASKS – Prďolotoč

Goal: Provide small (≤ ~4h) tasks with clear dependencies and "Done when" definitions, so an
autonomous coding agent (Codex) can implement the project step-by-step.

Tasks are grouped roughly by domain (backend, frontend, infra, glue). IDs are stable and
should be referenced in branches/PRs.

---

## Backend – Symfony API & Domain

- [x] T1 – Bootstrap Symfony Backend Skeleton (Docker-only)
- **Depends on:** –
- **Description:**
  - Create `/backend` Symfony application (latest LTS, PHP 8.3+), API-style skeleton.
  - Configure basic project structure for JSON-only API (no Twig).
  - Ensure the app can be run **only inside Docker** (no host PHP required).
- **Includes:**
  - New Symfony project under `/backend`.
  - Packages for Doctrine and PostgreSQL driver installed.
  - Basic `.env` with DB connection placeholders (host/user/password/db), using `db` as host for Docker.
- **Done when:**
  - Backend boots successfully via a container command, e.g. `docker compose run --rm php-cli php bin/console about` from repo root.
  - App bootstrap is committed and ready for further tasks.

- [x] T2 – Configure Doctrine + PostgreSQL Connection (Containerised)
- **Depends on:** T1
- **Description:**
  - Wire Doctrine ORM to PostgreSQL according to `ARCHITECTURE.md`.
- **Includes:**
  - DB connection configured via `.env` / `.env.local` (host `db` for Docker, optional `localhost` override if ever needed).
  - Doctrine migrations bundle installed and configured.
- **Done when:**
  - `docker compose run --rm php-cli php bin/console doctrine:migrations:diff` runs and sees no errors on empty schema.
  - Connection from the `php-cli` container to the `db` container works in dev.

- [x] T3 – Health Endpoint Implementation
- **Depends on:** T1, T2 (DB optional but preferred)
- **Description:**
  - Implement `GET /api/health` endpoint.
- **Includes:**
  - Route definition.
  - Controller returning `{ "status": "ok" }` JSON.
  - Basic functional test executed from inside containers.
- **Done when:**
  - With the stack up (`make up`), `GET /api/health` via nginx returns HTTP 200 with JSON body `{ "status": "ok" }` in local dev.
  - Corresponding test passes when run via `docker compose run --rm php-cli ./vendor/bin/phpunit`.

- [x] T4 – SpinConfig Entity + Migration — PR pending
- **Depends on:** T2
- **Description:**
  - Implement `SpinConfig` Doctrine entity and DB migration according to `ARCHITECTURE.md`.
- **Includes:**
  - Fields: `id`, `name`, `slug`, `options`, `removeAfterPick`, `visualMode`, `createdAt`.
  - DB-level constraints (unique index on `slug`).
  - Migration created and runnable via Docker (`docker compose run --rm php-cli php bin/console doctrine:migrations:diff`).
- **Done when:**
  - Migration runs successfully against dev database via `docker compose exec php-fpm php bin/console doctrine:migrations:migrate`.
  - Table structure matches the domain model in `ARCHITECTURE.md`.

- [x] T5 – SpinConfig Slug Generation Service — PR #1 ✅
- **Depends on:** T4
- **Description:**
  - Implement service responsible for generating unique, URL-safe slugs.
- **Includes:**
  - Kebab-case conversion from `name`.
  - Collision handling (append short random/unique suffix).
  - Unit tests for core cases.
- **Done when:**
  - Service can generate slugs for typical names and for collisions.
  - Tests cover at least: basic name, duplicate name, non-ASCII characters.
  - Test suite runs successfully via `docker compose run --rm php-cli ./vendor/bin/phpunit`.

- [x] T6 – Global JSON Error Response Helper — PR #2 ✅
- **Depends on:** T1
- **Description:**
  - Implement reusable helper / listener to shape error responses according to `ARCHITECTURE.md`.
- **Includes:**
  - Consistent JSON format for 400, 404, 500 errors:
    - `error`, `message`, optional `details` map.
  - Integration with Symfony validation errors for `validation_failed`.
- **Done when:**
  - Manual 404 or validation errors return JSON structure matching `ARCHITECTURE.md`.
  - At least one test asserts JSON error shape, executed via `docker compose run --rm php-cli ./vendor/bin/phpunit`.

- [x] T7 – POST /api/spin-configs Endpoint — PR #3 ✅
- **Depends on:** T4, T5, T6
- **Description:**
  - Implement creation endpoint for `SpinConfig`.
- **Includes:**
  - Input DTO / request object with validation rules.
  - Use of slug generator from T5.
  - Persistence via Doctrine.
  - Normalisation of `options` (trim, drop empties).
  - Defaulting of `removeAfterPick` and `visualMode` per `ARCHITECTURE.md`.
- **Done when:**
  - With stack up (`make up`), valid payload sent through nginx (e.g. `POST http://localhost:8080/api/spin-configs`) returns 201 with JSON body matching `SpinConfig` schema.
  - Invalid payload returns 400 with `error: "validation_failed"` and proper `details`.
  - Basic functional tests (happy path + at least two invalid cases) pass when run via `docker compose run --rm php-cli ./vendor/bin/phpunit`.

- [x] T8 – GET /api/spin-configs/{slugOrId} Endpoint — PR #4 ✅
- **Depends on:** T4, T6
- **Description:**
  - Implement retrieval endpoint for `SpinConfig`.
- **Includes:**
  - Lookup by `slug` primarily (optionally by `id`).
  - Return 404 with consistent JSON error on missing config.
- **Done when:**
  - With stack up (`make up`), existing config can be retrieved by slug through nginx (`GET /api/spin-configs/{slug}`).
  - Unknown slug returns 404 with `error: "not_found"` and message as per `ARCHITECTURE.md`.
  - Functional tests cover success + 404 and run via `docker compose run --rm php-cli ./vendor/bin/phpunit`.

- [x] T9 – Backend Test & QA Wiring (Containerised) — PR #5 ✅
- **Depends on:** T3, T7, T8
- **Description:**
  - Ensure backend has a minimal but reliable test suite and QA commands, all runnable via Docker.
- **Includes:**
  - PHPUnit configuration for functional tests.
  - At least one test class per endpoint.
  - Composer scripts or Makefile entries that delegate to `docker compose run` / `docker compose exec`.
- **Done when:**
  - `docker compose run --rm php-cli composer test` (or equivalent) runs and passes.
  - A single command exists to run backend tests from repo root (e.g. `make test-backend`) and it uses containers only.

---

## Frontend – Next.js SPA

- [x] T10 – Bootstrap Next.js + TypeScript App — PR #6 ✅
- **Depends on:** – (can run in parallel with backend tasks)
- **Description:**
  - Create `/frontend` Next.js app using TypeScript.
- **Includes:**
  - Basic Next.js project structure with ESLint & TypeScript.
  - Scripts for `dev`, `build`, `start`.
- **Done when:**
  - `cd frontend && npm run dev` starts a working default Next app.

- [x] T11 – Define API Client Types & Configuration — PR #7 ✅
- **Depends on:** T3, T7, T8, T10
- **Description:**
  - Create a small typed API client layer for calling backend endpoints.
- **Includes:**
  - TypeScript interfaces for `SpinConfig` and error responses (aligned with `ARCHITECTURE.md`).
  - Functions: `createSpinConfig(payload)` and `getSpinConfig(slug)`.
  - Base URL configuration that works in dev with Docker/nginx and in local no-Docker.
- **Done when:**
  - API client functions compile and have basic unit tests mocking HTTP.

- [x] T12 – Builder Page (`/`) – Basic Form & Local State — PR #8 ✅
- **Depends on:** T10
- **Description:**
  - Implement the builder view UI and local state (without wiring to API yet).
- **Includes:**
  - Form fields: name, options (textarea), removeAfterPick, visualMode.
  - Local validation for obviously invalid input (empty name/options).
  - Basic layout and styling good enough for MVP.
- **Done when:**
  - User can fill out the form and local validation errors are shown on submit.

- [x] T13 – Builder Page – API Integration & Navigation — PR #9 ✅
- **Depends on:** T11, T12
- **Description:**
  - Wire builder form to `POST /api/spin-configs` and navigate on success.
- **Includes:**
  - On submit, call `createSpinConfig` from T11.
  - On success, redirect to `/s/[slug]` using the returned slug.
  - On 400 validation error, map `details` to form fields.
  - Display generic error message on 5xx/network issues.
- **Done when:**
  - Creating a config via the UI results in navigation to `/s/[slug]`.
  - Intentional invalid input shows form errors coming from backend.

- [x] T14 – Spin / Share Page (`/s/[slug]`) – Data Loading & Error States
- **Depends on:** T11, T10
- **Description:**
  - Implement `/s/[slug]` page that loads config and handles loading/error states.
- **Includes:**
  - Fetch `SpinConfig` via `getSpinConfig(slug)`.
  - Show loading indicator while waiting.
  - On 404, show user-friendly "config not found" view.
  - On 5xx/network error, show generic error.
- **Done when:**
  - Navigating to `/s/[existingSlug]` shows basic config info.
  - Navigating to `/s/nonexistent` shows not-found state.

- [x] T15 – Spin / Share Page – Spin Logic & Visual Modes — PR #10 ✅
- **Depends on:** T14
- **Description:**
  - Implement spinning behaviour and basic visual modes.
- **Includes:**
  - Local state of remaining options respecting `removeAfterPick`.
  - Button to start/stop a spin and highlight the picked result.
  - Three visual modes:
    - `classic`: default speed & colours.
    - `slow`: slower animation.
    - `chaotic`: faster / randomised colour transitions.
- **Done when:**
  - User can repeatedly spin with expected behaviour for each visual mode.
  - `removeAfterPick` works as specified.

- [ ] T16 – Frontend Testing & Lint Wiring
- **Depends on:** T11, T12, T13, T14, T15
- **Description:**
  - Add tests and linters for frontend.
- **Includes:**
  - React Testing Library tests for:
    - Builder form value handling and validation.
    - Basic spin logic (pure functions where possible).
  - ESLint and TypeScript build checks wired into npm scripts.
- **Done when:**
  - `cd frontend && npm test` (or equivalent) runs and passes.
  - A single command exists to run frontend tests from repo root (used by `make test`).

---

## Infrastructure – Docker, Nginx, Makefile

- [ ] T20 – Docker Compose Skeleton
- **Depends on:** T1, T10
- **Description:**
  - Create `docker-compose.yml` with base services and shared network.
- **Includes:**
  - Services defined: `db`, `php-fpm`, `php-cli`, `frontend`, `nginx` (even if images not fully wired yet).
  - Shared network for all services.
- **Done when:**
  - `docker compose config` succeeds and shows expected services.

- [ ] T21 – PostgreSQL Service Configuration
- **Depends on:** T20
- **Description:**
  - Configure `db` service for PostgreSQL.
- **Includes:**
  - Image, volumes, basic env vars (user/password/db name).
  - Expose port if useful for local debugging.
- **Done when:**
  - `docker compose up db` starts PostgreSQL and is reachable from host.

- [ ] T22 – Backend Service & PHP-FPM Wiring
- **Depends on:** T20, T1, T2
- **Description:**
  - Configure `php-fpm` and `php-cli` services running Symfony via PHP-FPM and CLI.
- **Includes:**
  - `Dockerfile.php` defining a PHP 8.3 image with both FPM and CLI, plus Composer installed inside the image.
  - `php-fpm` service using this image and mounting `/backend` source into the container.
  - `php-cli` service using the same image for one-off commands via `docker compose run --rm php-cli ...`.
  - Environment variables wired from `docker-compose.yml` to backend.
- **Done when:**
  - `docker compose up php-fpm` runs PHP-FPM without crashing.
  - `docker compose run --rm php-cli php bin/console about` works from repo root.

- [ ] T23 – Frontend Service Configuration
- **Depends on:** T20, T10
- **Description:**
  - Configure `frontend` service running Next.js dev server for local dev.
- **Includes:**
  - `Dockerfile.frontend` based on Node LTS with dependencies installed (Yarn/NPM).
  - Volume mount of `/frontend` for fast local iteration.
  - Command to run `npm run dev` (or `yarn dev`) inside the container.
- **Done when:**
  - `docker compose up frontend` starts Next.js dev server accessible inside the Docker network (and via nginx once T24 is done).

- [ ] T24 – Nginx Reverse Proxy & Routing
- **Depends on:** T22, T23
- **Description:**
  - Configure `nginx` service to route `/api` to backend and `/` to frontend.
- **Includes:**
  - `Dockerfile.nginx` using an nginx base image.
  - Nginx config file(s) under `/infra/nginx` with:
    - `/api` proxied to `php-fpm` service (Symfony backend).
    - `/` proxied to `frontend` service in dev (Next dev server).
  - (Optional later) Static hosting of built frontend for production.
- **Done when:**
  - `docker compose up nginx php-fpm frontend` allows browsing app via single port (e.g. `http://localhost:8080`).
  - `GET /api/health` routed through nginx works.

- [ ] T25 – Logs Directory & Wiring
- **Depends on:** T20, T24
- **Description:**
  - Ensure logs are collected under `/logs` and git-ignored.
- **Includes:**
  - `logs/` directory created.
  - Volume mappings from nginx/backend/frontend containers to `./logs/...`.
- **Done when:**
  - Running stack writes logs into `./logs` (e.g. `logs/nginx/access.log`).

- [ ] T26 – Top-Level Makefile + Local Dev Workflow
- **Depends on:** T21, T22, T23, T24, T25, T9, T16
- **Description:**
  - Implement Makefile commands described in `ARCHITECTURE.md` and define the canonical local dev workflow.
- **Includes:**
  - Targets: `up`, `down`, `logs`, `backend-shell`, `frontend-shell`, `db-shell`, `qa`, `test`, `lint`, `build`.
  - All targets internally use `docker compose` (no direct `php`, `composer`, `npm` on host).
  - `qa` runs backend + frontend tests and linters via containers.
  - Document a short **"How to run locally"** section in `README.md` or `MANUAL-QA.md` with at least:
    - `make up` → start full stack (db + php-fpm + php-cli + frontend + nginx).
    - `make qa` → run tests + linters via containers.
- **Done when:**
  - `make up` starts full stack (db + php-fpm + php-cli + frontend + nginx) and ensures `logs/` exists.
  - `make qa` runs all checks and passes on a fresh clone using only Docker commands under the hood.

---

## Glue / Polish

- [ ] T30 – End-to-End Manual Verification Script (Docs)
- **Depends on:** T13, T15, T24, T26
- **Description:**
  - Add a short section (e.g. in `README.md` or a new `MANUAL-QA.md`) describing how to run the whole stack and manually verify core flows.
- **Includes:**
  - Steps to run `make up`.
  - URL to open.
  - Steps to create a config and spin.
  - Expected behaviour for `removeAfterPick` and visual modes.
- **Done when:**
  - Document exists and is easy to follow on a clean environment.

- [ ] T31 – Golden JSON Examples
- **Depends on:** T7, T8
- **Description:**
  - Populate "Golden Examples" section in `AGENTS.md`.
- **Includes:**
  - Example request + response for `POST /api/spin-configs`.
  - Example response for `GET /api/spin-configs/{slug}`.
- **Done when:**
  - `AGENTS.md` contains realistic, copy-pastable JSON examples consistent with actual implementation.

---

## Suggested Execution Order for Coding Agent

1. **Backend Foundations:** T1 → T2 → T3 → T4 → T5 → T6 → T7 → T8 → T9.
2. **Frontend Foundations:** T10 → T12 → T11 → T13 → T14 → T15 → T16.
3. **Infrastructure:** T20 → T21 → T22 → T23 → T24 → T25 → T26.
4. **Glue & Docs:** T30 → T31.

Each PR should ideally cover 1–2 tasks from the same group, keeping changes focused and
traceable by task ID.
