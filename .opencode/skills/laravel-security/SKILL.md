---
name: laravel-security
description: Use ONLY when implementing server-side validation, mass-assignment protection, rate limiting, business-rule checks, or security-related code in this Laravel project. Covers FormRequest, $request->validate(), $fillable/$guarded, throttle middleware, RateLimiter::for(), unique booking constraint, and JSON error formats (422/409). Gate/front-load: validate, validation, security, CSRF, authorize, throttle, rate-limit, fillable, guarded, FormRequest, 422, 409, conflict, rate.
---

# Laravel Security & Validation (backend/)

**Всегда сверяйся с `AGENTS.md` — там правила разработки, доменные ограничения и gotchas.** Этот навык — практическое руководство по безопасности и валидации в `backend/`. Проект без авторизации: нет Sanctum, нет `auth`-middleware, нет CSRF-защиты для API. Фокус — валидация, лимиты и защита от гонок.

---

## 1. Валидация: основной паттерн — FormRequest

API-проект, поэтому валидацию держим в FormRequest (`app/Http/Requests/Api/`):

```php
// app/Http/Requests/Api/StoreEventTypeRequest.php
public function authorize(): bool
{
    return true; // нет авторизации, владелец предустановлен
}

public function rules(): array
{
    return [
        'title'           => ['required', 'string', 'max:255'],
        'description'     => ['nullable', 'string', 'max:1000'],
        'duration_minutes' => ['required', 'integer', 'in:15,30,60'], // по контракту
    ];
}
```

Инлайн `$request->validate()` допустим для простых случаев (см. `SlotController` — валидация query `date`).

## 2. JSON-формат ошибок

Laravel сам вернёт `422` с `{ message, errors }`, если клиент шлёт `Accept: application/json` (SPA всегда так делает).

Кастомные бизнес-ошибки — явно:

```php
// слот занят (конфликт времени)
return response()->json(['message' => 'Слот уже занят'], 409);

// вне окна записи (14 дней от текущей даты)
return response()->json(['message' => 'Слот вне окна записи'], 422);

// не кратно 30 минутам / день не в окне
return response()->json(['message' => 'Недопустимое время начала'], 422);
```

## 3. Mass Assignment

**Все модели обязаны иметь `$fillable` (или `$guarded`).** Не оставлять пустым:

```php
class Booking extends Model
{
    protected $fillable = ['event_type_id', 'start', 'end', 'guest_name', 'guest_email'];
}
```

## 4. Rate Limiting

Публичные эндпоинты (гость без входа) — обязательно throttle:

```php
// в routes/api.php (простой вариант)
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:30,1');

// кастомный (bootstrap/app.php)
RateLimiter::for('slots', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});
Route::get('/event-types/{eventType}/slots', [SlotController::class, 'index'])->middleware('throttle:slots');
```

## 5. Бизнес-правило: уникальность времени брони (гонка)

Два гостя не могут забронировать одно время — даже для разных типов событий:

```php
// 1) База: уникальный индекс на bookings.start (миграция)
Schema::table('bookings', function (Blueprint $table) {
    $table->dateTime('start')->unique();
});

// 2) Сервис: транзакция + ловля дубля
DB::transaction(function () use ($validated, $eventType) {
    return Booking::create([... 'start' => $validated['start'], 'end' => ...]);
}, 3); // повтор при deadlock

try {
    // создание
} catch (QueryException $e) {
    if ((int) $e->errorInfo[1] === 1062) { // MySQL duplicate entry
        return response()->json(['message' => 'Слот уже занят'], 409);
    }
    throw $e;
}
```

Перед вставкой дополнительно проверяем «мягко»: `Booking::where('start', $start)->exists()` (быстрый отказ), но финальная гарантия — unique-индекс.

## 6. Защита времени (окно 14 дней)

- `start` должен быть в `[now(), now()->addDays(14)]` и кратным 30 минутам — проверка в `BookingService`/FormRequest, иначе `422`.

## 7. Чего в проекте НЕТ

- **Нет** Sanctum, `auth:sanctum`, `verified`, логинов/токенов — по условию проекта.
- **Нет** CSRF-токенов в API — приложение stateless, защита от CSRF не требуется (это не cookie-based сессии).
