<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
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
            'template_id' => $this->template_id,
            'template' => $this->whenLoaded('template', fn () => new TemplateResource($this->template)),
            'slug' => $this->slug,
            'title' => $this->title,
            'custom_css' => $this->custom_css,
            'content' => $this->content ?? (object) [],
            'template_config' => $this->content ?? (object) [],
            'wedding' => $this->wedding ? new WeddingResource($this->wedding) : null,
            'music_url' => $this->music_url,
            'view_count' => (int) $this->view_count,
            'status' => $this->status,
            'is_moderation_enabled' => (bool) $this->is_moderation_enabled,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
