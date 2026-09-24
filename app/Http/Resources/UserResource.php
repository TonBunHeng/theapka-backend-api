<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->roles->first()?->name ?? 'user',
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'is_active' => (bool) $this->is_active,
            'status' => $this->is_active ? 'active' : 'suspended',
            'avatar' => $this->avatar_url,
            'avatar_url' => $this->avatar_url,
            'weddings_count' => $this->ownedWeddings()->count(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
