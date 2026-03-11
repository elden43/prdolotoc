COMPOSE := docker compose

.PHONY: up down logs backend-shell frontend-shell db-shell qa test test-backend lint build

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

qa: test lint

test: test-backend

test-backend:
	$(COMPOSE) run --rm php-cli composer test

lint:
	$(COMPOSE) run --rm php-cli php bin/console lint:container

build:
	$(COMPOSE) build
