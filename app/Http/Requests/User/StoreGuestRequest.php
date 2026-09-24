<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_id' => ['nullable', 'integer', 'exists:guest_groups,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'side' => ['required', 'string', 'in:groom,bride,mutual'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
