<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use App\Services\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function __construct(private readonly SlotService $slots) {}

    /** Свободные 30-минутные слоты выбранного дня (гость). */
    public function index(Request $request, EventType $eventType)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'tz' => ['nullable', 'string', 'timezone'],
        ]);

        $timezone = $validated['tz'] ?? config('app.timezone');
        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'], $timezone);

        return response()->json($this->slots->availableSlots($eventType, $date, $timezone));
    }
}
