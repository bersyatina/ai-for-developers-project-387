<?php

namespace Tests\Feature;

use App\Models\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_event_types(): void
    {
        EventType::factory()->count(2)->create();

        $response = $this->getJson('/api/event-types');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([[
                'id',
                'title',
                'description',
                'durationMinutes',
            ]]);
    }

    public function test_owner_can_create_event_type(): void
    {
        $response = $this->postJson('/api/event-types', [
            'title' => 'Созвон',
            'description' => 'Обсуждение проекта',
            'durationMinutes' => 30,
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Созвон')
            ->assertJsonPath('durationMinutes', 30);

        $this->assertDatabaseHas('event_types', ['title' => 'Созвон', 'duration_minutes' => 30]);
    }

    public function test_description_is_optional(): void
    {
        $response = $this->postJson('/api/event-types', [
            'title' => 'Без описания',
            'durationMinutes' => 30,
        ]);

        $response->assertCreated()->assertJsonPath('description', '');
    }

    public function test_create_event_type_requires_duration_multiple_of_30(): void
    {
        $response = $this->postJson('/api/event-types', [
            'title' => 'Созвон',
            'durationMinutes' => 15,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('durationMinutes');
    }

    public function test_create_event_type_requires_title(): void
    {
        $response = $this->postJson('/api/event-types', [
            'durationMinutes' => 30,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }
}
