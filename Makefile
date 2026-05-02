.PHONY: install up down restart shell console test phpstan cs-fix rector qa logs

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
