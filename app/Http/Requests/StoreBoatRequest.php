<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'boat_number' => 'required|integer|min:1|unique:boats,boat_number',
            'name' => 'nullable|string|max:255',
            'color_hex' => 'nullable|string|max:7',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'boat_number.required' => 'Boat number is required.',
            'boat_number.unique' => 'This boat number is already in use.',
            'boat_number.min' => 'Boat number must be at least 1.',
        ];
    }
}
