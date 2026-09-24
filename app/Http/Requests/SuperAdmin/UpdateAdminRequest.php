<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
        $targetId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($targetId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($targetId)],
            'role' => ['sometimes', 'required', 'string', 'in:' . RoleName::ADMIN->value . ',' . RoleName::SUPER_ADMIN->value],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
