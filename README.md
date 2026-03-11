# Prdolotoc

## How to run locally

Prerequisite: Docker with `docker compose` support.

1. Start the full stack:
   - `make up`
   - Starts `db`, `php-fpm`, `php-cli`, `frontend`, `nginx`.
   - Ensures `logs/` directories exist (`logs/nginx`, `logs/backend`, `logs/frontend`).
2. Run all checks (tests + linters) via containers:
   - `make qa`

Useful commands:
- `make logs` - tail service logs
- `make test` - run backend + frontend tests
- `make lint` - run backend + frontend linters
- `make build` - build Docker images
- `make down` - stop and remove containers
- `make backend-shell` - open shell in `php-fpm`
- `make frontend-shell` - open shell in `frontend`
- `make db-shell` - open `psql` shell in `db`
