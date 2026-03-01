<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'type',
        'disk',
        'original_path',
        'thumb_path',
        'card_path',
        'hero_path',
        'full_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'uploaded_by',
    ];

    protected $appends = [
        'original_url',
        'thumb_url',
        'card_url',
        'hero_url',
        'full_url',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getOriginalUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->original_path);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->thumb_path);
    }

    public function getCardUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->card_path);
    }

    public function getHeroUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->hero_path);
    }

    public function getFullUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->full_path);
    }
}
