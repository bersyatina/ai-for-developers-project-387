<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventTypeRequest;
use App\Http\Resources\EventTypeResource;
use App\Models\EventType;

class EventTypeController extends Controller
{
    /** Список всех типов событий (гость). */
    public function index()
    {
        return EventTypeResource::collection(EventType::orderBy('title')->get());
    }

    /** Создать тип события (владелец). */
    public function store(StoreEventTypeRequest $request)
    {
        $validated = $request->validated();

        $eventType = EventType::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'duration_minutes' => $validated['durationMinutes'],
        ]);

        return response()->json(new EventTypeResource($eventType), 201);
    }
}
