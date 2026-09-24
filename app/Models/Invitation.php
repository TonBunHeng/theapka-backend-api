<?php

namespace App\Models;

use App\Models\Scopes\WeddingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'template_id',
        'slug',
        'title',
        'custom_css',
        'content',
        'music_url',
        'view_count',
        'status',
        'is_moderation_enabled',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_moderation_enabled' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new WeddingScope);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(InvitationSend::class);
    }
}
