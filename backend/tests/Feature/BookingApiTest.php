<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_booking(): void
    {
        $eventType = EventType::factory()->create(['duration_minutes' => 30]);
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(10);

        $response = $this->postJson('/api/bookings', [
            'eventTypeId' => $eventType->id,
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'ivan@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('eventTypeId', $eventType->id)
            ->assertJsonPath('guestName', 'Иван')
            ->assertJsonPath('start', $start->toIso8601String())
            ->assertJsonPath('end', $start->addMinutes(30)->toIso8601String());

        $this->assertDatabaseHas('bookings', ['guest_name' => 'Иван', 'guest_email' => 'ivan@example.com']);
    }

    public function test_double_booking_same_time_is_rejected(): void
    {
        $typeA = EventType::factory()->create();
        $typeB = EventType::factory()->create();
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(10)->toIso8601String();

        $this->postJson('/api/bookings', [
            'eventTypeId' => $typeA->id,
            'start' => $start,
            'guestName' => 'A',
            'guestEmail' => 'a@example.com',
        ])->assertCreated();

        // Тот же слот, но другой тип события → конфликт
        $this->postJson('/api/bookings', [
            'eventTypeId' => $typeB->id,
            'start' => $start,
            'guestName' => 'B',
            'guestEmail' => 'b@example.com',
        ])->assertStatus(409)->assertJsonPath('message', 'Слот уже занят.');
    }

    public function test_booking_outside_window_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $start = CarbonImmutable::now()->addDays(20)->startOfDay()->addHours(10);

        $response = $this->postJson('/api/bookings', [
            'eventTypeId' => $eventType->id,
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'ivan@example.com',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Слот вне окна записи (ближайшие 14 дней).');
    }

    public function test_booking_in_past_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $start = CarbonImmutable::yesterday()->startOfDay()->addHours(10);

        $response = $this->postJson('/api/bookings', [
            'eventTypeId' => $eventType->id,
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'ivan@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_booking_not_aligned_to_30_minutes_is_rejected(): void
    {
        $eventType = EventType::factory()->create();
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(10)->addMinutes(15);

        $response = $this->postJson('/api/bookings', [
            'eventTypeId' => $eventType->id,
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'ivan@example.com',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Время начала должно быть кратно 30 минутам.');
    }

    public function test_owner_can_list_upcoming_bookings(): void
    {
        $eventType = EventType::factory()->create();

        Booking::factory()->create([
            'event_type_id' => $eventType->id,
            'start' => CarbonImmutable::now()->subDay()->startOfDay()->addHours(10),
            'end' => CarbonImmutable::now()->subDay()->startOfDay()->addHours(10)->addMinutes(30),
        ]);

        $futureStart = CarbonImmutable::tomorrow()->startOfDay()->addHours(9);
        Booking::factory()->create([
            'event_type_id' => $eventType->id,
            'start' => $futureStart,
            'end' => $futureStart->addMinutes(30),
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.eventTypeId', $eventType->id);
    }

    public function test_booking_requires_event_type_id(): void
    {
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(10);

        $response = $this->postJson('/api/bookings', [
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'ivan@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('eventTypeId');
    }

    public function test_booking_requires_valid_email(): void
    {
        $eventType = EventType::factory()->create();
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(10);

        $response = $this->postJson('/api/bookings', [
            'eventTypeId' => $eventType->id,
            'start' => $start->toIso8601String(),
            'guestName' => 'Иван',
            'guestEmail' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('guestEmail');
    }
}
