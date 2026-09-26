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
        $custom = is_array($this->custom_fields) ? $this->custom_fields : [];
        $rawGroom = (string) ($this->groom_name ?? '');
        $rawBride = (string) ($this->bride_name ?? '');

        $groomKh = $custom['groom_name_kh'] ?? null;
        $groomEn = $custom['groom_name_en'] ?? null;
        if (! $groomKh || ! $groomEn) {
            if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawGroom, $m)) {
                $groomKh = $groomKh ?: trim($m[1]);
                $groomEn = $groomEn ?: trim($m[2]);
            } else {
                $groomKh = $groomKh ?: $rawGroom;
                $groomEn = $groomEn ?: $rawGroom;
            }
        }

        $brideKh = $custom['bride_name_kh'] ?? null;
        $brideEn = $custom['bride_name_en'] ?? null;
        if (! $brideKh || ! $brideEn) {
            if (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawBride, $m)) {
                $brideKh = $brideKh ?: trim($m[1]);
                $brideEn = $brideEn ?: trim($m[2]);
            } else {
                $brideKh = $brideKh ?: $rawBride;
                $brideEn = $brideEn ?: $rawBride;
            }
        }

        return [
            'id' => $this->id,
            'wedding_id' => $this->wedding_id,
            'groom_name' => $groomKh,
            'groom_name_kh' => $groomKh,
            'groom_name_en' => $groomEn,
            'groom_title' => $this->groom_title,
            'groom_parents' => $this->groom_parents,
            'bride_name' => $brideKh,
            'bride_name_kh' => $brideKh,
            'bride_name_en' => $brideEn,
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
