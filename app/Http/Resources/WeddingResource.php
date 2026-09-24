<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Wedding
 */
class WeddingResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'slug' => $this->slug,
            'title' => $this->title,
            'wedding_date' => $this->wedding_date instanceof \DateTimeInterface ? $this->wedding_date->format('Y-m-d') : ($this->wedding_date ? (string) $this->wedding_date : null),
            'venue_name' => $this->venue_name,
            'venue_address' => $this->venue_address,
            'venue_map_url' => $this->venue_map_url,
            'timezone' => $this->timezone,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'cover_image_url' => $this->cover_image_url,
            'cover_photo' => $this->cover_image_url,
            'music_url' => $this->invitation?->music_url,
            'user_id' => $this->owner_id,
            'user_name' => $this->owner?->name ?? 'Couple',
            'groom_name' => $this->details?->groom_name ?? '',
            'bride_name' => $this->details?->bride_name ?? '',
            'groom_name_kh' => $this->details?->groom_name ?? '',
            'groom_name_en' => $this->details?->groom_name ?? '',
            'bride_name_kh' => $this->details?->bride_name ?? '',
            'bride_name_en' => $this->details?->bride_name ?? '',
            'groom_father_kh' => $this->details?->groom_parents ?? '',
            'groom_mother_kh' => '',
            'bride_father_kh' => $this->details?->bride_parents ?? '',
            'bride_mother_kh' => '',
            'story' => $this->details?->story ?? '',
            'plan' => $this->activeSubscription?->plan?->slug ?? 'free',
            'guest_count' => $this->resource instanceof \App\Models\Wedding ? $this->resource->guests()->count() : 0,
            'settings' => $this->settings ?? (object) [],
            'details' => $this->whenLoaded('details', fn () => new WeddingDetailResource($this->details)),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
