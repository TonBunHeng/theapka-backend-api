<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
        if (! $this->has('settings') && count($this->all()) > 0) {
            $settings = [];
            foreach ($this->all() as $k => $v) {
                if (in_array($k, ['_token', '_method'], true)) continue;
                $settings[] = [
                    'key' => (string) $k,
                    'value' => $v,
                    'group' => 'general',
                    'is_public' => false,
                ];
            }
            $this->merge(['settings' => $settings]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:50'],
            'settings.*.is_public' => ['nullable', 'boolean'],
        ];
    }
}
