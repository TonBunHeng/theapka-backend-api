<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GuestImportPreviewRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // 5MB max
        ];
    }
}
