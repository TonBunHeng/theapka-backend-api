<?php

namespace App\Models;

use App\Enums\WeddingStatus;
use App\Models\Scopes\WeddingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wedding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'slug',
        'title',
        'wedding_date',
        'venue_name',
        'venue_address',
        'venue_map_url',
        'timezone',
        'status',
        'cover_image_url',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'settings' => 'array',
            'status' => WeddingStatus::class,
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new WeddingScope);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WeddingMember::class);
    }

    public function details(): HasOne
    {
        return $this->hasOne(WeddingDetail::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class)->orderBy('order')->orderBy('start_time');
    }

    public function guestGroups(): HasMany
    {
        return $this->hasMany(GuestGroup::class)->orderBy('order');
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class)->latest();
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function giftRecords(): HasMany
    {
        return $this->hasMany(GiftRecord::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
