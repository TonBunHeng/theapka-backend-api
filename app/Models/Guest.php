<?php

namespace App\Models;

use App\Models\Scopes\WeddingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wedding_id',
        'group_id',
        'name',
        'phone',
        'email',
        'side',
        'seats',
        'token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new WeddingScope);

        static::creating(function (Guest $guest) {
            if (empty($guest->token)) {
                $guest->token = Str::random(32);
            }
        });
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'group_id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(InvitationSend::class);
    }

    public function latestSend(): HasOne
    {
        return $this->hasOne(InvitationSend::class)->latestOfMany();
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function latestRsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class)->latestOfMany();
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function giftRecords(): HasMany
    {
        return $this->hasMany(GiftRecord::class);
    }

    public function getRsvpStatusAttribute(): string
    {
        $rsvp = $this->latestRsvp;
        if ($rsvp instanceof Rsvp) {
            return $rsvp->status instanceof \App\Enums\RsvpStatus ? $rsvp->status->value : (string) $rsvp->status;
        }

        return 'pending';
    }

    public function getSentAtAttribute(): ?string
    {
        $latestSend = $this->latestSend;
        $sentAt = $latestSend instanceof InvitationSend ? $latestSend->sent_at : null;
        return $sentAt instanceof \DateTimeInterface ? $sentAt->format('c') : ($sentAt ? (string) $sentAt : null);
    }

    public function getOpenedAtAttribute(): ?string
    {
        $latestSend = $this->latestSend;
        $openedAt = $latestSend instanceof InvitationSend ? $latestSend->opened_at : null;
        return $openedAt instanceof \DateTimeInterface ? $openedAt->format('c') : ($openedAt ? (string) $openedAt : null);
    }
}
