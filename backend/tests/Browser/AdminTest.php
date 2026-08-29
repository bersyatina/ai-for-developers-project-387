<?php

namespace Tests\Browser;

use App\Models\Booking;
use App\Models\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_create_event_type(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin')
                ->waitForText('Создать тип звонка')
                ->type('input[name="title"]', 'Созвон')
                ->type('textarea[name="description"]', 'Обсуждение проекта')
                ->select('select[name="durationMinutes"]', '60')
                ->click('@create-type-submit')
                ->waitForText('Тип «Созвон» создан.');
        });
    }

    public function test_admin_sees_upcoming_bookings(): void
    {
        $eventType = EventType::factory()->create(['title' => 'Созвон']);
        $start = CarbonImmutable::tomorrow()->startOfDay()->addHours(11);

        Booking::factory()->create([
            'event_type_id' => $eventType->id,
            'start' => $start,
            'end' => $start->addMinutes(30),
            'guest_name' => 'Мария Смирнова',
            'guest_email' => 'maria@example.com',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/bookings')
                ->waitForText('Мария Смирнова')
                ->assertSee('maria@example.com');
        });
    }
}
