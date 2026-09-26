<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $details = $this->input('details', []);

        $customFields = $details['custom_fields'] ?? [];
        if (! is_array($customFields)) {
            $customFields = [];
        }

        if ($this->has('groom_name_kh') || $this->has('groom_name_en') || $this->has('groom_name')) {
            $groomKh = $this->input('groom_name_kh');
            $groomEn = $this->input('groom_name_en');
            if ($groomKh) $customFields['groom_name_kh'] = $groomKh;
            if ($groomEn) $customFields['groom_name_en'] = $groomEn;
            $details['groom_name'] = $groomKh ?: ($this->input('groom_name') ?: $groomEn);
        }
        if ($this->has('bride_name_kh') || $this->has('bride_name_en') || $this->has('bride_name')) {
            $brideKh = $this->input('bride_name_kh');
            $brideEn = $this->input('bride_name_en');
            if ($brideKh) $customFields['bride_name_kh'] = $brideKh;
            if ($brideEn) $customFields['bride_name_en'] = $brideEn;
            $details['bride_name'] = $brideKh ?: ($this->input('bride_name') ?: $brideEn);
        }

        if (! empty($customFields)) {
            $details['custom_fields'] = $customFields;
        }
        if ($this->has('groom_father_kh') || $this->has('groom_mother_kh')) {
            $parents = array_filter([$this->input('groom_father_kh'), $this->input('groom_mother_kh')]);
            if (! empty($parents)) {
                $details['groom_parents'] = implode(' & ', $parents);
            }
        }
        if ($this->has('bride_father_kh') || $this->has('bride_mother_kh')) {
            $parents = array_filter([$this->input('bride_father_kh'), $this->input('bride_mother_kh')]);
            if (! empty($parents)) {
                $details['bride_parents'] = implode(' & ', $parents);
            }
        }
        if ($this->has('story')) {
            $details['story'] = $this->input('story');
        }

        $merge = [];
        if (! empty($details)) {
            $merge['details'] = array_merge($this->input('details', []), $details);
        }
        if ($this->has('cover_photo') && ! $this->has('cover_image_url')) {
            $merge['cover_image_url'] = $this->input('cover_photo');
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash'],
            'wedding_date' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string'],
            'venue_map_url' => ['nullable', 'string', 'url'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'cover_image_url' => ['nullable', 'string'],
            'cover_photo' => ['nullable', 'string'],
            'music_url' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'groom_name_kh' => ['nullable', 'string', 'max:255'],
            'groom_name_en' => ['nullable', 'string', 'max:255'],
            'bride_name_kh' => ['nullable', 'string', 'max:255'],
            'bride_name_en' => ['nullable', 'string', 'max:255'],
            'groom_father_kh' => ['nullable', 'string', 'max:255'],
            'groom_mother_kh' => ['nullable', 'string', 'max:255'],
            'bride_father_kh' => ['nullable', 'string', 'max:255'],
            'bride_mother_kh' => ['nullable', 'string', 'max:255'],
            'story' => ['nullable', 'string'],

            // Wedding Details fields
            'details' => ['nullable', 'array'],
            'details.groom_name' => ['nullable', 'string', 'max:255'],
            'details.groom_title' => ['nullable', 'string', 'max:100'],
            'details.groom_parents' => ['nullable', 'string'],
            'details.bride_name' => ['nullable', 'string', 'max:255'],
            'details.bride_title' => ['nullable', 'string', 'max:100'],
            'details.bride_parents' => ['nullable', 'string'],
            'details.story' => ['nullable', 'string'],
            'details.welcome_message' => ['nullable', 'string'],
            'details.dress_code' => ['nullable', 'string', 'max:255'],
            'details.contact_phones' => ['nullable', 'array'],
            'details.custom_fields' => ['nullable', 'array'],
        ];
    }
}
