<?php

namespace Database\Factories;

use App\Models\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventType>
 */
class EventTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'duration_minutes' => 30,
        ];
    }
}
