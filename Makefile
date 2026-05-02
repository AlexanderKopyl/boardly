.PHONY: install up down restart shell console test phpstan cs-fix rector qa logs infra-up infra-down infra-restart serve local-install local-console local-test local-phpstan local-cs-fix local-rector local-qa sf about cc warmup routes router container autowiring env db-create db-migrate db-diff db-status messenger-consume messenger-failed-show messenger-failed-retry messenger-failed-remove

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

sf:
	php bin/console $(cmd)

about:
	php bin/console about

cc:
	php bin/console cache:clear

warmup:
	php bin/console cache:warmup

routes:
	php bin/console debug:router

router: routes

container:
	php bin/console debug:container

autowiring:
	php bin/console debug:autowiring

env:
	php bin/console debug:dotenv

db-create:
	php bin/console doctrine:database:create --if-not-exists

db-migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

db-diff:
	php bin/console doctrine:migrations:diff

db-status:
	php bin/console doctrine:migrations:status

messenger-consume:
	php bin/console messenger:consume async -vv

messenger-failed-show:
	php bin/console messenger:failed:show

messenger-failed-retry:
	php bin/console messenger:failed:retry

messenger-failed-remove:
	php bin/console messenger:failed:remove
