.PHONY: setup install test lint start stop build dev migrate tsp dusk

## Установка зависимостей (composer + npm) — вызывается hexlet-проверкой
setup:
	cd backend && composer install --no-interaction --prefer-dist
	cd frontend && npm ci

install: setup

## Backend: PHPUnit feature-тесты
test:
	cd backend && php artisan test

## Backend: линтер (Laravel Pint)
lint:
	cd backend && vendor/bin/pint --test

## Запуск стека в Docker
start:
	docker compose up --build -d

## Остановка стека
stop:
	docker compose down

## Frontend (сборка в dist/)
build:
	cd frontend && npm run build

## Frontend dev-сервер (проксирует /api на backend, порт 8000)
dev:
	cd frontend && npm run dev

## Миграции backend
migrate:
	cd backend && php artisan migrate

## TypeSpec → spec/openapi.yaml
tsp:
	cd spec && npx tsp compile .

## Dusk: браузерные тесты (backend + Vite поднимаются автоматически)
dusk:
	bash scripts/dusk.sh
