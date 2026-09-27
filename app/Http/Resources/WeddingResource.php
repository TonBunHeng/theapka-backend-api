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
            'map_url' => $this->venue_map_url,
            'lat' => is_array($this->settings) && isset($this->settings['lat']) ? (float) $this->settings['lat'] : 11.6685,
            'lng' => is_array($this->settings) && isset($this->settings['lng']) ? (float) $this->settings['lng'] : 104.9452,
            'timezone' => $this->timezone,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'is_published' => ($this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status) === 'published',
            'cover_image_url' => $this->cover_image_url,
            'cover_photo' => $this->cover_image_url,
            'music_url' => $this->invitation?->music_url,
            'template_config' => $this->invitation?->content,
            'user_id' => $this->owner_id,
            'user_name' => $this->owner?->name ?? 'Couple',
            'groom_name' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['groom_name_kh'])) return $custom['groom_name_kh'];
                $raw = (string) ($this->details?->groom_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[1]);
                return $raw;
            })(),
            'bride_name' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['bride_name_kh'])) return $custom['bride_name_kh'];
                $raw = (string) ($this->details?->bride_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[1]);
                return $raw;
            })(),
            'groom_name_kh' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['groom_name_kh'])) return $custom['groom_name_kh'];
                $raw = (string) ($this->details?->groom_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[1]);
                return $raw;
            })(),
            'groom_name_en' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['groom_name_en'])) return $custom['groom_name_en'];
                $raw = (string) ($this->details?->groom_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[2]);
                if (! preg_match('/[a-zA-Z]/', $raw)) {
                    if ($this->slug && preg_match('/^([a-z0-9-]+?)-([a-z0-9-]+?)-wedding$/i', $this->slug, $sm)) {
                        return ucwords(str_replace('-', ' ', $sm[1]));
                    }
                    return '';
                }
                return $raw;
            })(),
            'bride_name_kh' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['bride_name_kh'])) return $custom['bride_name_kh'];
                $raw = (string) ($this->details?->bride_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[1]);
                return $raw;
            })(),
            'bride_name_en' => (function () {
                $custom = is_array($this->details?->custom_fields) ? $this->details->custom_fields : [];
                if (! empty($custom['bride_name_en'])) return $custom['bride_name_en'];
                $raw = (string) ($this->details?->bride_name ?? '');
                if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $raw, $m)) return trim($m[2]);
                if (! preg_match('/[a-zA-Z]/', $raw)) {
                    if ($this->slug && preg_match('/^([a-z0-9-]+?)-([a-z0-9-]+?)-wedding$/i', $this->slug, $sm)) {
                        return ucwords(str_replace('-', ' ', $sm[2]));
                    }
                    return '';
                }
                return $raw;
            })(),
            'groom_father_kh' => trim(explode('&', (string) $this->details?->groom_parents)[0] ?? ''),
            'groom_mother_kh' => trim(explode('&', (string) $this->details?->groom_parents)[1] ?? ''),
            'bride_father_kh' => trim(explode('&', (string) $this->details?->bride_parents)[0] ?? ''),
            'bride_mother_kh' => trim(explode('&', (string) $this->details?->bride_parents)[1] ?? ''),
            'groom_parents' => $this->details?->groom_parents ?? '',
            'bride_parents' => $this->details?->bride_parents ?? '',
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
