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
ifeq ($(CIRCLECI), true)
    OPTION_T += -e COLUMNS=120
endif
ifeq ($(GITLAB_CI), 1)
	# Determine additional params for phpunit in order to generate coverage badge on GitLabCI side
	PHPUNIT_OPTIONS := --coverage-text --colors=never
endif

help: ## Show available commands and their descriptions
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

export HOST_UID HOST_GID XDEBUG_CONFIG XDEBUG_VERSION

build-dev: ## Build the development environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose -f compose.yaml build
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start the development environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose -f compose.yaml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop the development environment containers (without removing them)
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose -f compose.yaml $(PROJECT_NAME) stop
else
	$(ERROR_ONLY_FOR_HOST)
endif

down: ## Stop and remove the development environment containers and networks
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose -f compose.yaml $(PROJECT_NAME) down
else
	$(ERROR_ONLY_FOR_HOST)
endif

restart: stop start ## Restart the development environment

ssh: ## Access the bash shell inside the php container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

ssh-root: ## Access the bash shell as root inside the php container
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose $(PROJECT_NAME) exec $(OPTION_T) php bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

exec:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@$$cmd
else
	@docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php $$cmd
endif

exec-bash:
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@bash -c "$(cmd)"
else
	@docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) php bash -c "$(cmd)"
endif

exec-by-root:
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker compose $(PROJECT_NAME) exec $(OPTION_T) php $$cmd
else
	$(ERROR_ONLY_FOR_HOST)
endif

info: ## Shows php, composer, phing, phive versions
	@make exec cmd="php --version"
	@make exec cmd="composer --version"
	@make exec cmd="php phing.phar -v"
	@make exec cmd="phive --version"
	@make exec cmd="xalan -v"

logs: ## View logs from the php container (use ctrl+c to exit)
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-php
else
	$(ERROR_ONLY_FOR_HOST)
endif

setup: ## Cleanup build artifacts and installs composer dependencies
	@make exec cmd="php phing.phar setup"

update: ## Update composer dependencies & tools
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"
	@make exec cmd="php phing.phar update-tools"

composer-audit: ## Check installed packages for security vulnerabilities
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer audit"

phar: ## Create PHAR archive of phpcpd and all its dependencies
	@make exec cmd="php phing.phar phar"

signed-phar: ## Create signed PHAR archive of PHPCPD and all its dependencies (release)
	@make exec cmd="php phing.phar signed-phar"

phpunit: ## Run the PHPUnit test suite
	@make exec-bash cmd="./vendor/bin/phpunit -c phpunit.xml.dist --coverage-html reports/coverage $(PHPUNIT_OPTIONS) --coverage-clover reports/clover.xml --log-junit reports/junit.xml"

phpcpd-run: ## Run PHP Copy/Paste Detector
	@make exec-bash cmd="php phpcpd --fuzzy --log-pmd=reports/phpcpd/phpcpd-report-v1.xml tests/Fixture"

phpcpd-html-report: ## Generate an HTML report for PHP Copy/Paste Detector
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@if [ ! -f reports/phpcpd/phpcpd-report-v1.xml ] ; then \
		printf "\033[32;49mreports/phpcpd/phpcpd-report-v1.xml not found, please run phpcpd.\033[39m\n" ; \
	else \
		printf "\033[32;49mCreating reports/phpcpd/phpcpd-report-v1.html report...\033[39m\n" ; \
		xalan -in reports/phpcpd/phpcpd-report-v1.xml -xsl https://systemsdk.github.io/phpcpd/report/phpcpd-html-v1_0_0.xslt -out reports/phpcpd/phpcpd-report-v1.html ; \
	fi;
else
	@make exec-bash cmd="make phpcpd-html-report"
endif

phpcpd-sarif-report: ## Generate an SARIF report for PHP Copy/Paste Detector
	@make exec-bash cmd="php phpcpd --log-sarif=reports/phpcpd/phpcpd-report.sarif src"

report-code-coverage: ## Update code coverage report on Coveralls.io (requires COVERALLS_REPO_TOKEN, should be set on CI side)
	@make exec-bash cmd="export COVERALLS_REPO_TOKEN=${COVERALLS_REPO_TOKEN} && php ./vendor/bin/php-coveralls -v --coverage_clover reports/clover.xml --json_path reports/coverals.json"

phpcs: ## Run PHP CodeSniffer checks
	@make exec-bash cmd="./vendor/bin/phpcs --version && ./vendor/bin/phpcs --standard=PSR12 --colors -p src tests/Integration"

ecs: ## Run Easy Coding Standard (ECS) checks
	@make exec-bash cmd="./vendor/bin/ecs --version && ./vendor/bin/ecs --clear-cache check src tests/Integration"

ecs-fix: ## Run Easy Coding Standard to automatically fix issues
	@make exec-bash cmd="./vendor/bin/ecs --version && ./vendor/bin/ecs --clear-cache --fix check src tests/Integration"

phpstan: ## Run PHPStan static analysis
ifeq ($(INSIDE_DOCKER_CONTAINER), 1)
	@echo "\033[32mRunning PHPStan - PHP Static Analysis Tool\033[39m"
	@./vendor/bin/phpstan --version
	@./vendor/bin/phpstan analyze src tests/Integration
else
	@make exec cmd="make phpstan"
endif
