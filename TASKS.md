# TASKS – Prďolotoč

- [x] T1 – Backend Symfony API Scaffold — implemented ✅
## T1 – Backend Symfony API Scaffold
- Create `/backend` Symfony application (latest LTS, PHP 8.3+), API-style skeleton
- Configure Doctrine + DB connection (PostgreSQL via docker-compose service `db`)
- Add basic health endpoint (e.g. `GET /api/health` → `{ "status": "ok" }`)
- Wire minimal logging to `logs/backend.log` via Docker / Symfony config

**Done when:**
- Symfony app runs inside Docker (php-fpm) and responds to `GET /api/health`
- DB container starts and is reachable from Symfony

- [x] T2 – SpinConfig Entity + Persistence + API — implemented ✅
## T2 – SpinConfig Entity + Persistence + API
- Define `SpinConfig` entity + migration (fields per ARCHITECTURE.md)
- Implement repository/service for creating and fetching configs
- Implement controller actions:
  - `POST /api/spin-configs` – validate, create, and return config
  - `GET /api/spin-configs/{slugOrId}` – fetch and return config, 404 otherwise
- Add basic validation and error handling

**Done when:**
- POST + GET endpoints behave as specified
- Requests and responses match the schema in ARCHITECTURE.md
- Basic functional tests exist and pass

- [x] T3 – Frontend Next.js App — implemented ✅
## T3 – Frontend Next.js App
- Create `/frontend` Next.js (latest) app with TypeScript
- Implement main builder page `/`:
  - Inputs for name, options (textarea), removeAfterPick, visualMode
  - Button to create config via backend
  - Display resulting share URL with slug
- Implement shared spin page `/s/[slug]`:
  - Fetch config from backend
  - Display and animate options according to visual mode
  - Implement spinning logic with removeAfterPick behaviour

**Done when:**
- Frontend dev server runs (locally or via Docker) and both pages function against backend

- [x] T4 – Docker Compose + Nginx + Makefile — implemented ✅
## T4 – Docker Compose + Nginx + Makefile
- Create `docker-compose.yml` for services: `php-fpm`, `nginx`, `db`, `frontend` (dev)
- Configure nginx to route `/api` to backend and `/` to frontend
- Create top-level Makefile with targets:
  - `up`, `down`, `qa`, `test`, `lint`, `build`
  - Ensure `make up` creates `logs/` and wires container logs into `logs/*.log`

**Done when:**
- `make up` starts a working stack and both backend + frontend are reachable
- `make qa` runs backend + frontend tests/linters without errors

## T5 – SpinConfig Slug Generation Service
- Implement dedicated service/class responsible for generating unique, URL-safe slugs for SpinConfig entities
- Slug rules:
  - based on name (kebab-case, ascii-only)
  - append short random suffix when collision occurs
  - ensure uniqueness at DB level (unique index) and via retry logic in the service
- Integrate slug service into SpinConfig creation flow (POST /api/spin-configs)
- Cover slug generation with unit tests (service) and functional tests (creating configs with colliding names)

**Done when:**
- New SpinConfig records always get a non-empty, URL-safe, unique slug
- Creating multiple configs with the same name yields different slugs
- Tests for slug generation and collision handling pass via `make test`
