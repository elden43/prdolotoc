# Prďolotoč – Architecture

## 1. Overview
Prďolotoč is a small web app for random selection from a list of user-defined options.
Users can create and share configurations (`SpinConfig`) via URL slugs. The backend is a
stateless JSON API in Symfony; the frontend is a single-page experience in Next.js.

- **Backend:** PHP 8.3+, Symfony (latest LTS), pure JSON API
- **Database:** PostgreSQL via Doctrine ORM
- **Frontend:** Next.js (latest) + React + TypeScript, SPA-style UI
- **Infra:** Docker Compose with nginx routing `/api` → backend and `/` → frontend

The goal of this document is to give the coding agent a precise target for:
- Backend domain model & JSON contracts
- Frontend routes & data flow
- Infrastructure layout (directory structure, Docker, Makefile)

---

## 2. Backend (Symfony API)

### 2.1 Runtime & Framework
- **PHP:** 8.3+
- **Symfony:** Latest LTS, API-style (no Twig, JSON-only responses)
- **Database:** PostgreSQL (via Doctrine ORM)
- **Process model:** Stateless HTTP API, no sessions.

### 2.2 Domain Model

**Entity: SpinConfig**

Persistence via Doctrine ORM to PostgreSQL.

Fields:
- `id: uuid | string`
  - Primary key in DB (implementation detail: may be UUID or auto-increment int).
  - Exposed to clients as an opaque string.
- `name: string`
  - Required, non-empty.
  - Length: 1–100 characters (backend validation).
- `slug: string`
  - URL-safe, unique identifier.
  - Generated from `name` (kebab-case) with random/unique suffix when needed.
  - Unique index in DB.
- `options: string[]`
  - Required.
  - At least **one** option.
  - Each option trimmed; empty strings are rejected.
- `removeAfterPick: bool`
  - Required.
  - Controls whether picked options are removed from the pool on the **frontend**.
  - Default: `true` when not provided in input.
- `visualMode: string`
  - Enum: `"classic" | "slow" | "chaotic"`.
  - Default: `"classic"` when not provided.
- `createdAt: datetime`
  - Set on insert, immutable.

The API always returns `options` as an array of strings, never a single string.

### 2.3 Validation Rules

On `POST /api/spin-configs`:
- `name`:
  - Must be present and non-empty.
  - Length 1–100 characters.
- `options`:
  - Must be present.
  - Must be an array.
  - After trimming, at least one non-empty string must remain.
- `removeAfterPick`:
  - Optional; if missing, default to `true`.
  - If present, must be boolean.
- `visualMode`:
  - Optional; if missing, default to `classic`.
  - If present, must be one of: `classic`, `slow`, `chaotic`.

### 2.4 JSON Error Format (Global)

All error responses (4xx and 5xx) use the same JSON shape:

```json
{
  "error": "validation_failed | not_found | server_error",
  "message": "Human-readable summary",
  "details": {
    "fieldName": ["Error message 1", "Error message 2"]
  }
}
```

- `details` is **optional** and present mainly for `validation_failed`.
- For `not_found` and `server_error`, `details` MAY be omitted.
- `Content-Type` is always `application/json`.


### 2.5 API Endpoints

Base path: `/api`.

#### 2.5.1 `GET /api/health`
- **Purpose:** Liveness check for backend and DB.
- **Behaviour:**
  - Returns `{ "status": "ok" }` when the Symfony app is up.
  - MAY later include additional diagnostics (e.g. DB status), but contract is that
    `status === "ok"` means the service is generally healthy.
- **Response 200:**

  ```json
  { "status": "ok" }
  ```

#### 2.5.2 `POST /api/spin-configs`

- **Body (JSON):**
  ```jsonc
  {
    "name": "string",            // required
    "options": ["string", "..."], // required, at least 1
    "removeAfterPick": true,      // optional, default true
    "visualMode": "classic"       // optional, one of classic|slow|chaotic
  }
  ```

- **Validation errors (400):**
  - `name` must be non-empty and within length constraints.
  - `options` must contain at least one non-empty string.
  - `visualMode`, if provided, must be one of `classic`, `slow`, `chaotic`.

- **Behaviour:**
  - Validate input as above, return 400 with `error: "validation_failed"` on failure.
  - Normalise `options` to `string[]` (trim whitespace, remove empties).
  - Generate unique slug (kebab-case name + short random suffix if needed).
  - Persist `SpinConfig`.

- **Response 201 (success):**

  ```json
  {
    "id": "...",                 
    "slug": "...",
    "name": "My config",
    "options": ["A", "B", "C"],
    "removeAfterPick": true,
    "visualMode": "classic",
    "createdAt": "2026-03-10T10:00:00+00:00"
  }
  ```

- **Response 400 (validation error):**

  ```json
  {
    "error": "validation_failed",
    "message": "Request validation failed.",
    "details": {
      "name": ["This value should not be blank."],
      "options": ["At least one option is required."]
    }
  }
  ```

- **Response 500 (generic server error):**

  ```json
  {
    "error": "server_error",
    "message": "Unexpected error. Please try again later."
  }
  ```


#### 2.5.3 `GET /api/spin-configs/{slugOrId}`

- **Path parameter:** `slugOrId: string`

- **Behaviour:**
  - First attempts to resolve by `slug` (primary).
  - Optionally, if `slugOrId` matches the internal ID shape (e.g. UUID), the backend MAY
    also support lookup by `id` (implementation detail; not required by the frontend).
  - Returns 404 if not found.

- **Response 200 (success):** Same schema as `POST /api/spin-configs` 201 response.

- **Response 404:**

  ```json
  {
    "error": "not_found",
    "message": "SpinConfig not found"
  }
  ```

- **Response 500:** See generic server error above.


### 2.6 Testing Strategy (Backend)

- Use Symfony functional tests (e.g. `WebTestCase`):
  - `GET /api/health` returns 200 + `{ "status": "ok" }`.
  - Creating a SpinConfig (valid payload) returns 201 and persisted entity.
  - Creating with invalid payload (missing name / options / invalid visualMode) → 400 with
    `error: "validation_failed"` and field-level messages.
  - Retrieving by slug returns the same data as creation.
  - Unknown slug returns 404 with `error: "not_found"`.

---

## 3. Frontend (Next.js SPA)

### 3.1 Runtime & Framework
- **Node:** LTS supported by current Next.js.
- **Next.js:** Latest stable.
- **Language:** TypeScript.
- **Rendering mode:** SPA-style UX (client-side navigation, but can use Next routing).

### 3.2 Routes / Screens

1. **Home / Builder (`/`)**
   - Purpose: create new SpinConfig and start spinning.
   - UI Elements:
     - Text input for configuration name.
     - Textarea for list of options (one per line).
     - Checkbox/toggle: "Remove option after it is picked".
     - Select/dropdown for visual mode (Classic / Slow / Chaotic).
     - Primary button: "Roztočit!" – creates config via API, then navigates to `/s/[slug]`.
     - Secondary button: "Vytvořit sdílitelný odkaz" – creates config via API and shows URL
       with slug (and optionally navigates).
   - Behaviour:
     - On submit, call `POST /api/spin-configs`.
     - On success, get `slug` from response and redirect to `/s/[slug]`.
     - On validation error (HTTP 400), surface per-field messages from `details`.

2. **Spin / Share (`/s/[slug]`)**
   - Purpose: run spins for a given config.
   - Behaviour:
     - On load, fetch config from `GET /api/spin-configs/{slug}`.
     - Show colourful spinner / wheel / list-based animation summarising options.
     - Button to start the spin; visual representation reflects `visualMode`.
     - When result is determined, highlight winning option.
     - If `removeAfterPick` is true, remove picked option from local state so repeated
       spins operate on a shrinking set.
     - If config is not found (404), show friendly "not found" page.

### 3.3 Frontend–Backend Contract

- Frontend **only** uses the two SpinConfig endpoints and the health check for diagnostics.
- Frontend never mutates configs after creation (MVP).
- Frontend stores only the `slug` in URL and local state of already-picked options.
- All HTTP uses JSON, via a small typed API client (e.g. `lib/api.ts`).
- Error handling:
  - For 400 validation errors, parse `details` to show field-level messages.
  - For 404, show a not-found state.
  - For 5xx or network errors, show a generic error toast/state.

### 3.4 Testing Strategy (Frontend)

- Unit tests for key components (React Testing Library):
  - Builder form transforms textarea into string[] correctly.
  - `removeAfterPick` logic behaves as expected across spins.
- Light integration tests (optional first wave) to ensure `POST` + navigation flow works.

---

## 4. Infrastructure

### 4.1 Repository Layout (Target)

- `/backend` – Symfony app (API only)
- `/frontend` – Next.js app
- `/infra` (optional) – Docker / nginx templates and helper scripts
- `/logs` – runtime logs (git-ignored)
- `Makefile` – top-level workflow commands
- `docker-compose.yml` – services, networks, volumes

### 4.2 Docker Compose

Services (target):

- `db` – PostgreSQL
  - Exposes port (e.g. `5432` to host for local dev if needed).
  - Proper volumes for data persistence.
- `backend` – PHP-FPM running Symfony app
  - Uses the same code volume as host (`./backend`).
  - Runs `php-fpm` with proper entrypoint.
- `frontend` – Next.js app
  - Dev mode in local environment (`next dev`).
  - Production mode can be a separate image / build stage.
- `nginx` – reverse proxy & static file server
  - Routes `/api` → backend container (php-fpm / Symfony).
  - Routes `/` → frontend (dev server in dev, built static files in prod).

Networking:
- Single Docker network for all services (e.g. `prdolotoc_net`).
- Environment variables for DB connection (host = `db`, DB name/user/password configurable).

Logs:
- Map nginx and app logs to `./logs/...`.

### 4.3 Makefile Targets (Target)

Top-level `Makefile` in repo root should define at least:

- `make up`
  - Ensure `logs/` directory exists.
  - Run `docker-compose up -d` with all services.
- `make down`
  - Stop and remove containers (and optional networks).
- `make logs`
  - Tail main service logs (nginx + backend + frontend) from `logs/` or docker.
- `make backend-shell`
  - Open shell in backend container for debugging (e.g. `docker-compose exec backend bash`).
- `make frontend-shell`
  - Open shell in frontend container.
- `make db-shell`
  - Open psql shell.
- `make qa`
  - Run backend + frontend checks (tests + linters) in containers or host.
- `make test`
  - Run full test suite (backend + frontend).
- `make lint`
  - Run linters (PHP-CS-Fixer/PHPStan + ESLint/TypeScript).
- `make build`
  - Build production images / frontend bundle.

The exact implementation can evolve, but **task names** above are the expected contract.

---

## 5. Non-Goals (MVP)

- No authentication or user accounts.
- No editing or deleting configs after creation.
- No analytics, rate limiting, or advanced error reporting.
- No complex admin UI.

These can be revisited later; for now they should **not** appear in code or tasks.
