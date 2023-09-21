#=========================================================#
#  GNU Makefile #
#=========================================================#
.DEFAULT_GOAL := help

# Define globals  #
##======================##
CONSOLE=bin/console --env=dev
CONSOLE_TEST=bin/console --env=test

DC =docker compose -f docker-compose.yml -f docker-compose.dev.yml
EXEC = $(DC) exec app_price_request
COMPOSER = $(EXEC) composer


# Define application commands  #
##============================##
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "%-20s %s\n", $$1, $$2}'

# Common
build:
	@$(DC) build

composer:
	@$(COMPOSER) install --optimize-autoloader

up:
	@$(DC) up -d --remove-orphans --no-recreate

down:
	@$(DC) stop
	@$(DC) rm -v --force

app:
	@$(EXEC) sh

# Tests, security checks, coverage and CI

check-security: ## check-security
	@$(EXEC) local-php-security-checker security:check

phpcbf: ## Run phpcbf
	@$(EXEC) ./vendor/bin/phpcbf --standard=PSR12 src tests

phpcs: ## Run php code sniffer
	$(EXEC) ./vendor/bin/php-cs-fixer fix src
	$(EXEC) ./vendor/bin/php-cs-fixer fix tests

phpcsfixer: ## Run php code sniffer
	@$(EXEC) /srv/app/vendor/bin/php-cs-fixer fix src --dry-run
	@$(EXEC) /srv/app/vendor/bin/php-cs-fixer fix tests --dry-run

phpstan:
	$(EXEC) ./vendor/bin/phpstan analyse src --level=5

behat: ## Run behat tests
	$(EXEC) ./vendor/bin/behat -vvv --no-snippets

phpunit: ## Run phpunit tests
	$(EXEC) ./vendor/bin/phpunit tests

ci: ## Run all check scripts
ci: phpcs phpstan check-security phpunit behat