<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PhotoAlbum extends Model
{
    use HasFactory;

    public const UNTITLED_LABEL = 'آلبوم بدون نام';

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'cover_thumbnail',
        'status',
        'views',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'boolean',
        'views' => 'integer',
        'sort_order' => 'integer',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'photo_album_id')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function activePhotos(): HasMany
    {
        return $this->hasMany(Photo::class, 'photo_album_id')
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => filled($this->title)
                ? trim((string) $this->title)
                : self::UNTITLED_LABEL
        );
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (filled($this->cover_thumbnail)) {
                    return Storage::disk('gallery')->url($this->cover_thumbnail);
                }

                if (filled($this->cover_image)) {
                    return Storage::disk('gallery')->url($this->cover_image);
                }

                $firstPhoto = $this->relationLoaded('activePhotos')
                    ? $this->activePhotos->first()
                    : $this->activePhotos()->first();

                return $firstPhoto?->thumbnail_url;
            }
        );
    }

    /**
     * Retrieve paginated published albums with optional search and whitelisted sorting.
     */
    public static function getPublicAlbums(
        ?string $search = null,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 12
    ): LengthAwarePaginator {
        $allowedSortFields = ['id', 'title', 'views', 'sort_order'];
        $safeField = in_array($sortField, $allowedSortFields, true) ? $sortField : 'id';
        $safeDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query = self::query()
            ->published()
            ->withCount('activePhotos')
            ->with([
                'activePhotos' => fn (HasMany $relation) => $relation->select([
                    'id',
                    'photo_album_id',
                    'image',
                    'thumbnail',
                    'sort_order',
                ]),
            ]);

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function (Builder $subQuery) use ($term): void {
                $subQuery->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $query->orderBy($safeField, $safeDirection);

        if ($safeField !== 'id') {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage);
    }
}
