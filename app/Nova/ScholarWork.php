<?php

namespace App\Nova;

use App\Nova\Concerns\ScopesByBookSection;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class ScholarWork extends Resource
{
    use ScopesByBookSection;

    /**
     * Get the book section owned by this resource.
     */
    protected static function resourceBookSection(): string
    {
        return \App\Models\Book::SECTION_SCHOLAR;
    }

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ScholarWork>
     */
    public static $model = \App\Models\ScholarWork::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title', 'author_name',
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __('Scholar Works');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Scholar Work');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Title'), 'title')->rules('required', 'min:3')->sortable(),
            Text::make(__('Author Name'), 'author_name')
                ->rules('required', 'max:500')
                ->sortable()
                ->help(__('help scholar author name')),
            File::make(__('Word File'), 'file')
                ->hideWhenUpdating()
                ->hideFromDetail()
                ->disk('word')
                ->acceptedTypes('.docx')
                ->rules('required'),
            Boolean::make(__('Has Pdf File'), function ($model) {
                return filled($model->pdf_file);
            })->onlyOnIndex(),
            Number::make(__('Views'), 'views')->exceptOnForms()->sortable(),
            Number::make(__('Downloads'), 'downloads')->exceptOnForms()->sortable(),
            URL::make(__('Pdf File Address'), 'pdf_file')->hideFromIndex(),
            HasMany::make(__('Book Contents'), 'contents', ScholarWorkContent::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
