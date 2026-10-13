.DEFAULT_GOAL := help

PLUGIN_NAME := sensei-certificates
WP_ENV := COMPOSE_PROJECT_NAME=$(PLUGIN_NAME) npx --no-install wp-env
PHPUNIT_ENV_EXPORTS := export SENSEI_PLUGIN_DIR=/var/www/html/wp-content/plugins/sensei

up: ## Start the isolated WordPress development and test environments
	$(WP_ENV) start

down: ## Stop the isolated WordPress environments
	$(WP_ENV) stop

test-php: up ## Run the PHP integration suite
	$(WP_ENV) run tests-cli --env-cwd='wp-content/plugins/$(PLUGIN_NAME)' bash -c '$(PHPUNIT_ENV_EXPORTS) && vendor/bin/phpunit -c phpunit.xml'

test-php-filter: up ## Run selected tests (FILTER="TestClass::method")
	@[ -n "$(FILTER)" ] || { echo 'Set FILTER to a test class or method.'; exit 1; }
	$(WP_ENV) run tests-cli --env-cwd='wp-content/plugins/$(PLUGIN_NAME)' bash -c '$(PHPUNIT_ENV_EXPORTS) && vendor/bin/phpunit -c phpunit.xml --filter "$(FILTER)"'

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.PHONY: up down test-php test-php-filter help
