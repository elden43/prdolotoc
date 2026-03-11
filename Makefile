COMPOSE := docker compose
BACKEND_RUN := $(COMPOSE) run --rm php-cli
FRONTEND_RUN := $(COMPOSE) run --rm frontend sh -lc

.PHONY: up down logs backend-shell frontend-shell db-shell qa test lint build backend-deps frontend-deps

up:
	@mkdir -p logs/nginx logs/backend logs/frontend
	$(COMPOSE) up -d db php-fpm php-cli frontend nginx

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

backend-shell:
	$(COMPOSE) exec php-fpm bash

frontend-shell:
	$(COMPOSE) exec frontend sh

db-shell:
	$(COMPOSE) exec db psql -U prdolotoc -d prdolotoc

qa: backend-deps frontend-deps test lint

test:
	$(BACKEND_RUN) ./vendor/bin/phpunit
	$(FRONTEND_RUN) "cd /app/frontend && npm test"

lint:
	$(BACKEND_RUN) php bin/console lint:container
	$(FRONTEND_RUN) "cd /app/frontend && npm run lint && npx tsc --noEmit"

build:
	$(COMPOSE) build

backend-deps:
	$(BACKEND_RUN) composer install --no-interaction --prefer-dist

frontend-deps:
	$(FRONTEND_RUN) "cd /app/frontend && rm -rf node_modules package-lock.json && npm install --no-package-lock"
