<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Weddings owned by this user.
     */
    public function ownedWeddings(): HasMany
    {
        return $this->hasMany(Wedding::class, 'owner_id');
    }

    /**
     * Wedding memberships for co-hosts/editors.
     */
    public function weddingMemberships(): HasMany
    {
        return $this->hasMany(WeddingMember::class, 'user_id');
    }

    /**
     * Get the primary or first associated wedding for this user.
     */
    public function currentWedding(): ?Wedding
    {
        $owned = $this->ownedWeddings()->first();
        if ($owned instanceof Wedding) {
            return $owned;
        }

        $membership = $this->weddingMemberships()->with('wedding')->first();
        if ($membership instanceof WeddingMember && $membership->wedding instanceof Wedding) {
            return $membership->wedding;
        }

        return null;
    }

    /**
     * Gift records recorded by this user.
     */
    public function recordedGifts(): HasMany
    {
        return $this->hasMany(GiftRecord::class, 'recorded_by');
    }

    /**
     * Support tickets created by this user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }
}
