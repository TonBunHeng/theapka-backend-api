<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvitationRequest extends FormRequest
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
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string', 'max:10000'],
            'content' => ['nullable', 'array'],
            'music_url' => ['nullable', 'string', 'url', 'max:2048'],
            'is_moderation_enabled' => ['nullable', 'boolean'],
        ];
    }
}
