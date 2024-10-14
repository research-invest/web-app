init: up composer-install migrate seed
up:
	docker compose up -d
composer-install:
	docker compose run --rm app-php composer install
migrate:
	docker compose run --rm app-php php artisan migrate --force
q-restart:
	docker compose run --rm app-php php artisan queue:restart
migrate-down:
	docker compose run --rm app-php php artisan migrate:rollback --force
optimize:
	docker compose run --rm app-php php artisan optimize
view-clear:
	docker compose run --rm app-php php artisan view:clear
config-clear:
	docker compose run --rm app-php php artisan config:clear
seed:
	docker compose run --rm app-php php artisan db:seed
recreate-php:
	docker compose up -d --force-recreate --no-deps --build app-php
restart-php:
	docker compose restart app-php
restart-nginx:
	docker compose restart app-nginx
recreate-mysql:
	docker compose up -d --force-recreate --no-deps --build app-mysql
recreate-nginx:
	docker compose up -d --force-recreate --no-deps --build app-nginx
recreate-redis:
	docker compose up -d --force-recreate --no-deps --build app-redis
go-php:
	docker compose exec app-php bash
go-nginx:
	docker compose exec app-nginx bash
go-msql:
	docker compose exec app-mysql bash
docker-down-clear:
	docker compose down -v --remove-orphans
tests:
	docker compose run app-php php artisan test
