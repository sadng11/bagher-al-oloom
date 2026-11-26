<?php

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Convert any cover-only albums into editable Photo rows.
     */
    public function up(): void
    {
        PhotoAlbum::query()
            ->whereNotNull('cover_image')
            ->doesntHave('photos')
            ->get()
            ->each(function (PhotoAlbum $album): void {
                Photo::withoutEvents(function () use ($album): void {
                    Photo::create([
                        'photo_album_id' => $album->id,
                        'image' => $album->cover_image,
                        'thumbnail' => $album->cover_thumbnail ?: $album->cover_image,
                        'caption' => $album->description,
                        'sort_order' => 0,
                        'status' => true,
                    ]);
                });

                PhotoAlbum::withoutEvents(function () use ($album): void {
                    $album->forceFill([
                        'cover_image' => null,
                        'cover_thumbnail' => null,
                    ])->save();
                });
            });
    }

    public function down(): void
    {
        // Non-destructive data migration.
    }
};
