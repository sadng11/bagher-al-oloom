<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    public const MAX_UPLOAD_KB = 2048;

    public const ACCEPTED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'bmp',
        'avif',
    ];

    protected $fillable = [
        'photo_album_id',
        'image',
        'thumbnail',
        'caption',
        'original_extension',
        'width',
        'height',
        'file_size',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(PhotoAlbum::class, 'photo_album_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->image)
                ? Storage::disk('gallery')->url($this->image)
                : null
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->thumbnail)
                ? Storage::disk('gallery')->url($this->thumbnail)
                : $this->image_url
        );
    }
}
