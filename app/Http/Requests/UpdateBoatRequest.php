<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'boat_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('boats', 'boat_number')->ignore($this->route('boat')),
            ],
            'name' => 'nullable|string|max:255',
            'color_hex' => 'nullable|string|max:7',
            'notes' => 'nullable|string',
        ];
    }
}
