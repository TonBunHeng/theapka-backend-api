<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $count = $this->guests_count ?? ($this->relationLoaded('guests') ? $this->guests->count() : $this->guests()->count());

        return [
            'id' => $this->id,
            'wedding_id' => $this->wedding_id,
            'name' => $this->name,
            'color' => $this->color ?? '#8B1E3F',
            'order' => (int) $this->order,
            'count' => (int) $count,
            'guests_count' => (int) $count,
        ];
    }
}
