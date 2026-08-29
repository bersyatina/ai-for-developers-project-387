<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_type_id' => EventType::factory(),
            'start' => now()->addDays(1)->startOfDay()->addHours(10),
            'end' => now()->addDays(1)->startOfDay()->addHours(10)->addMinutes(30),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
        ];
    }
}
