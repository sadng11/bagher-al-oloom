<?php

namespace Tests\Feature;

use App\Models\ReligiousPractice;
use App\Models\ReligiousSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousPracticeSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_header_renders_religious_practices_navigation_link(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('اعمال شرعی')
            ->assertSee(route('religious_practices.index'));

        $href = route('religious_practices.index');
        $anchorCount = preg_match_all(
            '/<a\b[^>]*\bhref="' . preg_quote($href, '/') . '"[^>]*>/',
            $response->getContent()
        );

        $this->assertGreaterThanOrEqual(2, $anchorCount);
    }

    public function test_migration_seeds_all_21_sections_in_canonical_order(): void
    {
        $sections = ReligiousSection::query()->ordered()->get();

        $this->assertCount(21, $sections);

        $months = $sections->where('group_type', ReligiousSection::GROUP_MONTH)->values();
        $this->assertCount(12, $months);
        $this->assertSame([
            'رمضان',
            'شوال',
            'ذی‌القعده',
            'ذی‌الحجه',
            'محرم',
            'صفر',
            'ربیع‌الاول',
            'ربیع‌الثانی',
            'جمادی‌الاول',
            'جمادی‌الثانی',
            'رجب',
            'شعبان',
        ], $months->pluck('title')->all());

        $weekdays = $sections->where('group_type', ReligiousSection::GROUP_WEEKDAY)->values();
        $this->assertCount(7, $weekdays);
        $this->assertSame([
            'شنبه',
            'یکشنبه',
            'دوشنبه',
            'سه‌شنبه',
            'چهارشنبه',
            'پنج‌شنبه',
            'جمعه',
        ], $weekdays->pluck('title')->all());

        $this->assertSame('مشترکات', $sections->firstWhere('group_type', ReligiousSection::GROUP_COMMON)?->title);
        $this->assertSame('نوروز', $sections->firstWhere('group_type', ReligiousSection::GROUP_NOWRUZ)?->title);
    }

    public function test_index_displays_all_21_sections_grouped_from_ramadan_to_shaban(): void
    {
        $response = $this->get(route('religious_practices.index'));

        $response->assertOk()
            ->assertViewIs('site.religious_practices.index')
            ->assertSeeInOrder(['رمضان', 'شوال', 'رجب', 'شعبان'])
            ->assertSeeInOrder(['شنبه', 'یکشنبه', 'پنج‌شنبه', 'جمعه'])
            ->assertSee('مشترکات')
            ->assertSee('نوروز');

        foreach (ReligiousSection::defaultSections() as $section) {
            $response->assertSee(route('religious_practices.section', ['section' => $section['slug']]));
        }
    }

    public function test_month_page_displays_all_30_clickable_days_and_optional_general_practices(): void
    {
        $ramadan = ReligiousSection::where('slug', 'ramadan')->firstOrFail();

        ReligiousPractice::create([
            'religious_section_id' => $ramadan->id,
            'day_number' => null,
            'title' => 'اعمال کلی ماه رمضان',
            'content' => '<p>تلاوت روزانه قرآن کریم</p>',
            'sort_order' => 1,
            'published' => true,
        ]);

        $response = $this->get(route('religious_practices.section', ['section' => 'ramadan']));
        $response->assertOk()
            ->assertViewIs('site.religious_practices.month')
            ->assertSee('اعمال ماه رمضان')
            ->assertSee('اعمال کلی ماه رمضان');

        for ($day = 1; $day <= 30; $day++) {
            $response->assertSee(route('religious_practices.day', ['section' => 'ramadan', 'day' => $day]));
            $response->assertSee('روز ' . $day);
        }
    }

    public function test_day_page_displays_ordered_published_practices_and_empty_message_when_none_exist(): void
    {
        $ramadan = ReligiousSection::where('slug', 'ramadan')->firstOrFail();

        ReligiousPractice::create([
            'religious_section_id' => $ramadan->id,
            'day_number' => 15,
            'title' => 'عمل دوم روز پانزدهم',
            'content' => '<p>متن عمل دوم</p>',
            'sort_order' => 2,
            'published' => true,
        ]);

        ReligiousPractice::create([
            'religious_section_id' => $ramadan->id,
            'day_number' => 15,
            'title' => 'عمل اول روز پانزدهم',
            'content' => '<p>متن عمل اول</p>',
            'sort_order' => 1,
            'published' => true,
        ]);

        ReligiousPractice::create([
            'religious_section_id' => $ramadan->id,
            'day_number' => 15,
            'title' => 'عمل پیش‌نویس مخفی',
            'content' => '<p>متن مخفی</p>',
            'sort_order' => 0,
            'published' => false,
        ]);

        $populatedResponse = $this->get(route('religious_practices.day', ['section' => 'ramadan', 'day' => 15]));
        $populatedResponse->assertOk()
            ->assertViewIs('site.religious_practices.day')
            ->assertSeeInOrder(['عمل اول روز پانزدهم', 'عمل دوم روز پانزدهم'])
            ->assertSee('متن عمل اول')
            ->assertSee('متن عمل دوم')
            ->assertDontSee('عمل پیش‌نویس مخفی')
            ->assertSee(route('religious_practices.day', ['section' => 'ramadan', 'day' => 14]))
            ->assertSee(route('religious_practices.day', ['section' => 'ramadan', 'day' => 16]));

        $emptyDayResponse = $this->get(route('religious_practices.day', ['section' => 'ramadan', 'day' => 2]));
        $emptyDayResponse->assertOk()
            ->assertViewIs('site.religious_practices.day')
            ->assertSee('در حال حاضر عملی برای انجام در این روز ثبت نشده است.');

        $this->get('/religious-practices/ramadan/31')->assertNotFound();
        $this->get('/religious-practices/ramadan/0')->assertNotFound();
        $this->get('/religious-practices/friday/5')->assertNotFound();
    }

    public function test_weekday_common_and_nowruz_pages_display_published_practices_and_empty_state(): void
    {
        $friday = ReligiousSection::where('slug', 'friday')->firstOrFail();
        $common = ReligiousSection::where('slug', 'moshtarakat')->firstOrFail();

        ReligiousPractice::create([
            'religious_section_id' => $friday->id,
            'day_number' => null,
            'title' => 'دعای ندبه',
            'content' => '<p>خواندن دعای ندبه در صبح جمعه</p>',
            'sort_order' => 1,
            'published' => true,
        ]);

        ReligiousPractice::create([
            'religious_section_id' => $common->id,
            'day_number' => null,
            'title' => 'تسبیحات حضرت زهرا (س)',
            'content' => '<p>پس از هر نماز واجب</p>',
            'sort_order' => 1,
            'published' => true,
        ]);

        $this->get(route('religious_practices.section', ['section' => 'friday']))
            ->assertOk()
            ->assertViewIs('site.religious_practices.section')
            ->assertSee('دعای ندبه')
            ->assertSee('شنبه')
            ->assertSee('جمعه');

        $this->get(route('religious_practices.section', ['section' => 'moshtarakat']))
            ->assertOk()
            ->assertViewIs('site.religious_practices.section')
            ->assertSee('تسبیحات حضرت زهرا (س)');

        $this->get(route('religious_practices.section', ['section' => 'nowruz']))
            ->assertOk()
            ->assertViewIs('site.religious_practices.section')
            ->assertSee('در حال حاضر عملی برای انجام در این بخش ثبت نشده است.');
    }
}
