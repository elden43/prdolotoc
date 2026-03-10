# AGENTS.md - Prďolotoč Project

## Project
- **Name:** Prďolotoč (prdolotoc)
- **Purpose:** Simple web "kolotoč" for random selection from a user-provided list, with shareable configurations.

## Stack (Target State)
- **Backend:** PHP 8.3+, Symfony (latest LTS), pure JSON API only
- **Database:** PostgreSQL via Doctrine ORM
- **Frontend:** Next.js (latest) + React + TypeScript, SPA-style UI
- **Infra:** Docker Compose (PHP-FPM + nginx + Node/Next + PostgreSQL), Makefile helpers

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
- For each task:
  - Reads relevant parts of `ARCHITECTURE.md` and existing code.
  - Implements changes in a focused branch.
  - Adds/updates tests and Makefile targets as needed, but **does not** change public JSON contracts without updating `ARCHITECTURE.md`.
- Prefer small, frequent PRs aligned with TASK IDs (e.g. `feature/T2a-spinconfig-entity`).

### Architectural / Planning Agent (you are here)
- Updates `ARCHITECTURE.md` and `TASKS.md`.
- Does **not** write application code.

## Golden Examples (to be filled later)
- After first implementation PRs, add 1–2 end-to-end examples:
  - Example request/response for `POST /api/spin-configs`.
  - Example usage of `/s/[slug]` URL.
