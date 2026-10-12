# Orbita - development and maintenance commands.
# Run `make` or `make help` to list everything.

DC      := docker compose
APP     := $(DC) exec app
APP_T   := $(DC) exec -T app
ARTISAN := $(APP) php artisan
PNPM    := pnpm

# Published ports. Override to avoid clashing with another project, e.g.
#   make up ORBITA_APP_PORT=9100
# These defaults are mirrored in docker-compose.yml.
ORBITA_APP_PORT  ?= 8100
ORBITA_VITE_PORT ?= 5274
ORBITA_DB_PORT   ?= 5433

export ORBITA_APP_PORT ORBITA_VITE_PORT ORBITA_DB_PORT

# Credentials for the seeded development user.
DEV_EMAIL    ?= owner@orbita.co
DEV_PASSWORD ?= orbita2026
DEV_NAME     ?= Owner

.DEFAULT_GOAL := help

.PHONY: help
help: ## List every available command
	@printf "\n\033[1mOrbita\033[0m - make targets\n"
	@awk 'BEGIN {FS = ":.*?## "} \
		/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5); next } \
		/^[a-zA-Z_-]+:.*?## / { printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2 }' \
		$(MAKEFILE_LIST)
	@printf "\n\033[1mArguments\033[0m\n"
	@printf "  make test only=SecurityTest\n"
	@printf "  make artisan cmd=\"migrate:status\"\n"
	@printf "  make composer cmd=\"require spatie/laravel-data\"\n"
	@printf "  make up ORBITA_APP_PORT=9100        (override any published port)\n"
	@$(MAKE) --no-print-directory urls

.PHONY: urls
urls: ## Show the URLs and ports this stack publishes
	@printf "\n\033[1mURLs\033[0m\n"
	@printf "  App          http://localhost:$(ORBITA_APP_PORT)\n"
	@printf "  Vite (HMR)   http://localhost:$(ORBITA_VITE_PORT)\n"
	@printf "  PostgreSQL   localhost:$(ORBITA_DB_PORT)   user=orbita pass=orbita\n"
	@printf "               databases: orbita (dev), orbita_test (suite)\n\n"

##@ Stack

.PHONY: install
install: ## First-time setup: build, start and migrate
	$(DC) up -d --build --remove-orphans
	@$(MAKE) --no-print-directory wait-db
	$(ARTISAN) migrate
	@printf "\nReady. Create a login with: make user\n"
	@$(MAKE) --no-print-directory urls

.PHONY: up
up: ## Start the stack
	$(DC) up -d --remove-orphans
	@$(MAKE) --no-print-directory urls

.PHONY: build
build: ## Rebuild the images and start the stack
	$(DC) up -d --build --remove-orphans

.PHONY: down
down: ## Stop and remove the containers
	$(DC) down --remove-orphans

.PHONY: stop
stop: ## Stop the containers without removing them
	$(DC) stop

.PHONY: restart
restart: ## Restart the stack
	$(DC) restart

.PHONY: ps
ps: ## Show the status of every service
	$(DC) ps

.PHONY: ports
ports: ## Show which host ports are already taken
	@lsof -nP -iTCP -sTCP:LISTEN 2>/dev/null \
		| awk 'NR>1 {split($$9,a,":"); print a[length(a)], $$1}' | sort -u -n

.PHONY: wait-db
wait-db: ## Block until PostgreSQL accepts connections
	@printf "Waiting for PostgreSQL...\n"
	@until $(DC) exec -T postgres pg_isready -U orbita -d orbita >/dev/null 2>&1; do sleep 1; done
	@printf "PostgreSQL is ready.\n"

##@ Logs and shells

.PHONY: logs
logs: ## Follow the logs of every service
	$(DC) logs -f

.PHONY: logs-app
logs-app: ## Follow the application logs
	$(DC) logs -f app

.PHONY: logs-vite
logs-vite: ## Follow the Vite dev server logs
	$(DC) logs -f vite

.PHONY: tail
tail: ## Follow the Laravel log with Pail
	$(ARTISAN) pail

.PHONY: shell
shell: ## Open a shell inside the app container
	$(APP) bash

.PHONY: tinker
tinker: ## Open a Tinker REPL
	$(ARTISAN) tinker

.PHONY: psql
psql: ## Open psql against the development database
	$(DC) exec postgres psql -U orbita -d orbita

.PHONY: psql-test
psql-test: ## Open psql against the test database
	$(DC) exec postgres psql -U orbita -d orbita_test

##@ Database

.PHONY: migrate
migrate: ## Run pending migrations
	$(ARTISAN) migrate

.PHONY: migrate-status
migrate-status: ## Show the status of every migration
	$(ARTISAN) migrate:status

.PHONY: rollback
rollback: ## Roll back the last batch of migrations
	$(ARTISAN) migrate:rollback

.PHONY: fresh
fresh: ## Drop every table, migrate and seed (DESTROYS development data)
	@printf "This will DESTROY all data in the development database. Continue? [y/N] " \
		&& read answer && [ "$$answer" = "y" ] || (printf "Aborted.\n"; exit 1)
	$(ARTISAN) migrate:fresh --seed

.PHONY: seed
seed: ## Run the database seeders
	$(ARTISAN) db:seed

.PHONY: user
user: ## Create a development login (DEV_EMAIL / DEV_PASSWORD)
	@$(APP_T) php artisan tinker --execute="\
		\$$user = App\Models\User::firstOrCreate(\
			['email' => '$(DEV_EMAIL)'],\
			['name' => '$(DEV_NAME)', 'password' => bcrypt('$(DEV_PASSWORD)')]\
		);\
		echo \$$user->wasRecentlyCreated ? 'created' : 'already exists';"
	@printf "\nEmail:    $(DEV_EMAIL)\nPassword: $(DEV_PASSWORD)\n"

##@ Quality gates

.PHONY: check
check: pint-test stan test assets types ## Run every quality gate
	@printf "\nAll gates passed.\n"

.PHONY: test
test: ## Run the test suite (only=Name to narrow it)
	$(APP) php artisan test $(if $(only),--filter=$(only),)

.PHONY: test-unit
test-unit: ## Run only the unit suite
	$(APP) php artisan test --testsuite=Unit

.PHONY: test-feature
test-feature: ## Run only the feature suite
	$(APP) php artisan test --testsuite=Feature

.PHONY: pint
pint: ## Fix code style
	$(APP) vendor/bin/pint

.PHONY: pint-test
pint-test: ## Check code style without changing files
	$(APP) vendor/bin/pint --test

.PHONY: stan
stan: ## Run static analysis
	$(APP) vendor/bin/phpstan analyse --memory-limit=1G

.PHONY: types
types: ## Type-check the application sources
	@# laravel/wayfinder 0.1.x emits `method: 'query'`, which is absent from the
	@# Method union in @inertiajs/core 3.9. Those files are generated and
	@# gitignored, so only our own sources gate the build. Run `make types-all`
	@# to see everything, generated output included.
	@out=$$($(PNPM) run types:check 2>&1 || true); \
	ours=$$(printf '%s\n' "$$out" | grep -E '^resources/js/' | grep -vE '^resources/js/(actions|routes|wayfinder)/' || true); \
	if [ -n "$$ours" ]; then printf '%s\n' "$$ours"; exit 1; fi; \
	gen=$$(printf '%s\n' "$$out" | grep -cE '^resources/js/(actions|routes|wayfinder)/' || true); \
	printf 'No type errors in application sources.\n'; \
	[ "$$gen" -eq 0 ] || printf 'Note: %s pre-existing error(s) in generated Wayfinder output (upstream).\n' "$$gen"

.PHONY: types-all
types-all: ## Type-check everything, generated output included
	$(PNPM) run types:check

.PHONY: assets
assets: ## Build the frontend (also verifies the Wayfinder routes)
	$(PNPM) run build

.PHONY: lint-js
lint-js: ## Lint and format the frontend sources
	$(PNPM) run check:fix

##@ Dependencies and caches

.PHONY: deps
deps: ## Install the PHP and JavaScript dependencies
	$(APP) composer install
	$(PNPM) install

.PHONY: routes
routes: ## List the registered routes
	$(ARTISAN) route:list

.PHONY: about
about: ## Show the application environment and drivers
	$(ARTISAN) about

.PHONY: clear
clear: ## Clear every application cache
	$(ARTISAN) optimize:clear

.PHONY: artisan
artisan: ## Run an Artisan command, e.g. make artisan cmd="migrate:status"
	$(ARTISAN) $(cmd)

.PHONY: composer
composer: ## Run a Composer command, e.g. make composer cmd="require foo/bar"
	$(APP) composer $(cmd)

##@ Teardown

.PHONY: destroy
destroy: ## Remove the containers AND volumes (DESTROYS the database)
	@printf "This will DESTROY the containers and all volumes, including the database. Continue? [y/N] " \
		&& read answer && [ "$$answer" = "y" ] || (printf "Aborted.\n"; exit 1)
	$(DC) down -v
