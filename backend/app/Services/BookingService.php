<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Создание брони: проверка бизнес-правил и атомарная вставка.
 */
class BookingService
{
    public function __construct(private readonly SlotService $slots) {}

    /**
     * @param  array{eventTypeId: string, start: string, guestName: string, guestEmail: string}  $validated
     */
    public function create(array $validated): Booking
    {
        $eventType = EventType::findOrFail($validated['eventTypeId']);
        $start = CarbonImmutable::parse($validated['start']);

        $this->slots->assertValidStart($start);

        if ($this->isOccupied($start, $eventType)) {
            throw new BookingConflictException('Слот уже занят.');
        }

        try {
            return DB::transaction(fn () => Booking::create([
                'event_type_id' => $eventType->id,
                'start' => $start,
                'end' => $start->addMinutes($eventType->duration_minutes),
                'guest_name' => $validated['guestName'],
                'guest_email' => $validated['guestEmail'],
            ]));
        } catch (UniqueConstraintViolationException $e) {
            // Защита от гонки: unique-индекс на bookings.start (MySQL и SQLite)
            throw new BookingConflictException('Слот уже занят.');
        }
    }

    /**
     * Мягкая проверка: пересечение брони с [start, start+duration) существующей брони.
     */
    private function isOccupied(CarbonImmutable $start, EventType $eventType): bool
    {
        $end = $start->addMinutes($eventType->duration_minutes);

        return Booking::where('start', '<', $end)
            ->where('end', '>', $start)
            ->exists();
    }
}
