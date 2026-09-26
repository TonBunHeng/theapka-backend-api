<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'file_name' => $this->file_name,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'mime_type' => $this->mime_type,
            'file_size' => (int) $this->file_size,
            'dimensions' => $this->dimensions,
            'type' => $this->type,
            'collection' => $this->collection,
            'is_cover' => (bool) ($this->wedding && $this->wedding->cover_image_url === $this->url),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
