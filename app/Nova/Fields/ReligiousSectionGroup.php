<?php

namespace App\Nova\Fields;

use App\Models\ReligiousSection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class ReligiousSectionGroup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'religious-section-group';

    public function __construct($name = null, $attribute = 'section_group', $resolveCallback = null)
    {
        parent::__construct($name ?? __('Section Group'), $attribute, $resolveCallback);

        $this->onlyOnForms();
        $this->withMeta([
            'options' => $this->groupOptions(),
        ]);
    }

    /**
     * Resolve the active group from an existing practice or parent section relation.
     */
    public function resolve($resource, $attribute = null): void
    {
        $this->resource = $resource;

        if ($resource && $resource->exists && $resource->religious_section_id) {
            $section = $resource->relationLoaded('section')
                ? $resource->section
                : ReligiousSection::find($resource->religious_section_id);

            $this->value = $section?->group_type;

            return;
        }

        $request = app(NovaRequest::class);
        if ($request->viaResource === 'religious-sections' && filled($request->viaResourceId)) {
            $section = ReligiousSection::find($request->viaResourceId);
            $this->value = $section?->group_type;

            return;
        }

        $this->value = null;
    }

    /**
     * Virtual UI selector; actual persistence uses religious_section_id.
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        // Intentionally left blank because section_group only drives dependent form fields.
    }

    /**
     * Build the 4 canonical group options for the Nova radio-card selector.
     *
     * @return array<int, array{value: string, label: string, hint: string}>
     */
    private function groupOptions(): array
    {
        return [
            [
                'value' => ReligiousSection::GROUP_MONTH,
                'label' => 'ماه‌های قمری',
                'hint' => '۱۲ ماه (رمضان تا شعبان)',
            ],
            [
                'value' => ReligiousSection::GROUP_WEEKDAY,
                'label' => 'روزهای هفته',
                'hint' => 'شنبه تا جمعه',
            ],
            [
                'value' => ReligiousSection::GROUP_COMMON,
                'label' => 'مشترکات',
                'hint' => 'اعمال مشترک',
            ],
            [
                'value' => ReligiousSection::GROUP_NOWRUZ,
                'label' => 'نوروز',
                'hint' => 'اعمال عید نوروز',
            ],
        ];
    }
}
