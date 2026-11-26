<?php

namespace App\Nova\Fields;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class AlbumPhotos extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'album-photos';

    /**
     * Indicates if the field should use all available width.
     *
     * @var bool
     */
    public $fullWidth = true;

    public function __construct($name = null, $attribute = 'album_photos', $resolveCallback = null)
    {
        parent::__construct($name ?? __('Album Photos'), $attribute, $resolveCallback);
    }

    /**
     * Resolve the field's value from the PhotoAlbum model.
     */
    public function resolve($resource, $attribute = null): void
    {
        $this->resource = $resource;

        if (! $resource || ! $resource->exists) {
            $this->value = [];

            return;
        }

        $this->value = $resource->photos()
            ->get()
            ->map(fn (Photo $photo) => [
                'id' => $photo->id,
                'image_url' => $photo->image_url,
                'thumbnail_url' => $photo->thumbnail_url,
                'caption' => (string) ($photo->caption ?? ''),
                'sort_order' => (int) $photo->sort_order,
                'status' => (bool) $photo->status,
                'file_size_kb' => $photo->file_size ? round($photo->file_size / 1024, 1) : null,
                'dimensions' => ($photo->width && $photo->height) ? "{$photo->width} × {$photo->height}" : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Get the validation rules for creation requests.
     */
    public function getCreationRules(NovaRequest $request): array
    {
        return $this->albumPhotoRules();
    }

    /**
     * Get the validation rules for update requests.
     */
    public function getUpdateRules(NovaRequest $request): array
    {
        return $this->albumPhotoRules();
    }

    /**
     * Hydrate the album photos after the parent PhotoAlbum model is saved.
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        $items = $request->input('album_photos', []);
        $files = $request->file('album_photos', []);
        $deletedIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $request->input('deleted_photo_ids', [])
        ))));

        $items = is_array($items) ? $items : [];
        $files = is_array($files) ? $files : [];

        $indices = array_unique(array_merge(array_keys($items), array_keys($files)));
        sort($indices, SORT_NUMERIC);

        foreach ($indices as $index) {
            $row = is_array($items[$index] ?? null) ? $items[$index] : [];
            $uploadedFile = $files[$index]['image'] ?? null;
            $photoId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
            $caption = isset($row['caption']) ? trim((string) $row['caption']) : '';

            if ($photoId === null && ! ($uploadedFile instanceof UploadedFile) && $caption !== '') {
                throw ValidationException::withMessages([
                    "album_photos.{$index}.image" => ['برای ثبت توضیح تصویر، انتخاب فایل عکس الزامی است.'],
                    'album_photos' => ['برای ثبت توضیح تصویر، انتخاب فایل عکس الزامی است.'],
                ]);
            }
        }

        return function () use ($model, $items, $files, $indices, $deletedIds): void {
            if ($deletedIds !== []) {
                Photo::where('photo_album_id', $model->id)
                    ->whereIn('id', $deletedIds)
                    ->get()
                    ->each(fn (Photo $photo) => $photo->delete());
            }

            foreach ($indices as $position => $index) {
                $row = is_array($items[$index] ?? null) ? $items[$index] : [];
                $uploadedFile = $files[$index]['image'] ?? null;
                $photoId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
                $caption = isset($row['caption']) && trim((string) $row['caption']) !== ''
                    ? trim((string) $row['caption'])
                    : null;
                $sortOrder = isset($row['sort_order']) && $row['sort_order'] !== ''
                    ? (int) $row['sort_order']
                    : $position;

                if ($photoId !== null) {
                    $photo = Photo::where('photo_album_id', $model->id)->find($photoId);
                    if (! $photo) {
                        continue;
                    }

                    $photo->caption = $caption;
                    $photo->sort_order = $sortOrder;

                    if ($uploadedFile instanceof UploadedFile) {
                        $photo->image = $uploadedFile->store('photos', 'gallery');
                    }

                    $photo->save();
                    continue;
                }

                if ($uploadedFile instanceof UploadedFile) {
                    $storedPath = $uploadedFile->store('photos', 'gallery');

                    Photo::create([
                        'photo_album_id' => $model->id,
                        'image' => $storedPath,
                        'caption' => $caption,
                        'sort_order' => $sortOrder,
                        'status' => true,
                    ]);
                }
            }
        };
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'acceptedExtensions' => Photo::ACCEPTED_EXTENSIONS,
            'maxUploadKb' => Photo::MAX_UPLOAD_KB,
        ]);
    }

    private function albumPhotoRules(): array
    {
        $mimesRule = 'mimes:' . implode(',', Photo::ACCEPTED_EXTENSIONS);

        return [
            'album_photos' => ['nullable', 'array'],
            'album_photos.*.id' => ['nullable', 'integer'],
            'album_photos.*.caption' => ['nullable', 'string', 'max:2000'],
            'album_photos.*.sort_order' => ['nullable', 'integer'],
            'album_photos.*.image' => ['nullable', 'file', $mimesRule, 'max:' . Photo::MAX_UPLOAD_KB],
            'deleted_photo_ids' => ['nullable', 'array'],
            'deleted_photo_ids.*' => ['integer'],
        ];
    }
}
