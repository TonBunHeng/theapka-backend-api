<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'groom_name',
        'groom_title',
        'groom_parents',
        'bride_name',
        'bride_title',
        'bride_parents',
        'story',
        'welcome_message',
        'dress_code',
        'contact_phones',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'contact_phones' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
