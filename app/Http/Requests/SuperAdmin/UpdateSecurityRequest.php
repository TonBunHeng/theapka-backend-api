<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityRequest extends FormRequest
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
        if ($this->has('session_timeout_minutes') && ! $this->has('session_lifetime')) {
            $merge['session_lifetime'] = (int) $this->input('session_timeout_minutes');
        }
        if ($this->has('require_2fa') && ! $this->has('enforce_2fa')) {
            $merge['enforce_2fa'] = (bool) $this->input('require_2fa');
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
            'session_lifetime' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'max_login_attempts' => ['nullable', 'integer', 'min:3', 'max:20'],
            'enforce_2fa' => ['nullable', 'boolean'],
            'require_2fa' => ['nullable', 'boolean'],
            'password_min_length' => ['nullable', 'integer', 'min:8', 'max:32'],
            'ip_allowlist' => ['nullable', 'string'],
        ];
    }
}
