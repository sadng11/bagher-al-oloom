<?php

namespace App\Nova\Filters;

use App\Models\ReligiousSection;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class ReligiousDayFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Get the displayable name of the filter.
     */
    public function name(): string
    {
        return __('Day of Month');
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value === 'general') {
            return $query->whereNull('day_number');
        }

        return $query->where('day_number', (int) $value);
    }

    /**
     * Get the filter's available options.
     */
    public function options(NovaRequest $request): array
    {
        $options = [
            __('General / No Day') => 'general',
        ];

        for ($day = 1; $day <= ReligiousSection::DAYS_IN_MONTH; $day++) {
            $options['روز ' . $day] = (string) $day;
        }

        return $options;
    }
}
