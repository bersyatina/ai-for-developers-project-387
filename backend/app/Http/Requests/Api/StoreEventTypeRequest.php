<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // авторизации в проекте нет — владелец предустановлен
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'durationMinutes' => ['required', 'integer', 'multiple_of:30', 'between:30,240'],
        ];
    }
}
