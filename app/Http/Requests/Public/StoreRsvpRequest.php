<?php

namespace App\Http\Requests\Public;

use App\Enums\RsvpStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('rsvp_status') && ! $this->has('status')) {
            $merge['status'] = $this->input('rsvp_status');
        }
        if ($this->has('seats') && ! $this->has('attending_count')) {
            $merge['attending_count'] = (int) $this->input('seats');
        }
        if ($this->has('note') && ! $this->has('notes')) {
            $merge['notes'] = $this->input('note');
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
            'status' => ['required', 'string', 'in:' . implode(',', RsvpStatus::values())],
            'rsvp_status' => ['nullable', 'string'],
            'attending_count' => ['required', 'integer', 'min:0', 'max:50'],
            'seats' => ['nullable', 'integer'],
            'dietary_requirements' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ];
    }
}
