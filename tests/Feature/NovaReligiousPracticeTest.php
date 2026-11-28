<?php

namespace Tests\Feature;

use App\Models\ReligiousPractice;
use App\Models\ReligiousSection;
use App\Models\User;
use App\Nova\Filters\ReligiousDayFilter;
use App\Nova\Filters\ReligiousSectionFilter;
use App\Nova\ReligiousPractice as NovaReligiousPractice;
use App\Nova\ReligiousSection as NovaReligiousSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Tests\TestCase;

class NovaReligiousPracticeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsNovaUser(): User
    {
        $user = User::factory()->create();
        Gate::define('viewNova', fn () => true);
        $this->actingAs($user);

        return $user;
    }

    private function bootNova(): NovaRequest
    {
        Nova::flushState();
        ServingNova::dispatch(Request::create('/manage'));

        return NovaRequest::create('/manage');
    }

    public function test_nova_registers_religious_practice_resources_in_menu_and_resolves_models(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $this->assertSame(NovaReligiousSection::class, Nova::resourceForModel(ReligiousSection::class));
        $this->assertSame(NovaReligiousPractice::class, Nova::resourceForModel(ReligiousPractice::class));

        $menu = collect(Nova::resolveMainMenu($request));
        $menuSection = $menu->first(
            fn ($item) => $item instanceof MenuSection && $item->name === __('Religious Practices')
        );
        $this->assertInstanceOf(MenuSection::class, $menuSection);

        $paths = collect($menuSection->items)
            ->filter(fn ($item) => $item instanceof MenuItem)
            ->pluck('path')
            ->all();

        $this->assertContains('/resources/religious-practices', $paths);
        $this->assertContains('/resources/religious-sections', $paths);
    }

    public function test_nova_prevents_creating_or_deleting_the_21_canonical_sections(): void
    {
        $this->actingAsNovaUser();
        $this->bootNova();

        $ramadan = ReligiousSection::where('slug', 'ramadan')->firstOrFail();

        $this->postJson('/nova-api/religious-sections', [
            'title' => 'بخش اضافه',
            'group_type' => ReligiousSection::GROUP_MONTH,
            'sort_order' => 99,
        ])->assertForbidden();

        $this->deleteJson('/nova-api/religious-sections', [
            'resources' => [$ramadan->id],
        ])->assertOk();

        $this->assertNotNull(ReligiousSection::find($ramadan->id));
    }

    public function test_nova_creation_fields_group_selector_filters_sections_and_toggles_day_field(): void
    {
        $this->actingAsNovaUser();
        $this->bootNova();

        $creationFields = $this->getJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create')
            ->assertOk()
            ->json('fields');

        $fieldsByAttr = collect($creationFields)->keyBy('attribute');
        $this->assertSame('religious-section-group', $fieldsByAttr['section_group']['component']);
        $this->assertCount(4, $fieldsByAttr['section_group']['options']);
        $this->assertSame([], $fieldsByAttr['religious_section_id']['options']);
        $this->assertFalse($fieldsByAttr['day_number']['visible']);

        $sectionComponentKey = $fieldsByAttr['religious_section_id']['dependentComponentKey'];
        $dayComponentKey = $fieldsByAttr['day_number']['dependentComponentKey'];

        // Selecting "ماه‌های قمری" yields 12 months and makes day_number visible
        $monthSections = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=religious_section_id&component=' . urlencode($sectionComponentKey), [
            'section_group' => ReligiousSection::GROUP_MONTH,
        ])->assertOk()->json();
        $this->assertCount(12, $monthSections['options']);
        $this->assertSame('رمضان', $monthSections['options'][0]['label']);
        $this->assertSame('شعبان', $monthSections['options'][11]['label']);

        $monthDay = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=day_number&component=' . urlencode($dayComponentKey), [
            'section_group' => ReligiousSection::GROUP_MONTH,
        ])->assertOk()->json();
        $this->assertTrue($monthDay['visible']);

        // Selecting "روزهای هفته" yields 7 weekdays and hides day_number
        $weekdaySections = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=religious_section_id&component=' . urlencode($sectionComponentKey), [
            'section_group' => ReligiousSection::GROUP_WEEKDAY,
        ])->assertOk()->json();
        $this->assertCount(7, $weekdaySections['options']);
        $this->assertSame('شنبه', $weekdaySections['options'][0]['label']);
        $this->assertSame('جمعه', $weekdaySections['options'][6]['label']);

        $weekdayDay = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=day_number&component=' . urlencode($dayComponentKey), [
            'section_group' => ReligiousSection::GROUP_WEEKDAY,
        ])->assertOk()->json();
        $this->assertFalse($weekdayDay['visible']);

        // Selecting "مشترکات" auto-selects the single common section and hides day_number
        $common = ReligiousSection::where('slug', 'moshtarakat')->firstOrFail();
        $commonSections = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=religious_section_id&component=' . urlencode($sectionComponentKey), [
            'section_group' => ReligiousSection::GROUP_COMMON,
        ])->assertOk()->json();
        $this->assertCount(1, $commonSections['options']);
        $this->assertSame($common->id, $commonSections['value']);

        // Selecting "نوروز" auto-selects the single Nowruz section and hides day_number
        $nowruz = ReligiousSection::where('slug', 'nowruz')->firstOrFail();
        $nowruzSections = $this->patchJson('/nova-api/religious-practices/creation-fields?editing=true&editMode=create&field=religious_section_id&component=' . urlencode($sectionComponentKey), [
            'section_group' => ReligiousSection::GROUP_NOWRUZ,
        ])->assertOk()->json();
        $this->assertCount(1, $nowruzSections['options']);
        $this->assertSame($nowruz->id, $nowruzSections['value']);
    }

    public function test_nova_update_fields_preselect_existing_section_group_and_save_practices(): void
    {
        $this->actingAsNovaUser();
        $this->bootNova();

        $ramadan = ReligiousSection::where('slug', 'ramadan')->firstOrFail();
        $friday = ReligiousSection::where('slug', 'friday')->firstOrFail();

        $this->postJson('/nova-api/religious-practices', [
            'section_group' => ReligiousSection::GROUP_MONTH,
            'religious_section_id' => $ramadan->id,
            'day_number' => 31,
            'title' => 'عمل نامعتبر',
            'content' => '<p>متن</p>',
            'sort_order' => 0,
            'published' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('day_number');

        $ramadanResponse = $this->postJson('/nova-api/religious-practices', [
            'section_group' => ReligiousSection::GROUP_MONTH,
            'religious_section_id' => $ramadan->id,
            'day_number' => 19,
            'title' => 'اعمال شب نوزدهم رمضان',
            'content' => '<p>احیا و دعا</p>',
            'sort_order' => 1,
            'published' => true,
        ])->assertCreated();

        $ramadanPractice = ReligiousPractice::findOrFail($ramadanResponse->json('id'));
        $this->assertSame($ramadan->id, $ramadanPractice->religious_section_id);
        $this->assertSame(19, $ramadanPractice->day_number);

        $updateFields = $this->getJson('/nova-api/religious-practices/' . $ramadanPractice->id . '/update-fields?editing=true&editMode=update')
            ->assertOk()
            ->json('fields');
        $updateByAttr = collect($updateFields)->keyBy('attribute');
        $this->assertSame(ReligiousSection::GROUP_MONTH, $updateByAttr['section_group']['value']);
        $this->assertCount(12, $updateByAttr['religious_section_id']['options']);
        $this->assertTrue($updateByAttr['day_number']['visible']);

        $fridayResponse = $this->postJson('/nova-api/religious-practices', [
            'section_group' => ReligiousSection::GROUP_WEEKDAY,
            'religious_section_id' => $friday->id,
            'day_number' => 10,
            'title' => 'غسل جمعه',
            'content' => '<p>مستحب موکد</p>',
            'sort_order' => 1,
            'published' => true,
        ])->assertCreated();

        $fridayPractice = ReligiousPractice::findOrFail($fridayResponse->json('id'));
        $this->assertNull($fridayPractice->day_number);

        $fridayUpdateFields = $this->getJson('/nova-api/religious-practices/' . $fridayPractice->id . '/update-fields?editing=true&editMode=update')
            ->assertOk()
            ->json('fields');
        $fridayByAttr = collect($fridayUpdateFields)->keyBy('attribute');
        $this->assertSame(ReligiousSection::GROUP_WEEKDAY, $fridayByAttr['section_group']['value']);
        $this->assertCount(7, $fridayByAttr['religious_section_id']['options']);
        $this->assertFalse($fridayByAttr['day_number']['visible']);

        $novaRequest = NovaRequest::create('/manage');
        $sectionFiltered = (new ReligiousSectionFilter())->apply($novaRequest, ReligiousPractice::query(), $ramadan->id)->get();
        $this->assertCount(1, $sectionFiltered);
        $this->assertSame($ramadanPractice->id, $sectionFiltered->first()->id);

        $dayFiltered = (new ReligiousDayFilter())->apply($novaRequest, ReligiousPractice::query(), '19')->get();
        $this->assertCount(1, $dayFiltered);
        $this->assertSame($ramadanPractice->id, $dayFiltered->first()->id);

        $generalFiltered = (new ReligiousDayFilter())->apply($novaRequest, ReligiousPractice::query(), 'general')->get();
        $this->assertCount(1, $generalFiltered);
        $this->assertSame($fridayPractice->id, $generalFiltered->first()->id);
    }

    public function test_nova_persian_translations_json_contains_religious_practice_keys(): void
    {
        $raw = file_get_contents(lang_path('vendor/nova/fa.json'));
        $this->assertNotFalse($raw);

        $translations = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        foreach ([
            'Religious Practices',
            'Religious Practice',
            'Religious Sections',
            'Religious Section',
            'Section Group',
            'Day of Month',
            'General / No Day',
            'help practice day number',
            'Choose section group first',
            'help section group',
        ] as $key) {
            $this->assertArrayHasKey($key, $translations);
        }
    }
}
