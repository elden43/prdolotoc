# Prďolotoč – Architecture

## 1. Overview
Prďolotoč is a small web app for random selection from a list of user-defined options.
Users can create and share configurations (SpinConfig) via URL slugs. The backend is a
stateless JSON API in Symfony; the frontend is a single-page experience in Next.js.

## 2. Backend (Symfony API)
- **Runtime**: PHP 8.3+
- **Framework**: Symfony (latest LTS, API-only style)
- **Responsibilities**:
  - Persist and retrieve `SpinConfig` entities
  - Expose JSON endpoints for creating and loading configs
  - Generate and enforce unique slugs
  - Perform basic validation (non-empty name, at least one option, etc.)

### 2.1 Domain Model
**Entity: SpinConfig**
- `id: uuid` (or auto-increment int; implementation detail)
- `name: string` – display name
- `slug: string` – URL-safe, unique identifier
- `options: string[]` – the list of options to spin between
- `removeAfterPick: bool` – whether picked options are removed from the pool
- `visualMode: string` – e.g. `"classic" | "slow" | "chaotic"` (extensible)
- `createdAt: datetime`

Persistence via Doctrine ORM to a relational DB (PostgreSQL or MySQL; exact vendor not
critical for MVP, pick one and wire through Docker Compose).

### 2.2 API Endpoints
Base path: `/api`.

- `POST /api/spin-configs`
  - **Body (JSON)**:
    - `name: string`
    - `options: string` or `string[]` (frontend will send array; backend normalises)
    - `removeAfterPick: bool`
    - `visualMode?: string`
  - **Behaviour**:
    - Validate input
    - Generate unique slug (e.g. kebab-case name + random suffix)
    - Persist SpinConfig
  - **Response 201**:
    ```json
    {
      "id": "...",
      "slug": "...",
      "name": "...",
      "options": ["..."],
      "removeAfterPick": true,
      "visualMode": "classic",
      "createdAt": "..."
    }
    ```

- `GET /api/spin-configs/{slugOrId}`
  - **Path parameter**: `slugOrId: string`
  - **Behaviour**:
    - Try to resolve by slug; optionally also support numeric ID
    - Return 404 if not found
  - **Response 200**: same schema as POST response

Error responses use JSON with `{ "error": "..." }`.

## 3. Frontend (Next.js SPA)
- **Runtime**: Node LTS
- **Framework**: Next.js (latest) with React and TypeScript
- **Mode**: Single-page UX (main screen at `/`, optional share view under `/s/[slug]`).

### 3.1 Screens
1. **Home / Builder (`/`)**
   - Text input for configuration name
   - Textarea for list of options (one per line)
   - Checkbox/toggle: "Remove option after it is picked"
   - Select/dropdown for visual mode (e.g. Classic / Slow / Chaotic)
   - Button: "Roztočit!" which either starts spinning immediately or first saves
     the config via backend and then transitions into spin view.
   - Button: "Vytvořit sdílitelný odkaz" – creates config via API and shows URL
     with slug.

2. **Spin / Share (`/s/[slug]`)**
   - Loads configuration from backend via `slug`
   - Shows colourful spinner / wheel / list-based animation summarising options
   - Button to start/stop the spin, highlights the winning option
   - Behaviour respects `removeAfterPick` on the client side for repeated spins
   - Simple visual modes:
     - **Classic** – default speed and colours
     - **Slow** – slower animation
     - **Chaotic** – faster / randomised colour transitions

### 3.2 Frontend-Backend Contract
- Frontend never needs to mutate configs after creation (MVP).
- Frontend stores only the `slug` in URL and local state of already-picked options.
- All HTTP is JSON, handled via fetch/axios-wrapped client using the endpoints above.

## 4. Infrastructure

### 4.1 Repository Layout (target)
- `/backend` – Symfony app (API only)
- `/frontend` – Next.js app
- `/infra` (optional) – Docker / nginx templates
- `Makefile` – top-level workflow commands
- `docker-compose.yml` – services, networks, volumes
- `logs/` – runtime logs (git-ignored)

### 4.2 Docker Compose
Services:
- `php-fpm` – Symfony backend
- `nginx` – serves backend under `/api` and frontend static build under `/`
- `db` – PostgreSQL (or MySQL) for SpinConfig persistence
- `frontend` (optional for dev) – Next.js dev server during development only

High-level wiring:
- nginx routes `/api` → php-fpm container
- nginx routes `/` → built Next.js app (or dev server in development)

## 5. Makefile Targets
Top-level Makefile (in repo root):

- `make up` – start full local environment via Docker Compose, ensure `logs/` exists
- `make down` – stop environment / docker-compose
- `make qa` – run all checks (backend + frontend)
- `make test` – run all tests
- `make lint` – run linters
- `make build` – build production images / frontend bundle

## 6. Testing Strategy

### Backend
- Symfony HTTP tests (e.g. WebTestCase) for:
  - Creating a SpinConfig (valid payload)
  - Creating with invalid payload (missing name / options)
  - Retrieving by slug
  - 404 on unknown slug

### Frontend
- Basic React Testing Library tests for core components (optional for first MVP).

## 7. Non-Goals (MVP)
- No authentication or user accounts
- No editing or deleting configs after creation
- No analytics, rate limiting, or advanced error reporting
