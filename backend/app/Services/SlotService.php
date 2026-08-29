<?php

namespace App\Services;

use App\Exceptions\InvalidSlotException;
use App\Models\Booking;
use App\Models\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Генерация свободных 30-минутных слотов (24/7) в пределах окна 14 дней.
 * В ответе — только свободные слоты выбранного дня.
 */
class SlotService
{
    public const SLOT_MINUTES = 30;

    public const WINDOW_DAYS = 14;

    /** Количество 30-минутных слотов в сутках (24 часа). */
    public const SLOTS_PER_DAY = 24 * 60 / self::SLOT_MINUTES;

    /**
     * @return array<int, array{start: string, end: string, available: bool}>
     */
    public function availableSlots(EventType $eventType, CarbonImmutable $date, string $timezone = 'UTC'): array
    {
        $this->assertDateInWindow($date, $timezone);

        $slots = $this->generateSlots($date, $timezone);

        $occupied = $this->occupiedIntervalsForDay($date);

        $result = [];
        foreach ($slots as $slot) {
            $isFree = ! $occupied->contains(
                fn (array $booking) => $booking['start']->lt($slot['end']) && $booking['end']->gt($slot['start'])
            );

            if ($isFree) {
                $result[] = [
                    'start' => $slot['start']->toIso8601String(),
                    'end' => $slot['end']->toIso8601String(),
                    'available' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Дата должна входить в окно записи: от сегодня до сегодня + 13 дней включительно.
     * «Сегодня» вычисляется в часовом поясе клиента.
     */
    public function assertDateInWindow(CarbonImmutable $date, string $timezone = 'UTC'): void
    {
        $today = CarbonImmutable::today($timezone);
        $windowEnd = $today->addDays(self::WINDOW_DAYS);

        if ($date->lt($today) || $date->gte($windowEnd)) {
            throw new InvalidSlotException('Дата вне окна записи (ближайшие 14 дней).');
        }
    }

    /**
     * Проверка начала брони: внутри окна, не в прошлом, на 30-минутной сетке.
     */
    public function assertValidStart(CarbonImmutable $start): void
    {
        $now = CarbonImmutable::now();
        $windowEnd = $now->addDays(self::WINDOW_DAYS);

        if ($start->lte($now)) {
            throw new InvalidSlotException('Слот в прошлом.');
        }

        if ($start->gte($windowEnd)) {
            throw new InvalidSlotException('Слот вне окна записи (ближайшие 14 дней).');
        }

        if ($start->minute % self::SLOT_MINUTES !== 0 || $start->second !== 0) {
            throw new InvalidSlotException('Время начала должно быть кратно 30 минутам.');
        }
    }

    /**
     * 30-минутные слоты дня; для «сегодня» отсекаются уже прошедшие.
     * Граница «сейчас» — в часовом поясе клиента.
     *
     * @return array<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function generateSlots(CarbonImmutable $date, string $timezone): array
    {
        $slots = [];
        $dayStart = $date->startOfDay();
        $now = CarbonImmutable::now($timezone);

        for ($i = 0; $i < self::SLOTS_PER_DAY; $i++) {
            $slotStart = $dayStart->addMinutes($i * self::SLOT_MINUTES);
            if ($slotStart->lte($now)) {
                continue;
            }

            $slots[] = [
                'start' => $slotStart,
                'end' => $slotStart->addMinutes(self::SLOT_MINUTES),
            ];
        }

        return $slots;
    }

    /**
     * Занятые интервалы всех броней, пересекающих выбранный день.
     *
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function occupiedIntervalsForDay(CarbonImmutable $date): Collection
    {
        $dayStart = $date->startOfDay();
        $nextDay = $dayStart->addDay();

        return Booking::where('start', '<', $nextDay)
            ->where('end', '>', $dayStart)
            ->get()
            ->map(fn (Booking $booking) => [
                'start' => $booking->start->toImmutable(),
                'end' => $booking->end->toImmutable(),
            ]);
    }
}
