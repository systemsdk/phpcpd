include .env
# Determine if .env.local file exist
ifneq ("$(wildcard .env.local)", "")
	include .env.local
endif

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
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose -f compose.yaml build
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start dev environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose -f compose.yaml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop dev environment containers
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose -f compose.yaml $(PROJECT_NAME) stop
else
	$(ERROR_ONLY_FOR_HOST)
endif

down: ## Stop and remove dev environment containers, networks
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose -f compose.yaml $(PROJECT_NAME) down
else
	$(ERROR_ONLY_FOR_HOST)
endif

restart: stop start ## Stop and start dev environment

ssh: ## Get bash inside php docker container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

ssh-root: ## Get bash as root user inside php docker container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose $(PROJECT_NAME) exec $(OPTION_T) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

exec:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@$$cmd
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php $$cmd
endif

exec-bash:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash -c "$(cmd)"
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash -c "$(cmd)"
endif

exec-by-root:
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) XDEBUG_CONFIG=$(XDEBUG_CONFIG) XDEBUG_VERSION=$(XDEBUG_VERSION) docker compose $(PROJECT_NAME) exec $(OPTION_T) php $$cmd
else
	$(ERROR_ONLY_FOR_HOST)
endif

info: ## Shows php, composer, phing, phive versions
	@make exec cmd="php --version"
	@make exec cmd="composer --version"
	@make exec cmd="php phing.phar -v"
	@make exec cmd="phive --version"
	@make exec cmd="xalan -v"

logs: ## Shows logs from the php container. Use ctrl+c in order to exit
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-php
else
	$(ERROR_ONLY_FOR_HOST)
endif

setup: ## Cleanup build artifacts and installs composer dependencies
	@make exec cmd="php phing.phar setup"

update: ## Updates composer dependencies & tools
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"
	@make exec cmd="php phing.phar update-tools"

composer-audit: ## Checks for security vulnerability advisories for installed packages
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer audit"

phar: ## Create PHAR archive of phpcpd and all its dependencies
	@make exec cmd="php phing.phar phar"

signed-phar: ## Create signed PHAR archive of PHPCPD and all its dependencies (release)
	@make exec cmd="php phing.phar signed-phar"

phpunit: ## Runs PhpUnit tests and create coverage report inside reports/coverage folder
	@make exec-bash cmd="./vendor/bin/phpunit -c phpunit.xml.dist --coverage-html reports/coverage --coverage-clover reports/clover.xml --log-junit reports/junit.xml"

phpcpd-run: ## Runs phpcpd
	@make exec-bash cmd="php phpcpd --fuzzy --log-pmd=reports/phpcpd/phpcpd-report-v1.xml tests/Fixture"

phpcpd-html-report: ## Generate html report (should be run after phpcpd-run)
	@make exec-bash cmd="xalan -in reports/phpcpd/phpcpd-report-v1.xml -xsl https://systemsdk.github.io/phpcpd/report/phpcpd-html-v1_0_0.xslt -out reports/phpcpd/phpcpd-report-v1.html"

report-code-coverage: ## Updates code coverage on coveralls.io. Note: COVERALLS_REPO_TOKEN should be set on CI side.
	@make exec-bash cmd="export COVERALLS_REPO_TOKEN=${COVERALLS_REPO_TOKEN} && php ./vendor/bin/php-coveralls -v --coverage_clover reports/clover.xml --json_path reports/coverals.json"

phpcs: ## Runs PHP CodeSniffer
	@make exec-bash cmd="./vendor/bin/phpcs --version && ./vendor/bin/phpcs --standard=PSR12 --colors -p src tests/Unit"

ecs: ## Runs Easy Coding Standard tool
	@make exec-bash cmd="./vendor/bin/ecs --version && ./vendor/bin/ecs --clear-cache check src tests/Unit"

ecs-fix: ## Runs Easy Coding Standard tool to fix issues
	@make exec-bash cmd="./vendor/bin/ecs --version && ./vendor/bin/ecs --clear-cache --fix check src tests/Unit"

phpstan: ## Runs PhpStan static analysis tool
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan analyze src tests/Unit
else
	@make exec cmd="make phpstan"
endif
