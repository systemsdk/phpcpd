include .env

ifndef INSIDE_DOCKER_CONTAINER
	INSIDE_DOCKER_CONTAINER = 0
endif

HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
PHP_USER := -u www-data
PROJECT_NAME := -p ${COMPOSE_PROJECT_NAME}
INTERACTIVE := $(shell [ -t 0 ] && echo 1)
ERROR_ONLY_FOR_HOST = @printf "\033[33mThis command for host machine\033[39m\n"
.DEFAULT_GOAL := help
ifneq ($(INTERACTIVE), 1)
	OPTION_T := -T
endif

help: ## Shows available commands with description
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

build-dev: ## Build dev environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose -f docker-compose.yml build --no-cache
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start dev environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose -f docker-compose.yml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop dev environment containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose -f docker-compose.yml $(PROJECT_NAME) stop
else
	$(ERROR_ONLY_FOR_HOST)
endif

down: ## Stop and remove dev environment containers, networks
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose -f docker-compose.yml $(PROJECT_NAME) down
else
	$(ERROR_ONLY_FOR_HOST)
endif

restart: stop start ## Stop and start dev environment

ssh: ## Get bash inside php docker container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

ssh-root: ## Get bash as root user inside php docker container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose $(PROJECT_NAME) exec $(OPTION_T) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

exec:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@$$cmd
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php $$cmd
endif

exec-bash:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash -c "$(cmd)"
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash -c "$(cmd)"
endif

exec-by-root:
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) docker-compose $(PROJECT_NAME) exec $(OPTION_T) php $$cmd
else
	$(ERROR_ONLY_FOR_HOST)
endif

info: ## Shows php, composer, phing, phive versions
	@make exec cmd="php --version"
	@make exec cmd="composer --version"
	@make exec cmd="php phing.phar -v"
	@make exec cmd="phive --version"

setup: # Cleanup build artifacts and install dependencies with composer
	make exec cmd="php phing.phar setup"

update-tools: ## Update tools
	make exec cmd="php phing.phar update-tools"

phar: ## Create PHAR archive of phpcpd and all its dependencies
	make exec cmd="php phing.phar phar"

signed-phar: ## Create signed PHAR archive of PHPCPD and all its dependencies (release)
	make exec cmd="php phing.phar signed-phar"

composer-install: ## Installs composer dependencies
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader"

composer-update: ## Updates composer dependencies
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"

phpunit: ## Runs PhpUnit tests and create coverage report inside reports/coverage folder
	@make exec-bash cmd="./tools/phpunit -c phpunit.xml --coverage-html reports/coverage --coverage-clover reports/clover.xml --log-junit reports/junit.xml"

logs: ## Shows logs from the php container. Use ctrl+c in order to exit
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-php
else
	$(ERROR_ONLY_FOR_HOST)
endif
