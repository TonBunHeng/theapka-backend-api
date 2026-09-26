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
            'file' => ['required_without_all:url,id', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'url' => ['required_without_all:file,id', 'nullable', 'string'],
            'id' => ['nullable', 'integer'],
            'is_cover' => ['nullable', 'boolean'],
            'collection' => ['nullable', 'string', 'in:gallery,cover,avatar,general'],
        ];
    }
}
