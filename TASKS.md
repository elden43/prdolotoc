# TASKS – Prďolotoč

Goal: Provide small (≤ ~4h) tasks with clear dependencies and "Done when" definitions, so an
autonomous coding agent (Codex) can implement the project step-by-step.

Tasks are grouped roughly by domain (backend, frontend, infra, glue). IDs are stable and
should be referenced in branches/PRs.

---

## Backend – Symfony API & Domain

### T1 – Bootstrap Symfony Backend Skeleton
- **Depends on:** –
- **Description:**
  - Create `/backend` Symfony application (latest LTS, PHP 8.3+), API-style skeleton.
  - Configure basic project structure for JSON-only API (no Twig).
- **Includes:**
  - New Symfony project under `/backend`.
  - Packages for Doctrine and PostgreSQL driver installed.
  - Basic `.env` with DB connection placeholders (host/user/password/db).
- **Done when:**
  - `cd backend && symfony console about` (or equivalent) runs successfully.
  - App bootstrap is committed and ready for further tasks.

### T2 – Configure Doctrine + PostgreSQL Connection
- **Depends on:** T1
- **Description:**
  - Wire Doctrine ORM to PostgreSQL according to `ARCHITECTURE.md`.
- **Includes:**
  - DB connection configured via `.env` / `.env.local` (host `db` for Docker, `localhost` for local).
  - Doctrine migrations bundle installed and configured.
- **Done when:**
  - `cd backend && php bin/console doctrine:migrations:diff` runs and sees no errors on empty schema.
  - Local manual connection to DB (using configured params) works in dev.

### T3 – Health Endpoint Implementation
- **Depends on:** T1, T2 (DB optional but preferred)
- **Description:**
  - Implement `GET /api/health` endpoint.
- **Includes:**
  - Route definition.
  - Controller returning `{ "status": "ok" }` JSON.
  - Basic functional test.
- **Done when:**
  - `GET /api/health` returns HTTP 200 with JSON body `{ "status": "ok" }` in local dev.
  - Corresponding test passes.

### T4 – SpinConfig Entity + Migration
- **Depends on:** T2
- **Description:**
  - Implement `SpinConfig` Doctrine entity and DB migration according to `ARCHITECTURE.md`.
- **Includes:**
  - Fields: `id`, `name`, `slug`, `options`, `removeAfterPick`, `visualMode`, `createdAt`.
  - DB-level constraints (unique index on `slug`).
  - Migration created and runnable.
- **Done when:**
  - Migration runs successfully against dev database.
  - Table structure matches the domain model in `ARCHITECTURE.md`.

### T5 – SpinConfig Slug Generation Service
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

### T6 – Global JSON Error Response Helper
- **Depends on:** T1
- **Description:**
  - Implement reusable helper / listener to shape error responses according to `ARCHITECTURE.md`.
- **Includes:**
  - Consistent JSON format for 400, 404, 500 errors:
    - `error`, `message`, optional `details` map.
  - Integration with Symfony validation errors for `validation_failed`.
- **Done when:**
  - Manual 404 or validation errors return JSON structure matching `ARCHITECTURE.md`.
  - At least one test asserts JSON error shape.

### T7 – POST /api/spin-configs Endpoint
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
  - Valid payload returns 201 with JSON body matching `SpinConfig` schema.
  - Invalid payload returns 400 with `error: "validation_failed"` and proper `details`.
  - Basic functional tests (happy path + at least two invalid cases) pass.

### T8 – GET /api/spin-configs/{slugOrId} Endpoint
- **Depends on:** T4, T6
- **Description:**
  - Implement retrieval endpoint for `SpinConfig`.
- **Includes:**
  - Lookup by `slug` primarily (optionally by `id`).
  - Return 404 with consistent JSON error on missing config.
- **Done when:**
  - Existing config can be retrieved by slug.
  - Unknown slug returns 404 with `error: "not_found"` and message as per `ARCHITECTURE.md`.
  - Functional tests cover success + 404.

### T9 – Backend Test & QA Wiring
- **Depends on:** T3, T7, T8
- **Description:**
  - Ensure backend has a minimal but reliable test suite and QA commands.
- **Includes:**
  - PHPUnit configuration for functional tests.
  - At least one test class per endpoint.
  - Composer scripts or Makefile entries to run backend tests and code style (lint).
- **Done when:**
  - `cd backend && composer test` (or equivalent) runs and passes.
  - A single command exists to run backend tests from repo root (used by `make test` later).

---

## Frontend – Next.js SPA

### T10 – Bootstrap Next.js + TypeScript App
- **Depends on:** – (can run in parallel with backend tasks)
- **Description:**
  - Create `/frontend` Next.js app using TypeScript.
- **Includes:**
  - Basic Next.js project structure with ESLint & TypeScript.
  - Scripts for `dev`, `build`, `start`.
- **Done when:**
  - `cd frontend && npm run dev` starts a working default Next app.

### T11 – Define API Client Types & Configuration
- **Depends on:** T3, T7, T8, T10
- **Description:**
  - Create a small typed API client layer for calling backend endpoints.
- **Includes:**
  - TypeScript interfaces for `SpinConfig` and error responses (aligned with `ARCHITECTURE.md`).
  - Functions: `createSpinConfig(payload)` and `getSpinConfig(slug)`.
  - Base URL configuration that works in dev with Docker/nginx and in local no-Docker.
- **Done when:**
  - API client functions compile and have basic unit tests mocking HTTP.

### T12 – Builder Page (`/`) – Basic Form & Local State
- **Depends on:** T10
- **Description:**
  - Implement the builder view UI and local state (without wiring to API yet).
- **Includes:**
  - Form fields: name, options (textarea), removeAfterPick, visualMode.
  - Local validation for obviously invalid input (empty name/options).
  - Basic layout and styling good enough for MVP.
- **Done when:**
  - User can fill out the form and local validation errors are shown on submit.

### T13 – Builder Page – API Integration & Navigation
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

### T14 – Spin / Share Page (`/s/[slug]`) – Data Loading & Error States
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

### T15 – Spin / Share Page – Spin Logic & Visual Modes
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

### T16 – Frontend Testing & Lint Wiring
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

### T20 – Docker Compose Skeleton
- **Depends on:** T1, T10
- **Description:**
  - Create `docker-compose.yml` with base services and shared network.
- **Includes:**
  - Services defined: `db`, `backend`, `frontend`, `nginx` (even if images not fully wired yet).
  - Shared network for all services.
- **Done when:**
  - `docker-compose config` succeeds and shows expected services.

### T21 – PostgreSQL Service Configuration
- **Depends on:** T20
- **Description:**
  - Configure `db` service for PostgreSQL.
- **Includes:**
  - Image, volumes, basic env vars (user/password/db name).
  - Expose port if useful for local debugging.
- **Done when:**
  - `docker-compose up db` starts PostgreSQL and is reachable from host.

### T22 – Backend Service & PHP-FPM Wiring
- **Depends on:** T20, T1, T2
- **Description:**
  - Configure `backend` service running Symfony via PHP-FPM.
- **Includes:**
  - Dockerfile or image reference for PHP 8.3 + needed extensions.
  - Volume mount of `/backend` source into container.
  - Environment variables wired from `docker-compose.yml` to backend.
- **Done when:**
  - `docker-compose up backend` runs PHP-FPM without crashing.

### T23 – Frontend Service Configuration
- **Depends on:** T20, T10
- **Description:**
  - Configure `frontend` service running Next.js dev server for local dev.
- **Includes:**
  - Node image with dependencies installed.
  - Volume mount of `/frontend`.
  - Command to run `npm run dev`.
- **Done when:**
  - `docker-compose up frontend` starts Next.js dev server accessible inside network.

### T24 – Nginx Reverse Proxy & Routing
- **Depends on:** T22, T23
- **Description:**
  - Configure `nginx` service to route `/api` to backend and `/` to frontend.
- **Includes:**
  - Nginx config file(s) under `/infra` or similar.
  - Proxy to backend container for `/api` requests.
  - Proxy or static hosting for frontend (dev: proxy to frontend service).
- **Done when:**
  - `docker-compose up nginx backend frontend` allows browsing app via single port (e.g. `http://localhost:8080`).
  - `GET /api/health` routed through nginx works.

### T25 – Logs Directory & Wiring
- **Depends on:** T20, T24
- **Description:**
  - Ensure logs are collected under `/logs` and git-ignored.
- **Includes:**
  - `logs/` directory created.
  - Volume mappings from nginx/backend/frontend containers to `./logs/...`.
- **Done when:**
  - Running stack writes logs into `./logs` (e.g. `logs/nginx/access.log`).

### T26 – Top-Level Makefile
- **Depends on:** T21, T22, T23, T24, T25, T9, T16
- **Description:**
  - Implement Makefile commands described in `ARCHITECTURE.md`.
- **Includes:**
  - Targets: `up`, `down`, `logs`, `backend-shell`, `frontend-shell`, `db-shell`, `qa`, `test`, `lint`, `build`.
  - `qa` runs backend + frontend tests and linters.
- **Done when:**
  - `make up` starts full stack (db + backend + frontend + nginx) and ensures `logs/` exists.
  - `make qa` runs all checks and passes on a fresh clone.

---

## Glue / Polish

### T30 – End-to-End Manual Verification Script (Docs)
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

### T31 – Golden JSON Examples
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
