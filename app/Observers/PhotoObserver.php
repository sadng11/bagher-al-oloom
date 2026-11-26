<?php

namespace App\Observers;

use App\Models\Photo;
use App\Services\ImageOptimizationService;

class PhotoObserver
{
    public function __construct(private readonly ImageOptimizationService $optimizer)
    {
    }

    public function saving(Photo $photo): void
    {
        if (! $photo->isDirty('image') || empty($photo->image)) {
            return;
        }

        $oldImage = $photo->exists ? $photo->getOriginal('image') : null;
        $oldThumbnail = $photo->exists ? $photo->getOriginal('thumbnail') : null;

        $optimized = $this->optimizer->optimize('gallery', $photo->image, 'photos');

        $photo->image = $optimized['image'];
        $photo->thumbnail = $optimized['thumbnail'];
        $photo->width = $optimized['width'];
        $photo->height = $optimized['height'];
        $photo->file_size = $optimized['file_size'];
        $photo->original_extension = $optimized['original_extension'];

        if ($photo->exists) {
            $toDelete = array_filter([
                $oldImage !== $photo->image ? $oldImage : null,
                $oldThumbnail !== $photo->thumbnail ? $oldThumbnail : null,
            ]);
            if ($toDelete !== []) {
                $this->optimizer->deleteFiles('gallery', ...$toDelete);
            }
        }
    }

    public function deleted(Photo $photo): void
    {
        $this->optimizer->deleteFiles('gallery', $photo->image, $photo->thumbnail);
    }
}
