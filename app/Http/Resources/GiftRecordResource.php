<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftRecordResource extends JsonResource
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
            'client_uuid' => $this->client_uuid,
            'guest_id' => $this->guest_id,
            'guest' => $this->whenLoaded('guest', fn () => [
                'id' => $this->guest->id,
                'name' => $this->guest->name,
            ]),
            'giver_name' => $this->giver_name,
            'amount' => (float) $this->amount,
            'currency' => $this->currency?->value ?? (string) $this->currency,
            'method' => $this->method,
            'entry_type' => $this->entry_type?->value ?? (string) $this->entry_type,
            'corrects_id' => $this->corrects_id,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
            'recorded_at' => $this->recorded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
