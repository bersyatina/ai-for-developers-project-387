<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // авторизации в проекте нет — гость бронирует без аккаунта
    }

    public function rules(): array
    {
        return [
            'eventTypeId' => ['required', 'uuid', 'exists:event_types,id'],
            'start' => ['required', 'date'],
            'guestName' => ['required', 'string', 'max:255'],
            'guestEmail' => ['required', 'email', 'max:255'],
        ];
    }
}
