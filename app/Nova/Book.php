<?php

namespace App\Nova;

use App\Models\BookStatus;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class Book extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Book>
     */
    public static $model = \App\Models\Book::class;

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
        'id', 'title', 'writer.name', 'writer.family'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __('Books');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return __('Book');
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where($query->qualifyColumn('section'), \App\Models\Book::SECTION_WRITER);
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return $query->where($query->qualifyColumn('section'), \App\Models\Book::SECTION_WRITER);
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
            Text::make(__('Title'), 'title')->rules('required', 'min:3')->sortable(),
            BelongsTo::make(__('Writer'), 'writer', Writer::class)->showCreateRelationButton()->modalSize('7xl')->sortable(),
            File::make(__('Word File'), 'file')
                ->hideWhenUpdating()
                ->hideFromDetail()
                ->disk('word')
                ->acceptedTypes('.docx')
                ->rules('required'),
            Boolean::make(__("Has Pdf File"), function ($model) {
                if ($model->pdf_file != null)
                    return true;
                return false;
            })->onlyOnIndex(),
            Number::make(__('Views'), 'views')->exceptOnForms()->sortable(),
            Number::make(__('Downloads'), 'downloads')->exceptOnForms()->sortable(),
            Url::make(__('Pdf File Address'), 'pdf_file')->hideFromIndex(),
//            File::make(__('Pdf File'), 'pdf_file')->disk('pdf')->acceptedTypes('.pdf'),
//            Text::make(__('PdfBookStatus'), 'status')->asHtml()->exceptOnForms(),
            HasMany::make(__('Book Contents'), 'contents', BookContent::class),
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
//        $action = new Actions\MakePdf();
//        $action->name = __('admin.make_pdf');
//        return [
//            $action->showInline()->confirmText(__('admin.make_pdf_confirm'))
//                ->confirmButtonText(__('admin.yes'))
//        ];
        return [];
    }
}
