<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
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
            'thumbnail_url' => $this->thumbnail_url,
            'thumbnail' => $this->thumbnail_url,
            'preview_url' => $this->preview_url,
            'config' => $this->config ?? (object) [],
            'is_active' => (bool) $this->is_active,
            'is_premium' => (bool) $this->is_premium,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
