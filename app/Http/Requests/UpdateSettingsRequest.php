<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|in:rental_duration_minutes,warning_minutes,alarm_interval_seconds,session_timeout_minutes',
            'value' => 'required|integer|min:1',
        ];
    }
}
