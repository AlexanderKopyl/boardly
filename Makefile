.PHONY: install up down restart shell console test phpstan cs-fix rector qa logs infra-up infra-down infra-restart serve local-install local-console local-test local-phpstan local-cs-fix local-rector local-qa

install:
	docker compose build
	docker compose run --rm app composer install

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose down
	docker compose up -d

shell:
	docker compose exec app sh

console:
	docker compose exec app php bin/console

test:
	docker compose exec app vendor/bin/phpunit

phpstan:
	docker compose exec app vendor/bin/phpstan analyse

cs-fix:
	docker compose exec app vendor/bin/php-cs-fixer fix --diff

rector:
	docker compose exec app vendor/bin/rector process

qa: test phpstan

logs:
	docker compose logs -f

infra-up:
	docker compose up -d postgres redis rabbitmq opensearch

infra-down:
	docker compose stop postgres redis rabbitmq opensearch

infra-restart:
	docker compose restart postgres redis rabbitmq opensearch

serve:
	php -S 127.0.0.1:8080 -t public

local-install:
	composer install

local-console:
	php bin/console

local-test:
	vendor/bin/phpunit

local-phpstan:
	vendor/bin/phpstan analyse

local-cs-fix:
	vendor/bin/php-cs-fixer fix --diff

local-rector:
	vendor/bin/rector process

local-qa: local-test local-phpstan
