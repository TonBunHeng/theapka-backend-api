<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:templates,slug', 'alpha_dash'],
            'thumbnail_url' => ['nullable', 'string', 'url'],
            'preview_url' => ['nullable', 'string', 'url'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_premium' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,retired'],
        ];
    }
}
