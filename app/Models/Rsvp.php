<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'wedding_id',
        'status',
        'attending_count',
        'dietary_requirements',
        'notes',
        'ip_address',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RsvpStatus::class,
            'attending_count' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
