<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];
        if (! $this->has('content') && ($this->has('body_km') || $this->has('body_en'))) {
            $merge['content'] = $this->input('body_km') ?: $this->input('body_en');
        }
        if ($this->has('audience') && ! $this->has('target_role')) {
            $merge['target_role'] = $this->input('audience');
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'body_km' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'audience' => ['nullable', 'string', 'in:all,user,admin'],
            'target_role' => ['nullable', 'string', 'in:all,user,admin'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
