# AGENTS.md - Prďolotoč Project

## Project
- **Name:** Prďolotoč (prdolotoc)
- **Purpose:** Simple web "kolotoč" for random selection from a user-provided list, with shareable configurations.

## Branches

- default_branch: feature/20260310-backend-codex
- architecture_branch: feature/20260310-initial-architecture

## Stack (Target State)
- **Backend:** PHP 8.3+, Symfony (latest LTS), pure JSON API only, running **exclusively inside Docker** (PHP-FPM + CLI + Composer baked into the image; no host PHP/Composer).
- **Database:** PostgreSQL via Doctrine ORM (in its own Docker container).
- **Frontend:** Next.js (latest) + React + TypeScript, SPA-style UI, running in a dedicated Node/Next Docker container.
- **Infra:** Docker Compose stack (services: `db`, `php-fpm`, `php-cli`, `frontend`, `nginx`) + Makefile helpers. nginx acts as a reverse proxy: `/api` → Symfony backend, `/` → Next frontend.

## High-Level Domains
- **SpinConfig** (backend):
  - Named configuration of options and behaviour (removal after pick, visual mode)
  - Persisted in PostgreSQL via Doctrine
  - Resolved by URL-safe `slug`
- **SpinSession** (frontend-only):
  - Client-side running spin based on a SpinConfig
  - Tracks picked options across multiple spins (uses `removeAfterPick` flag)

## API Conventions
- Backend API under `/api/...`, JSON only.
- All responses use a consistent JSON envelope and error format:
  - **Success (2xx):** raw resource object(s), no extra wrapper.
  - **Client / server errors:**
    ```json
    {
      "error": "validation_failed | not_found | server_error",
      "message": "Human-readable summary",
      "details": { "field": ["message", "..."] }
    }
    ```
  - `details` is optional and only present for validation errors.
- Shareable configs via slug in URL (no auth).
- All times in ISO 8601 with timezone (e.g. `2026-03-10T10:00:00+00:00`).

## Logging & Runtime
- All long-running services log under `logs/` (git-ignored):
  - `logs/nginx/*.log`
  - `logs/backend/*.log`
  - `logs/frontend/*.log` (if needed)
  - Docker services wired to log to files where feasible.

## Agents & Roles

### Human
- **Owner:** Jakub
- **Decides:** Product direction, UX tweaks, infra tradeoffs.

### Autonomous Coder (Codex engine)
- Works **task-by-task** from `TASKS.md` (no improvising outside tasks).
- Operates **entirely inside the Docker stack** – it should never rely on host PHP/Composer/Symfony.
- For each task:
  - Reads relevant parts of `ARCHITECTURE.md` and existing code.
  - Uses Makefile + `docker compose` commands for all backend/DB/QA actions (e.g. `make up`, `make qa`, `docker compose run --rm php-cli ...`).
  - Implements changes in a focused branch.
  - Adds/updates tests and Makefile targets as needed, but **does not** change public JSON contracts without updating `ARCHITECTURE.md`.
- Prefer small, frequent PRs aligned with TASK IDs (e.g. `feature/T2a-spinconfig-entity`).

### Architectural / Planning Agent (you are here)
- Updates `ARCHITECTURE.md` and `TASKS.md`.
- Keeps the architecture **fully Docker-centric** (no assumptions about host PHP/Composer/Symfony).
- Does **not** write application code.

## Workflow Summary for Agents

- **Bring stack up:** `make up` ⇒ starts `db`, `php-fpm`, `php-cli`, `frontend`, `nginx`.
- **Run full QA:** `make qa` ⇒ executes backend & frontend tests + linters via containers.
- **Common ad-hoc commands (examples):**
  - `docker compose run --rm php-cli composer install`
  - `docker compose run --rm php-cli php bin/console doctrine:migrations:diff`
  - `docker compose exec php-fpm php bin/console doctrine:migrations:migrate`
  - `docker compose run --rm php-cli ./vendor/bin/phpunit`

Agents should prefer these Make/Docker entrypoints instead of calling `php`, `composer`, or `npm` directly on the host.

## Golden Examples (to be filled later)
- After first implementation PRs, add 1–2 end-to-end examples:
  - Example request/response for `POST /api/spin-configs`.
  - Example usage of `/s/[slug]` URL.
