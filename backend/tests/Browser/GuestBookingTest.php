<?php

namespace Tests\Browser;

use App\Models\EventType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GuestBookingTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_guest_can_book_a_slot(): void
    {
        EventType::factory()->create([
            'title' => 'Созвон',
            'description' => 'Обсуждение проекта',
            'duration_minutes' => 30,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('Созвон')
                ->click('@book-link')
                ->waitFor('[data-testid="date-pill"]')
                ->click('[data-testid="date-pill"]:nth-child(2)') // завтра — 48 слотов
                ->waitFor('[data-testid="slot"]')
                ->click('[data-testid="slot"]')
                ->type('input[name="guest_name"]', 'Иван Петров')
                ->type('input[name="guest_email"]', 'ivan@example.com')
                ->click('@book-submit')
                ->waitForText('Запись подтверждена')
                ->assertSee('Иван Петров');
        });
    }

    public function test_guest_sees_error_on_invalid_email(): void
    {
        EventType::factory()->create(['title' => 'Созвон']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('Созвон')
                ->click('@book-link')
                ->waitFor('[data-testid="date-pill"]')
                ->click('[data-testid="date-pill"]:nth-child(2)')
                ->waitFor('[data-testid="slot"]')
                ->click('[data-testid="slot"]')
                ->type('input[name="guest_name"]', 'Иван Петров')
                ->type('input[name="guest_email"]', 'not-an-email')
                ->click('@book-submit')
                ->waitForText('The guest email field must be a valid email address.');
        });
    }
}
