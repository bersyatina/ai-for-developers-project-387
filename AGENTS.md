# AGENTS.md

Проект «Календарь звонков» (Call Calendar) — учебный проект Hexlet (Design First). Упрощённый аналог Cal.com: владелец публикует типы событий, гость выбирает свободный 30-минутный слот и записывается на звонок.

Стек: **Laravel 13 (PHP 8.3) API-only + Vue 3 SPA (Vite) + Tailwind 4 + MySQL**. Финальный деплой — Docker (Dockerfile + docker-compose). Локальная разработка — Windows/OSPanel (PHP 8.3, MySQL). Русский используется в комментариях, коммитах и аннотациях — придерживайся этого стиля.

## Структура (monorepo)

```
.
├── AGENTS.md
├── .opencode/skills/     # скиллы: vue-spa, laravel-api, laravel-security, laravel-testing
├── spec/                 # TypeSpec API-контракт → spec/openapi.yaml (единый источник правды)
├── backend/              # Laravel 13 (JSON API, без веб-страниц)
├── frontend/             # Vue 3 + Vite + Tailwind (SPA, отдельное приложение)
├── docker/               # Dockerfile'ы
├── docker-compose.yml
└── Makefile              # хаб команд (создать при старте реализации)
```

## Design First / API-контракт

- `spec/main.tsp` (TypeSpec) — источник правды. `tsp compile` генерирует `spec/openapi.yaml`.
- **Любое изменение поведения сначала вносится в TypeSpec-контракт**, затем синхронно в backend и frontend.
- Backend и frontend реализуются независимо, строго по `spec/openapi.yaml`. Не добавлять эндпоинты/поля вне контракта.

## Доменные правила

- **Нет авторизации и регистрации.** Владелец — один предустановленный профиль, админ-часть доступна по маршруту `/admin` без входа. Гость бронирует без аккаунта.
- `EventType`: `id`, `title`, `description`, `durationMinutes`.
- `Slot`: 30 минут, **24/7**, окно — ближайшие **14 дней** от текущей даты.
- `Booking`: `id`, `eventTypeId`, `start`, `end`, `guestName`, `guestEmail`.
- **Правило занятости:** на одно время нельзя две брони, даже для разных типов событий (уникальность по `start`).
- Владелец: создаёт типы событий, видит список предстоящих встреч всех типов.
- Гость: видит типы событий, выбирает тип → дату → свободный слот → бронирует.

## Эндпоинты API (см. spec/openapi.yaml)

- `GET /api/event-types` — список типов (гость)
- `POST /api/event-types` — создать тип (владелец)
- `GET /api/event-types/{id}/slots?date=YYYY-MM-DD` — свободные слоты дня (гость)
- `POST /api/bookings` — создать бронь (гость)
- `GET /api/bookings` — предстоящие встречи (владелец)

## Локальная среда

- PHP 8.3 (OSPanel): `C:\OSPanelNew\modules\PHP-8.3-FCGI\PHP\php.exe`, Composer 2.4, Node 22, npm 11.
- MySQL запущен в OSPanel (host `127.0.0.1`). Backend: `backend/.env` → `DB_DATABASE=call_calendar`; тесты → `call_calendar_test` (в `.env.testing`).
- **Docker Desktop НЕ установлен** на этой машине. Понадобится только на шаге деплоя (сборка образа, `docker compose up`).

## Команды (Makefile — создать на старте реализации)

- `make test` — `php artisan test` (PHPUnit, backend)
- `make dusk` — Laravel Dusk (браузерные сценарии, локально)
- `make build` / `make dev` — `npm run build` / `vite` (frontend)
- `make migrate` — `php artisan migrate`
- `make tsp` — `tsp compile spec` → `spec/openapi.yaml`

## Правила разработки

- **Серверная валидация обязательна.** Любой приём данных от клиента (API, форма) — через `FormRequest` или `$request->validate()`. Клиентская валидация — только UX-подсказка.
- **Массовое присвоение:** все модели используют `$fillable` (или `$guarded`), не оставлять пустым.
- **Формат API:** `response()->json(...)`; ошибки валидации — автоматический `422` с `{ errors: [...] }`; конфликт времени брони — `409`; вне окна записи — `422`.
- **Rate limiting:** публичные эндпоинты (`slots`, `POST /bookings`) — `throttle:N` в роуте или `RateLimiter::for()` в `bootstrap/app.php`.
- **Тестирование обязательно.** Новый функционал покрывается тестами вместе с кодом (PHPUnit — контроллеры/сервисы/валидация; Dusk — сквозные браузерные сценарии).
- Атомарность брони: транзакция + unique-индекс на `bookings.start` (защита от гонки).

## Тестирование

- PHPUnit: `tests/Feature/` (API-сценарии через `getJson`/`postJson`), `RefreshDatabase` против `call_calendar_test`.
- Laravel Dusk: `tests/Browser/`. Требует Chrome + ChromeDriver. Конфигурация в `.env.dusk.local` (локально, в `.gitignore`). Подробнее — в скилле `laravel-testing`.

## Gotchas

- `.env`, `.env.testing`, `.env.dusk.local` — в `.gitignore`, не коммитить.
- Не добавлять Sanctum/авторизацию/регистрацию — в проекте их нет по условию.
- Docker-образ должен собираться и запускаться в контейнере (требование курса). Dusk — только локально, в образ не входит.
- Dusk (браузерные тесты) в этом стеке направлен на **frontend SPA**: backend поднимается отдельно (`php artisan serve`), SPA — через Vite (или собранный билд), `APP_URL` указывает на SPA. Точные команды зафиксировать в Makefile при реализации.
- Pint (`laravel/pint`) — дефолтный пресет Laravel.

## Деплой (Docker)

- **Корневой `Dockerfile`** — единый самодостаточный образ (SPA + API), слушает порт из env `PORT` (дефолт 8000), БД — SQLite в контейнере. Используется hexlet-проверкой (`docker build -t calendar-slot-code:local -f Dockerfile .`) и деплоем на Railway.
- `docker/Dockerfile` (backend: php-fpm + nginx) и `docker/Dockerfile.frontend` (nginx, раздаёт собранный SPA, проксирует `/api` → backend) — для локального docker-compose.
- `docker-compose.yml`: backend, frontend/nginx, mysql. Миграции — при старте (entrypoint).
- **Деплой на Railway:** публичная ссылка — `https://call-calendar-production.up.railway.app` (см. README). SQLite в контейнере, данные сбрасываются при пересборке (не использовать Railway Volume для SQLite — сетевой volume ломает блокировки SQLite → 500).
- Проверка: `docker compose up --build` (локально), либо `docker build -f Dockerfile .` + запуск с `-e PORT`.
