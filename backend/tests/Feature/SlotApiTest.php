<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_slots_returned_for_date_in_window(): void
    {
        $eventType = EventType::factory()->create();
        $tomorrow = CarbonImmutable::tomorrow()->toDateString();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date={$tomorrow}");

        $response->assertOk()
            ->assertJsonCount(48) // 24 часа × 2 слота по 30 минут
            ->assertJsonStructure([['start', 'end', 'available']]);
    }

    public function test_booked_slot_is_excluded(): void
    {
        $eventType = EventType::factory()->create();
        $tomorrow = CarbonImmutable::tomorrow();

        $bookedStart = $tomorrow->startOfDay()->addHours(10);
        Booking::factory()->create([
            'event_type_id' => $eventType->id,
            'start' => $bookedStart,
            'end' => $bookedStart->addMinutes(30),
        ]);

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date={$tomorrow->toDateString()}");

        $response->assertOk();
        $starts = collect($response->json())->pluck('start');

        $this->assertFalse($starts->contains($bookedStart->toIso8601String()));
        $this->assertTrue($starts->contains($bookedStart->addMinutes(30)->toIso8601String()));
    }

    public function test_date_outside_window_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $tooFar = CarbonImmutable::today()->addDays(14)->toDateString();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date={$tooFar}");

        $response->assertStatus(422)->assertJsonPath('message', 'Дата вне окна записи (ближайшие 14 дней).');
    }

    public function test_date_in_past_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $yesterday = CarbonImmutable::yesterday()->toDateString();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date={$yesterday}");

        $response->assertStatus(422);
    }

    public function test_today_returns_only_future_slots(): void
    {
        CarbonImmutable::setTestNow('2026-01-01 14:00:00');

        $eventType = EventType::factory()->create();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date=2026-01-01");

        $response->assertOk();

        $starts = collect($response->json())->pluck('start');

        // Ни один слот не должен быть раньше текущего момента.
        $this->assertTrue($starts->every(
            fn (string $iso) => CarbonImmutable::parse($iso)->gt(CarbonImmutable::now())
        ));
        // Первый слот — ближайшая 30-минутная граница после 14:00.
        $this->assertSame('2026-01-01T14:30:00+00:00', $starts->first());
        // Прошедшие слоты текущего дня исключены — слотов меньше 48.
        $this->assertCount(19, $starts);
    }

    public function test_today_slots_respect_client_timezone(): void
    {
        // Серверное время 22:30 UTC — у клиента (UTC+3) уже 2026-01-02 01:30.
        CarbonImmutable::setTestNow('2026-01-01 22:30:00');

        $eventType = EventType::factory()->create();

        $response = $this->getJson(
            "/api/event-types/{$eventType->id}/slots?date=2026-01-02&tz=Europe/Moscow"
        );

        $response->assertOk();

        $starts = collect($response->json())->pluck('start');

        $clientNow = CarbonImmutable::now('Europe/Moscow');
        // Граница «сейчас» учитывает таймзону клиента.
        $this->assertTrue($starts->every(
            fn (string $iso) => CarbonImmutable::parse($iso)->gt($clientNow)
        ));
        // Первый слот — ближайшая 30-минутная граница после 01:30 по клиенту.
        $this->assertSame('2026-01-02T02:00:00+03:00', $starts->first());
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $today = CarbonImmutable::now()->toDateString();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots?date={$today}&tz=Not/AZone");

        $response->assertStatus(422)->assertJsonValidationErrors('tz');
    }

    public function test_date_is_required(): void
    {
        $eventType = EventType::factory()->create();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots");

        $response->assertStatus(422)->assertJsonValidationErrors('date');
    }
}
