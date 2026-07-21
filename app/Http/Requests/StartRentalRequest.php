<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'boat_id' => 'required|integer|exists:boats,id',
        ];
    }

    public function messages(): array
    {
        return [
            'boat_id.required' => 'Please select a boat.',
            'boat_id.exists' => 'The selected boat does not exist.',
        ];
    }
}
