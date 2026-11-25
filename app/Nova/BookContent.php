<?php

namespace App\Nova;

use App\Nova\Concerns\ScopesByBookSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class BookContent extends Resource
{
    use ScopesByBookSection;

    /**
     * The relationships that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $with = ['book'];

    /**
     * Get the book section owned by this resource.
     */
    protected static function resourceBookSection(): string
    {
        return \App\Models\Book::SECTION_WRITER;
    }

    /**
     * Determine whether the resource scopes via the parent book relation.
     */
    protected static function scopesThroughBookRelation(): bool
    {
        return true;
    }

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\BookContent>
     */
    public static $model = \App\Models\BookContent::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'content',
    ];

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 15;

    /**
     * The pagination per-page options available for this resource.
     *
     * @var array
     */
    public static $perPageOptions = [15, 25, 50, 100];

    /**
     * Determine if the current user can create new resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return '#' . $this->id . ' - ' . Str::limit(strip_tags($this->content), 50);
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __('Book Contents');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Book Content');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            BelongsTo::make(__('Book'), 'book', Book::class)
                ->searchable()
                ->rules('required')
                ->sortable(),

            Text::make(__('Content Preview'), 'content')
                ->displayUsing(fn ($val) => Str::limit(strip_tags($val), 100))
                ->onlyOnIndex(),

            Trix::make(__('Content'), 'content')
                ->alwaysShow()
                ->rules('required'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
