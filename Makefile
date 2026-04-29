ENV ?= dev
ENV_FILE := .env.$(ENV)
COMPOSE_FILE := compose.$(ENV).yaml
COMPOSE := docker compose --env-file $(ENV_FILE) -f compose.yaml -f $(COMPOSE_FILE)

DEV_ENV_FILE ?= .env.dev
STAGING_ENV_FILE ?= .env.staging
PROD_ENV_FILE ?= .env.prod

DEV_PROJECT ?= app-dev
STAGING_PROJECT ?= app-staging
PROD_PROJECT ?= app-prod

COMPOSE_INFRA := docker compose -f compose.infra.yaml
COMPOSE_DEV := docker compose --env-file $(DEV_ENV_FILE) -f compose.yaml -f compose.dev.yaml -p $(DEV_PROJECT)
COMPOSE_STAGING := docker compose --env-file $(STAGING_ENV_FILE) -f compose.yaml -f compose.staging.yaml -p $(STAGING_PROJECT)
COMPOSE_PROD := docker compose --env-file $(PROD_ENV_FILE) -f compose.yaml -f compose.prod.yaml -p $(PROD_PROJECT)

APP_PROXY_NETWORK ?= app_proxy

.PHONY: build up down restart clean prune bash assets caddy-cert
.PHONY: check-env check-dev-env check-staging-env check-prod-env proxy-network
.PHONY: up-infra down-infra clean-infra up-dev down-dev clean-dev up-staging down-staging clean-staging up-prod down-prod clean-prod
.PHONY: bootstrap-dev bootstrap-staging bootstrap-prod
.PHONY: up-all down-all clean-all

check-env:
	@test -f "$(ENV_FILE)" || (echo "Missing $(ENV_FILE)" && exit 1)
	@test -f "$(COMPOSE_FILE)" || (echo "Unsupported ENV=$(ENV). Expected dev, staging, or prod." && exit 1)

check-dev-env:
	@test -f "$(DEV_ENV_FILE)" || (echo "Missing $(DEV_ENV_FILE)" && exit 1)

check-staging-env:
	@test -f "$(STAGING_ENV_FILE)" || (echo "Missing $(STAGING_ENV_FILE)" && exit 1)

check-prod-env:
	@test -f "$(PROD_ENV_FILE)" || (echo "Missing $(PROD_ENV_FILE)" && exit 1)

proxy-network:
	@docker network inspect $(APP_PROXY_NETWORK) >/dev/null 2>&1 || docker network create $(APP_PROXY_NETWORK)

build: check-env
	$(COMPOSE) build

up: check-env proxy-network
	$(COMPOSE) up -d
	make bash

down: check-env
	$(COMPOSE) down

restart: down up

clean: check-env
	$(COMPOSE) down -v

prune:
	docker system prune -f

bash: check-env
	$(COMPOSE) exec app bash

assets: check-env
	$(COMPOSE) exec -T app php bin/console tailwind:build --minify --env=prod --no-interaction
	$(COMPOSE) exec -T app php bin/console asset-map:compile --env=prod --no-interaction

caddy-cert:
	@mkdir -p Caddy/certs
	$(COMPOSE_INFRA) cp caddy:/data/caddy/pki/authorities/local/root.crt Caddy/certs/caddy-local-root.crt
	@echo "Extracted Caddy root certificate to Caddy/certs/caddy-local-root.crt"

up-infra: proxy-network
	$(COMPOSE_INFRA) up -d

down-infra:
	$(COMPOSE_INFRA) down

clean-infra:
	$(COMPOSE_INFRA) down -v

up-dev: check-dev-env proxy-network
	$(COMPOSE_DEV) up -d

down-dev: check-dev-env
	$(COMPOSE_DEV) down

clean-dev: check-dev-env
	$(COMPOSE_DEV) down -v

up-staging: check-staging-env proxy-network
	$(COMPOSE_STAGING) up -d

down-staging: check-staging-env
	$(COMPOSE_STAGING) down

clean-staging: check-staging-env
	$(COMPOSE_STAGING) down -v

up-prod: check-prod-env proxy-network
	$(COMPOSE_PROD) up -d

bootstrap-dev: check-dev-env
	$(COMPOSE_DEV) exec -T app php bin/console doctrine:database:drop --force
	$(COMPOSE_DEV) exec -T app php bin/console doctrine:database:create
	$(COMPOSE_DEV) exec -T app php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE_DEV) exec -T app php bin/console tailwind:build

bootstrap-staging: check-staging-env
	$(COMPOSE_STAGING) exec -T app php bin/console doctrine:database:drop --force
	$(COMPOSE_STAGING) exec -T app php bin/console doctrine:database:create
	$(COMPOSE_STAGING) exec -T app php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE_STAGING) exec -T app php bin/console importmap:install --env=prod --no-interaction
	$(COMPOSE_STAGING) exec -T app php bin/console assets:install public --env=prod --no-interaction
	$(COMPOSE_STAGING) exec -T app php bin/console tailwind:build --minify --env=prod --no-interaction
	$(COMPOSE_STAGING) exec -T app php bin/console asset-map:compile --env=prod --no-interaction

bootstrap-prod: check-prod-env
	$(COMPOSE_PROD) exec -T app php bin/console doctrine:database:drop --force
	$(COMPOSE_PROD) exec -T app php bin/console doctrine:database:create
	$(COMPOSE_PROD) exec -T app php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE_PROD) exec -T app php bin/console importmap:install --env=prod --no-interaction
	$(COMPOSE_PROD) exec -T app php bin/console assets:install public --env=prod --no-interaction
	$(COMPOSE_PROD) exec -T app php bin/console tailwind:build --minify --env=prod --no-interaction
	$(COMPOSE_PROD) exec -T app php bin/console asset-map:compile --env=prod --no-interaction

down-prod: check-prod-env
	$(COMPOSE_PROD) down

clean-prod: check-prod-env
	$(COMPOSE_PROD) down -v

up-all: check-dev-env check-staging-env check-prod-env proxy-network
	$(MAKE) up-infra
	$(MAKE) up-dev
	$(MAKE) bootstrap-dev
	$(MAKE) up-staging
	$(MAKE) bootstrap-staging
	$(MAKE) up-prod
	$(MAKE) bootstrap-prod

down-all: down-prod down-staging down-dev down-infra

clean-all: clean-prod clean-staging clean-dev clean-infra
