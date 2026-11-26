<?php

namespace App\Nova;

use App\Models\Photo as PhotoModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Photo extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Photo>
     */
    public static $model = \App\Models\Photo::class;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'caption',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Photos');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Photo');
    }

    /**
     * Get the value that should be displayed to represent the resource.
     */
    public function title(): string
    {
        return filled($this->caption)
            ? Str::limit(strip_tags((string) $this->caption), 60)
            : __('Photo') . ' #' . $this->id;
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        $acceptedTypes = '.' . implode(',.', PhotoModel::ACCEPTED_EXTENSIONS);
        $mimesRule = 'mimes:' . implode(',', PhotoModel::ACCEPTED_EXTENSIONS);

        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make(__('Photo Album'), 'album', PhotoAlbum::class)
                ->showCreateRelationButton()
                ->modalSize('7xl')
                ->sortable()
                ->rules('required'),
            Image::make(__('Photo File'), 'image')
                ->disk('gallery')
                ->acceptedTypes($acceptedTypes)
                ->deletable(false)
                ->creationRules('required', 'file', $mimesRule, 'max:' . PhotoModel::MAX_UPLOAD_KB)
                ->updateRules('nullable', 'file', $mimesRule, 'max:' . PhotoModel::MAX_UPLOAD_KB)
                ->thumbnail(function () {
                    $path = $this->resource?->thumbnail ?: $this->value;

                    return $path ? Storage::disk('gallery')->url($path) : null;
                })
                ->preview(function () {
                    return $this->value ? Storage::disk('gallery')->url($this->value) : null;
                })
                ->help(__('help image upload rules')),
            Textarea::make(__('Photo Caption'), 'caption')
                ->nullable()
                ->alwaysShow()
                ->displayUsing(fn ($value) => filled($value) ? Str::limit((string) $value, 80) : '—')
                ->help(__('help photo caption')),
            Text::make(__('Dimensions'), function () {
                return ($this->width && $this->height) ? "{$this->width} × {$this->height}" : '—';
            })->onlyOnDetail(),
            Text::make(__('Optimized Size'), function () {
                return $this->file_size ? number_format($this->file_size / 1024, 1) . ' KB' : '—';
            })->onlyOnDetail(),
            Number::make(__('Sort Order'), 'sort_order')->default(0)->sortable(),
            Boolean::make(__('Status'), 'status')->default(true)->sortable(),
        ];
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
