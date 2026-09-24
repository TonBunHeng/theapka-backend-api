<?php

namespace App\Models;

use App\Models\Scopes\WeddingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wedding_id',
        'uploaded_by',
        'disk',
        'file_path',
        'thumbnail_path',
        'file_name',
        'mime_type',
        'file_size',
        'dimensions',
        'type',
        'collection',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'file_size' => 'integer',
        ];
    }

    protected $appends = [
        'url',
        'thumbnail_url',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new WeddingScope);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return $this->url;
        }

        if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
            return $this->thumbnail_path;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }
}
