<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('template_config') && ! $this->has('content')) {
            $merge['content'] = $this->input('template_config');
        }

        if ($this->has('template_id') && is_string($this->input('template_id'))) {
            $matchedId = \App\Models\Template::where('slug', $this->input('template_id'))->value('id');
            $merge['template_id'] = $matchedId ?: null;
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
            'template_id' => ['nullable', 'integer'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string', 'max:10000'],
            'content' => ['nullable', 'array'],
            'template_config' => ['nullable', 'array'],
            'music_url' => ['nullable', 'string', 'max:2048'],
            'is_moderation_enabled' => ['nullable', 'boolean'],
        ];
    }
}
