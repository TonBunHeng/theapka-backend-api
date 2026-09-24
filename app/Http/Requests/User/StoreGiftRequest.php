<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftRequest extends FormRequest
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
            'client_uuid' => ['required', 'string', 'uuid'],
            'giver_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'in:KHR,USD'],
            'method' => ['nullable', 'string', 'max:50'],
            'entry_type' => ['nullable', 'string', 'in:gift,correction'],
            'corrects_id' => ['required_if:entry_type,correction', 'nullable', 'integer', 'exists:gift_records,id'],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'notes' => ['nullable', 'string'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
