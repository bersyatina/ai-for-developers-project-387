---
name: laravel-api
description: Use ONLY when building or modifying API endpoints, controllers, routes/api.php, request/response formats, or ensuring compliance with the OpenAPI contract in this project. Covers backend/ structure, app/Http/Controllers/Api/*, FormRequests, Resources, JSON response format, and the spec/openapi.yaml contract (Design First).
---

# Laravel API (backend/)

**Сверяйся с `AGENTS.md` для правил разработки, доменных ограничений и контракта.** Этот навык — о построении JSON API в `backend/`. Backend — отдельное приложение (без веб-страниц), общается только через HTTP API по контракту `spec/openapi.yaml`.

---

## 1. Структура

| Директория | Назначение |
|---|---|
| `backend/routes/api.php` | Все API-роуты (префикс `/api`) |
| `backend/app/Http/Controllers/Api/` | `EventTypeController`, `SlotController`, `BookingController` |
| `backend/app/Http/Requests/Api/` | FormRequest (валидация входных данных) |
| `backend/app/Http/Resources/` | JsonResource (формат выходных данных) |
| `backend/app/Models/` | `EventType`, `Booking` |
| `backend/app/Services/` | `SlotService` (генерация слотов), `BookingService` (создание брони) |
| `spec/openapi.yaml` | **Контракт** — единый источник правды (из TypeSpec) |

## 2. Роуты (routes/api.php)

В проекте нет авторизации — роуты публичные, без `auth` middleware:

```php
Route::get('/event-types', [EventTypeController::class, 'index']);
Route::post('/event-types', [EventTypeController::class, 'store']);

Route::get('/event-types/{eventType}/slots', [SlotController::class, 'index']);

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings', [BookingController::class, 'index']);
```

Публичные эндпоинты с приёмом данных (`POST /bookings`, `GET slots`) — через `throttle` (см. `laravel-security`).

## 3. Формат ответов

```php
// ✅ Успех (список)
return EventTypeResource::collection($eventTypes);

// ✅ Успех (один объект)
return new BookingResource($booking); // 201 для POST

// ✅ Ошибка валидации (Laravel автоматически, Accept: application/json)
// 422 → { "message": ..., "errors": { "start": [...] } }

// ✅ Бизнес-ошибка: слот занят / вне окна записи
return response()->json(['message' => 'Слот уже занят'], 409);
return response()->json(['message' => 'Слот вне окна записи (14 дней)'], 422);
```

Всегда возвращай `JsonResponse`/Resource, не строки и не шаблоны.

## 4. Валидация

Форматы входных данных строго по `spec/openapi.yaml`. Валидация — в FormRequest:

```php
// app/Http/Requests/Api/StoreBookingRequest.php
public function rules(): array
{
    return [
        'event_type_id' => ['required', 'integer', 'exists:event_types,id'],
        'start'         => ['required', 'date_format:Y-m-d\TH:i:sP'], // ISO 8601 из контракта
        'guest_name'    => ['required', 'string', 'max:255'],
        'guest_email'   => ['required', 'email', 'max:255'],
    ];
}
```

## 5. Слоты (SlotService)

- Генерирует 30-минутные слоты, **24/7**, на окно **14 дней** от текущей даты.
- `GET /api/event-types/{id}/slots?date=YYYY-MM-DD` возвращает только свободные слоты выбранного дня.
- Слот занят, если существует `Booking` с пересечением времени (независимо от типа события).
- В ответе — слоты с `start`, `end`, `available: true`.

## 6. Брони (BookingService)

- Проверки: тип события существует; `start` внутри 14-дневного окна; кратно 30 минутам; на это время нет другой брони.
- Создание атомарно: **транзакция** + полагаемся на **unique-индекс** `bookings.start` (защита от гонки). При `QueryException` дубля — вернуть `409`.
- `end` = `start + durationMinutes` типа события.

## 7. Контракт (Design First)

- Перед изменением эндпоинта/полей — сначала обновить `spec/main.tsp` (TypeSpec), сгенерировать `spec/openapi.yaml` (`make tsp`), и только потом менять backend и frontend.
- Backend не должен отдавать поля, которых нет в контракте.
