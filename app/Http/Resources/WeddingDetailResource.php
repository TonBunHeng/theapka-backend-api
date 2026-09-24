<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeddingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wedding_id' => $this->wedding_id,
            'groom_name' => $this->groom_name,
            'groom_title' => $this->groom_title,
            'groom_parents' => $this->groom_parents,
            'bride_name' => $this->bride_name,
            'bride_title' => $this->bride_title,
            'bride_parents' => $this->bride_parents,
            'story' => $this->story,
            'welcome_message' => $this->welcome_message,
            'dress_code' => $this->dress_code,
            'contact_phones' => $this->contact_phones ?? [],
            'custom_fields' => $this->custom_fields ?? (object) [],
        ];
    }
}
