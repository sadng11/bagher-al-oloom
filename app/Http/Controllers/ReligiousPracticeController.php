<?php

namespace App\Http\Controllers;

use App\Models\ReligiousSection;
use Illuminate\Contracts\View\View;

class ReligiousPracticeController extends Controller
{
    /**
     * Display all 21 religious practice sections grouped cleanly for easy navigation.
     */
    public function index(): View
    {
        $sections = ReligiousSection::query()
            ->ordered()
            ->get();

        $months = $sections->where('group_type', ReligiousSection::GROUP_MONTH)->values();
        $weekdays = $sections->where('group_type', ReligiousSection::GROUP_WEEKDAY)->values();
        $commonSection = $sections->firstWhere('group_type', ReligiousSection::GROUP_COMMON);
        $nowruzSection = $sections->firstWhere('group_type', ReligiousSection::GROUP_NOWRUZ);

        return view('site.religious_practices.index', [
            'months' => $months,
            'weekdays' => $weekdays,
            'commonSection' => $commonSection,
            'nowruzSection' => $nowruzSection,
        ]);
    }

    /**
     * Display a section: 30-day selector for lunar months, or practices directly for other sections.
     */
    public function showSection(ReligiousSection $section): View
    {
        if ($section->isMonth()) {
            $generalPractices = $section->publishedPractices()
                ->whereNull('day_number')
                ->ordered()
                ->get();

            return view('site.religious_practices.month', [
                'section' => $section,
                'days' => range(1, ReligiousSection::DAYS_IN_MONTH),
                'generalPractices' => $generalPractices,
            ]);
        }

        $practices = $section->publishedPractices()
            ->ordered()
            ->get();

        $weekdays = $section->group_type === ReligiousSection::GROUP_WEEKDAY
            ? ReligiousSection::query()
                ->where('group_type', ReligiousSection::GROUP_WEEKDAY)
                ->ordered()
                ->get()
            : collect();

        return view('site.religious_practices.section', [
            'section' => $section,
            'practices' => $practices,
            'weekdays' => $weekdays,
        ]);
    }

    /**
     * Display practices for a specific day (1..30) within a lunar month section.
     */
    public function showDay(ReligiousSection $section, int $day): View
    {
        abort_unless(
            $section->isMonth() && $day >= 1 && $day <= ReligiousSection::DAYS_IN_MONTH,
            404
        );

        $practices = $section->publishedPractices()
            ->where('day_number', $day)
            ->ordered()
            ->get();

        $prevDay = $day > 1 ? $day - 1 : null;
        $nextDay = $day < ReligiousSection::DAYS_IN_MONTH ? $day + 1 : null;

        return view('site.religious_practices.day', [
            'section' => $section,
            'day' => $day,
            'practices' => $practices,
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
        ]);
    }
}
