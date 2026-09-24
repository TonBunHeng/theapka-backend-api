<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'features' => $this->features ?? (object) [],
            'max_guests' => (int) $this->max_guests,
            'max_photos' => (int) $this->max_photos,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
