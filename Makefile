ENV ?= dev
ENV_FILE := .env.$(ENV)
COMPOSE_FILE := compose.$(ENV).yaml
COMPOSE := docker compose --env-file $(ENV_FILE) -f compose.yaml -f $(COMPOSE_FILE)

.PHONY: build up down restart clean prune bash assets
.PHONY: caddy-cert

build:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) build

up:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) up -d
	make bash

down:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) down

restart: down up

clean:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) down -v

prune:
	docker system prune -f

bash:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) exec app bash

assets:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	$(COMPOSE) exec -T app php bin/console tailwind:build --minify --env=prod --no-interaction
	$(COMPOSE) exec -T app php bin/console asset-map:compile --env=prod --no-interaction

caddy-cert:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)
	@mkdir -p Caddy/certs
	$(COMPOSE) cp caddy:/data/caddy/pki/authorities/local/root.crt Caddy/certs/caddy-local-root.$(ENV).crt
	@echo "Extracted Caddy root certificate to Caddy/certs/caddy-local-root.$(ENV).crt"
