<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eventTypeId' => $this->event_type_id,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'guestName' => $this->guest_name,
            'guestEmail' => $this->guest_email,
        ];
    }
}
