<?php

namespace App\Nova;

use App\Models\ReligiousSection as ReligiousSectionModel;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class ReligiousSection extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ReligiousSection>
     */
    public static $model = ReligiousSectionModel::class;

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
        'id', 'title', 'slug',
    ];

    /**
     * The number of resources to show per page on index.
     *
     * @var int
     */
    public static $perPageViaRelationship = 25;

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Religious Sections');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Religious Section');
    }

    /**
     * Order sections canonically from Ramadan to Sha'ban, then weekdays, Moshtarakat, Nowruz.
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return parent::indexQuery($request, $query)->orderBy('sort_order');
    }

    /**
     * Ensure relation dropdowns also show the 21 sections in canonical order.
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query)->orderBy('sort_order');
    }

    /**
     * The 21 sections are fixed and cannot be created manually.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    /**
     * Prevent deleting the 21 canonical sections.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }

    /**
     * Prevent replicating canonical sections.
     */
    public function authorizedToReplicate(Request $request): bool
    {
        return false;
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Title'), 'title')
                ->readonly()
                ->sortable(),
            Select::make(__('Section Group'), 'group_type')
                ->options(ReligiousSectionModel::groupLabels())
                ->displayUsingLabels()
                ->readonly()
                ->sortable(),
            Number::make(__('Sort Order'), 'sort_order')
                ->readonly()
                ->sortable(),
            Textarea::make(__('Description'), 'description')
                ->nullable()
                ->hideFromIndex(),
            HasMany::make(__('Religious Practices'), 'practices', ReligiousPractice::class),
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
