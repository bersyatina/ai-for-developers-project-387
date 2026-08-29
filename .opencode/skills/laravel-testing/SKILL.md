---
name: laravel-testing
description: Use ONLY when writing or modifying tests, discussing test strategy, or when asked to verify functionality with tests. Covers PHPUnit feature tests (getJson/postJson) for API controllers/services/validation, Laravel Dusk browser tests for the SPA, RefreshDatabase vs DatabaseTransactions, test DB setup (call_calendar_test), and the mandatory test requirement. Gate/front-load: тест, test, testing, PHPUnit, Dusk, проверка, verify, assert, RefreshDatabase, DatabaseTransactions, getJson, postJson.
---

# Laravel Testing (backend/)

**Сверяйся с `AGENTS.md` для правил тестирования, Makefile и gotchas.** Этот навык — о том, как писать тесты в `backend/`. Проект: API-only backend + Vue SPA. Одна MySQL-БД, Dusk (браузерные тесты) — направлен на frontend SPA.

---

## 1. Обязательное правило

**Новый функционал всегда должен покрываться тестами**, если это технически возможно (контроллеры, сервисы, модели, валидация). Тесты пишутся вместе с кодом, не отдельным PR.

## 2. Два вида тестов

| Вид | Инструмент | Где лежат | Когда использовать |
|---|---|---|---|
| Unit / Feature | PHPUnit | `backend/tests/Unit/`, `backend/tests/Feature/` | Контроллеры, сервисы, валидация, API-эндпоинты |
| Browser | Laravel Dusk | `backend/tests/Browser/` | Сквозные сценарии через браузер (открыть SPA, выбрать тип, забронировать слот, проверить админ-список) |

## 3. PHPUnit: паттерны

### 3.1 Feature-тесты API (основной вид)

Наследуются от `Tests\TestCase`, используют `RefreshDatabase` (одна БД — `call_calendar_test` из `.env.testing`):

```php
class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_booking(): void
    {
        $type = EventType::factory()->create(['duration_minutes' => 30]);

        $response = $this->postJson('/api/bookings', [
            'event_type_id' => $type->id,
            'start'         => now()->startOfDay()->addHours(10)->toIso8601String(),
            'guest_name'    => 'Иван',
            'guest_email'   => 'ivan@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.event_type_id', $type->id);
    }

    public function test_double_booking_same_time_is_rejected(): void
    {
        $typeA = EventType::factory()->create();
        $typeB = EventType::factory()->create();
        $start = now()->startOfDay()->addHours(10)->toIso8601String();

        $this->postJson('/api/bookings', [
            'event_type_id' => $typeA->id, 'start' => $start,
            'guest_name' => 'A', 'guest_email' => 'a@example.com',
        ])->assertCreated();

        // тот же слот, другой тип → 409
        $this->postJson('/api/bookings', [
            'event_type_id' => $typeB->id, 'start' => $start,
            'guest_name' => 'B', 'guest_email' => 'b@example.com',
        ])->assertStatus(409);
    }

    public function test_booking_outside_window_is_rejected(): void
    {
        $type = EventType::factory()->create();
        $start = now()->addDays(20)->startOfDay()->addHours(10)->toIso8601String();

        $this->postJson('/api/bookings', [
            'event_type_id' => $type->id, 'start' => $start,
            'guest_name' => 'Иван', 'guest_email' => 'ivan@example.com',
        ])->assertStatus(422);
    }
}
```

```php
class SlotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_listed_for_date_in_window(): void
    {
        $type = EventType::factory()->create();
        $date = now()->toDateString();

        $this->getJson("/api/event-types/{$type->id}/slots?date={$date}")
            ->assertOk()
            ->assertJsonCount(48, 'data'); // 24 часа × 2 слота по 30 минут
    }
}
```

### 3.2 Unit-тесты (без БД)

Наследуются от `PHPUnit\Framework\TestCase`. Для чистых вычислений (например, генерация слотов `SlotService` с замоканным `now()`).

## 4. RefreshDatabase vs DatabaseTransactions

| Trait | Когда использовать |
|---|---|
| `RefreshDatabase` | Стандарт: мигрирует `call_calendar_test` один раз на тестовый запуск |
| `DatabaseTransactions` | Скорость (не пересоздаёт схему), когда БД уже замигрирована |

Одна БД, поэтому `RefreshDatabase` достаточно — без multi-DB хитростей.

## 5. Laravel Dusk (браузерные тесты SPA)

Dusk тестирует **frontend SPA** (сквозные пользовательские сценарии), а backend работает как API.

**Настройка:**
1. `composer require --dev laravel/dusk` в `backend/`; `php artisan dusk:install`.
2. ChromeDriver качается `php artisan dusk:chrome-driver`. Chrome установлен: `C:\Program Files\Google\Chrome\Application\chrome.exe`.
3. Конфигурация `.env.dusk.local` (в `.gitignore`): `APP_URL` → адрес SPA (например `http://127.0.0.1:5173`), БД — та же тестовая.
4. Перед запуском поднять backend (`php artisan serve --env=dusk.local`) и SPA (Vite dev-сервер). Точные команды — в `Makefile` (`make dusk`).

```php
class GuestBookingTest extends DuskTestCase
{
    public function test_guest_books_a_slot(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('Созвон')
                ->clickLink('Созвон')
                ->waitForText('Выберите слот')
                ->click('[data-testid="slot-10-00"]')
                ->type('input[name="guest_name"]', 'Иван')
                ->type('input[name="guest_email"]', 'ivan@example.com')
                ->press('Записаться')
                ->waitForText('Запись подтверждена');
        });
    }
}
```

**Важно:** для стабильности — `data-testid`-атрибуты на ключевых элементах SPA (слоты, кнопки форм).

## 6. Запуск тестов

```bash
# из корня проекта
make test   # php --version из backend: php artisan test --env=testing
make dusk   # поднять backend + SPA, затем php artisan dusk --env=dusk.local

# конкретный набор
php artisan test --env=testing --filter="Booking"
```

## 7. Фабрики моделей

Использовать встроенные фабрики Laravel (`database/factories/`):

```php
$type = EventType::factory()->create(['duration_minutes' => 30]);
```

Если нужной нет — создать.
