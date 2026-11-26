<?php

namespace App\Observers;

use App\Models\PhotoAlbum;
use App\Services\ImageOptimizationService;

class PhotoAlbumObserver
{
    public function __construct(private readonly ImageOptimizationService $optimizer)
    {
    }

    public function saving(PhotoAlbum $album): void
    {
        if (! $album->isDirty('cover_image')) {
            return;
        }

        $oldCover = $album->exists ? $album->getOriginal('cover_image') : null;
        $oldCoverThumbnail = $album->exists ? $album->getOriginal('cover_thumbnail') : null;

        if (filled($album->cover_image)) {
            $optimized = $this->optimizer->optimize('gallery', $album->cover_image, 'covers');
            $album->cover_image = $optimized['image'];
            $album->cover_thumbnail = $optimized['thumbnail'];
        } else {
            $album->cover_thumbnail = null;
        }

        if ($album->exists) {
            $toDelete = array_filter([
                $oldCover !== $album->cover_image ? $oldCover : null,
                $oldCoverThumbnail !== $album->cover_thumbnail ? $oldCoverThumbnail : null,
            ]);
            if ($toDelete !== []) {
                $this->optimizer->deleteFiles('gallery', ...$toDelete);
            }
        }
    }

    public function deleting(PhotoAlbum $album): void
    {
        $filesToDelete = [$album->cover_image, $album->cover_thumbnail];

        foreach ($album->photos()->get(['id', 'image', 'thumbnail']) as $photo) {
            $filesToDelete[] = $photo->image;
            $filesToDelete[] = $photo->thumbnail;
        }

        $this->optimizer->deleteFiles('gallery', ...$filesToDelete);
    }
}
