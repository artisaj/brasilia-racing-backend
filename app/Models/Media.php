<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class, 'image_media_id');
    }

    public function getOriginalUrlAttribute(): string
    {
        return $this->resolveUrl($this->original_path);
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->resolveUrl($this->thumb_path);
    }

    public function getCardUrlAttribute(): string
    {
        return $this->resolveUrl($this->card_path);
    }

    public function getHeroUrlAttribute(): string
    {
        return $this->resolveUrl($this->hero_path);
    }

    public function getFullUrlAttribute(): string
    {
        return $this->resolveUrl($this->full_path);
    }

    private function resolveUrl(string $path): string
    {
        $disk = Storage::disk($this->disk);

        $resolved = $disk->url($path);

        if (str_starts_with($resolved, 'http://') || str_starts_with($resolved, 'https://')) {
            return $resolved;
        }

        return '/'.ltrim($resolved, '/');
    }
}
