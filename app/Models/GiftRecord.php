<?php

namespace App\Models;

use App\Enums\GiftCurrency;
use App\Enums\GiftEntryType;
use App\Models\Scopes\WeddingScope;
use App\Observers\GiftRecordObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([GiftRecordObserver::class])]
class GiftRecord extends Model
{
    use HasFactory;

    /**
     * Disable default updated_at since gift ledger is append-only.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'wedding_id',
        'client_uuid',
        'guest_id',
        'giver_name',
        'amount',
        'currency',
        'method',
        'entry_type',
        'corrects_id',
        'notes',
        'recorded_by',
        'recorded_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency' => GiftCurrency::class,
            'entry_type' => GiftEntryType::class,
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new WeddingScope);
        static::observe(GiftRecordObserver::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function correctedRecord(): BelongsTo
    {
        return $this->belongsTo(GiftRecord::class, 'corrects_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(GiftRecord::class, 'corrects_id');
    }
}
