<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
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
        if (! $this->has('role')) {
            $this->merge(['role' => RoleName::ADMIN->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', 'string', 'in:' . RoleName::ADMIN->value],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
