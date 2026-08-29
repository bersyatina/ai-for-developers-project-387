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

    public function test_date_is_required(): void
    {
        $eventType = EventType::factory()->create();

        $response = $this->getJson("/api/event-types/{$eventType->id}/slots");

        $response->assertStatus(422)->assertJsonValidationErrors('date');
    }
}
