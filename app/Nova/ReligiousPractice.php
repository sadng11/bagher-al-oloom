<?php

namespace App\Nova;

use App\Models\ReligiousPractice as ReligiousPracticeModel;
use App\Models\ReligiousSection as ReligiousSectionModel;
use App\Nova\Fields\ReligiousSectionGroup;
use App\Nova\Filters\ReligiousDayFilter;
use App\Nova\Filters\ReligiousSectionFilter;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class ReligiousPractice extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ReligiousPractice>
     */
    public static $model = ReligiousPracticeModel::class;

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
        'id', 'title', 'content',
    ];

    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 30;

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Religious Practices');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Religious Practice');
    }

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = parent::indexQuery($request, $query)->with('section');

        if (! $request->filled('orderBy')) {
            $query->orderBy('religious_section_id')
                ->orderBy('day_number')
                ->orderBy('sort_order')
                ->orderBy('id');
        }

        return $query;
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        $dayOptions = [];
        for ($day = 1; $day <= ReligiousSectionModel::DAYS_IN_MONTH; $day++) {
            $dayOptions[$day] = 'روز ' . $day;
        }

        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make(__('Religious Section'), 'section', ReligiousSection::class)
                ->exceptOnForms()
                ->sortable(),
            ReligiousSectionGroup::make(__('Section Group'), 'section_group')
                ->onlyOnForms()
                ->help(__('help section group')),
            Select::make(__('Religious Section'), 'religious_section_id')
                ->onlyOnForms()
                ->nullable()
                ->options([])
                ->placeholder(__('Choose section group first'))
                ->rules('required', 'exists:religious_sections,id')
                ->dependsOn(['section_group'], function (Select $field, NovaRequest $request, FormData $formData): void {
                    $group = $formData->get('section_group');

                    if (! filled($group) && $request->viaResource === 'religious-sections' && filled($request->viaResourceId)) {
                        $viaSection = ReligiousSectionModel::find($request->viaResourceId);
                        if ($viaSection) {
                            $group = $viaSection->group_type;
                            $field->value = $viaSection->id;
                        }
                    }

                    if (! filled($group) && $this->resource?->exists && $this->resource->religious_section_id) {
                        $group = $this->resource->section?->group_type;
                    }

                    if (! filled($group)) {
                        $field->options([])->placeholder(__('Choose section group first'));

                        return;
                    }

                    $sections = ReligiousSectionModel::query()
                        ->where('group_type', $group)
                        ->ordered()
                        ->pluck('title', 'id')
                        ->all();

                    $field->options($sections)->placeholder(__('Choose an option'));

                    if (in_array($group, [ReligiousSectionModel::GROUP_COMMON, ReligiousSectionModel::GROUP_NOWRUZ], true)
                        && count($sections) === 1
                    ) {
                        $field->value = array_key_first($sections);
                    }
                }),
            Select::make(__('Day of Month'), 'day_number')
                ->options($dayOptions)
                ->nullable()
                ->sortable()
                ->displayUsing(fn ($value) => filled($value) ? 'روز ' . $value : '—')
                ->rules('nullable', 'integer', 'min:1', 'max:' . ReligiousSectionModel::DAYS_IN_MONTH)
                ->help(__('help practice day number'))
                ->dependsOn(['section_group'], function (Select $field, NovaRequest $request, FormData $formData): void {
                    $group = $formData->get('section_group');

                    if (! filled($group) && $request->viaResource === 'religious-sections' && filled($request->viaResourceId)) {
                        $group = ReligiousSectionModel::find($request->viaResourceId)?->group_type;
                    }

                    if (! filled($group) && $this->resource?->exists && $this->resource->religious_section_id) {
                        $group = $this->resource->section?->group_type;
                    }

                    if ($group === ReligiousSectionModel::GROUP_MONTH) {
                        $field->show();
                    } else {
                        $field->hide();
                        $field->value = null;
                    }
                }),
            Text::make(__('Title'), 'title')
                ->rules('required', 'max:500')
                ->sortable(),
            Trix::make(__('Content'), 'content')
                ->rules('required')
                ->alwaysShow(),
            Number::make(__('Sort Order'), 'sort_order')
                ->default(0)
                ->rules('integer', 'min:0')
                ->sortable(),
            Boolean::make(__('Published'), 'published')
                ->default(true)
                ->sortable(),
        ];
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [
            new ReligiousSectionFilter(),
            new ReligiousDayFilter(),
        ];
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
