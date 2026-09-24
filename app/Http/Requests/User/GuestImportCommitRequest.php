<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GuestImportCommitRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.phone' => ['nullable', 'string', 'max:30'],
            'rows.*.email' => ['nullable', 'email', 'max:255'],
            'rows.*.side' => ['nullable', 'string', 'in:groom,bride,mutual'],
            'rows.*.seats' => ['nullable', 'integer', 'min:1', 'max:50'],
            'rows.*.group_name' => ['nullable', 'string', 'max:255'],
            'rows.*.notes' => ['nullable', 'string'],
        ];
    }
}
