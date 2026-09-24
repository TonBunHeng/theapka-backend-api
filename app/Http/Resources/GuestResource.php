<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
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
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'name' => $this->group->name,
            ]),
            'side' => $this->side,
            'seats' => (int) $this->seats,
            'token' => $this->token,
            'rsvp_status' => $this->rsvp_status,
            'sent_at' => $this->sent_at,
            'opened_at' => $this->opened_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
