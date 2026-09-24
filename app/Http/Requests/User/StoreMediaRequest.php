<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'], // 10MB max
            'collection' => ['nullable', 'string', 'in:gallery,cover,avatar,general'],
        ];
    }
}
