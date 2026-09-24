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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', RsvpStatus::values())],
            'attending_count' => ['required', 'integer', 'min:0', 'max:50'],
            'dietary_requirements' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
