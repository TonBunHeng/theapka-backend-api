<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
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
            'value' => ['required'],
            'group' => ['nullable', 'string', 'max:50'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
