.RECIPEPREFIX = >

DC := docker compose
APP := app
EXEC := $(DC) exec -T $(APP)

.DEFAULT_GOAL := help

help: ## Show this help
> @grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Build, start the stack, install dependencies and set the app key
> $(DC) up -d --build
> $(EXEC) composer install
> $(EXEC) php artisan key:generate --ansi

down: ## Stop and remove the stack (keeps data volumes)
> $(DC) down

down-volumes: ## Stop the stack and delete database/cache volumes
> $(DC) down -v

build: ## Build the application image
> $(DC) build

ps: ## Show the status of every service
> $(DC) ps

logs: ## Tail the logs of all services
> $(DC) logs -f

shell: ## Open a shell inside the app container
> $(DC) exec $(APP) bash

composer-install: ## Install composer dependencies inside the container
> $(EXEC) composer install

key: ## Generate the application key
> $(EXEC) php artisan key:generate

migrate: ## Run database migrations
> $(EXEC) php artisan migrate

fresh: ## Drop, recreate and seed the database
> $(EXEC) php artisan migrate:fresh --seed

seed: ## Seed the database
> $(EXEC) php artisan db:seed

test: ## Run the test suite
> $(EXEC) php artisan test

coverage: ## Run the tests with a minimum coverage threshold
> $(EXEC) php artisan test --coverage --min=80

lint: ## Fix code style with Pint
> $(EXEC) ./vendor/bin/pint

lint-test: ## Check code style without modifying files
> $(EXEC) ./vendor/bin/pint --test

stan: ## Run static analysis with Larastan
> $(EXEC) ./vendor/bin/phpstan analyse --memory-limit=1G

ci: lint-test stan test ## Run the same checks as CI

.PHONY: help up down down-volumes build ps logs shell composer-install key migrate fresh seed test coverage lint lint-test stan ci
