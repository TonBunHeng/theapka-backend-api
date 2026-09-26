<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('note') && ! $this->has('notes')) {
            $merge['notes'] = $this->input('note');
        }

        if ($this->has('corrects_id') && ! is_numeric($this->input('corrects_id'))) {
            $matchedId = \App\Models\GiftRecord::where('client_uuid', $this->input('corrects_id'))->value('id');
            if ($matchedId) {
                $merge['corrects_id'] = $matchedId;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
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
            'note' => ['nullable', 'string'],
            'recorded_by' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
