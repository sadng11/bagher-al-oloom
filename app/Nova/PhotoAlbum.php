<?php

namespace App\Nova;

use App\Nova\Fields\AlbumPhotos;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class PhotoAlbum extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\PhotoAlbum>
     */
    public static $model = \App\Models\PhotoAlbum::class;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title', 'description',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Photo Albums');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Photo Album');
    }

    /**
     * Get the value that should be displayed to represent the resource.
     */
    public function title(): string
    {
        return filled($this->title)
            ? (string) $this->title
            : __('Untitled Album') . ' #' . $this->id;
    }

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->with('photos');
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Album Title'), 'title')
                ->nullable()
                ->rules('nullable', 'max:500')
                ->sortable()
                ->displayUsing(fn ($value) => filled($value) ? $value : __('Untitled Album'))
                ->help(__('help album title')),
            Textarea::make(__('Description'), 'description')
                ->nullable()
                ->hideFromIndex(),
            AlbumPhotos::make(__('Album Photos'), 'album_photos')
                ->help(__('help album photos')),
            Number::make(__('Views'), 'views')->exceptOnForms()->sortable(),
            Number::make(__('Sort Order'), 'sort_order')->default(0)->sortable(),
            Boolean::make(__('Status'), 'status')->default(true)->sortable(),
            HasMany::make(__('Photos'), 'photos', Photo::class),
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
