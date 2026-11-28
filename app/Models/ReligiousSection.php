<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReligiousSection extends Model
{
    use HasFactory;

    public const GROUP_MONTH = 'month';
    public const GROUP_WEEKDAY = 'weekday';
    public const GROUP_COMMON = 'common';
    public const GROUP_NOWRUZ = 'nowruz';

    public const DAYS_IN_MONTH = 30;

    protected $fillable = [
        'slug',
        'title',
        'group_type',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Return the 21 canonical yearless sections ordered from Ramadan to Sha'ban,
     * followed by Saturday through Friday, Moshtarakat, and Nowruz.
     *
     * @return array<int, array{slug: string, title: string, group_type: string, sort_order: int}>
     */
    public static function defaultSections(): array
    {
        return [
            ['slug' => 'ramadan', 'title' => 'رمضان', 'group_type' => self::GROUP_MONTH, 'sort_order' => 1],
            ['slug' => 'shawwal', 'title' => 'شوال', 'group_type' => self::GROUP_MONTH, 'sort_order' => 2],
            ['slug' => 'dhul-qadah', 'title' => 'ذی‌القعده', 'group_type' => self::GROUP_MONTH, 'sort_order' => 3],
            ['slug' => 'dhul-hijjah', 'title' => 'ذی‌الحجه', 'group_type' => self::GROUP_MONTH, 'sort_order' => 4],
            ['slug' => 'muharram', 'title' => 'محرم', 'group_type' => self::GROUP_MONTH, 'sort_order' => 5],
            ['slug' => 'safar', 'title' => 'صفر', 'group_type' => self::GROUP_MONTH, 'sort_order' => 6],
            ['slug' => 'rabi-al-awwal', 'title' => 'ربیع‌الاول', 'group_type' => self::GROUP_MONTH, 'sort_order' => 7],
            ['slug' => 'rabi-al-thani', 'title' => 'ربیع‌الثانی', 'group_type' => self::GROUP_MONTH, 'sort_order' => 8],
            ['slug' => 'jumada-al-awwal', 'title' => 'جمادی‌الاول', 'group_type' => self::GROUP_MONTH, 'sort_order' => 9],
            ['slug' => 'jumada-al-thani', 'title' => 'جمادی‌الثانی', 'group_type' => self::GROUP_MONTH, 'sort_order' => 10],
            ['slug' => 'rajab', 'title' => 'رجب', 'group_type' => self::GROUP_MONTH, 'sort_order' => 11],
            ['slug' => 'shaban', 'title' => 'شعبان', 'group_type' => self::GROUP_MONTH, 'sort_order' => 12],

            ['slug' => 'saturday', 'title' => 'شنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 13],
            ['slug' => 'sunday', 'title' => 'یکشنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 14],
            ['slug' => 'monday', 'title' => 'دوشنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 15],
            ['slug' => 'tuesday', 'title' => 'سه‌شنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 16],
            ['slug' => 'wednesday', 'title' => 'چهارشنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 17],
            ['slug' => 'thursday', 'title' => 'پنج‌شنبه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 18],
            ['slug' => 'friday', 'title' => 'جمعه', 'group_type' => self::GROUP_WEEKDAY, 'sort_order' => 19],

            ['slug' => 'moshtarakat', 'title' => 'مشترکات', 'group_type' => self::GROUP_COMMON, 'sort_order' => 20],
            ['slug' => 'nowruz', 'title' => 'نوروز', 'group_type' => self::GROUP_NOWRUZ, 'sort_order' => 21],
        ];
    }

    /**
     * Human-readable Persian labels for section groups.
     *
     * @return array<string, string>
     */
    public static function groupLabels(): array
    {
        return [
            self::GROUP_MONTH => 'ماه‌های قمری',
            self::GROUP_WEEKDAY => 'ایام هفته',
            self::GROUP_COMMON => 'مشترکات',
            self::GROUP_NOWRUZ => 'نوروز',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function practices(): HasMany
    {
        return $this->hasMany(ReligiousPractice::class, 'religious_section_id')
            ->orderBy('day_number')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function publishedPractices(): HasMany
    {
        return $this->practices()->where('published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isMonth(): bool
    {
        return $this->group_type === self::GROUP_MONTH;
    }

    public function isDefaultSection(): bool
    {
        $defaultSlugs = array_column(self::defaultSections(), 'slug');

        return in_array($this->slug, $defaultSlugs, true);
    }

    protected function groupLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::groupLabels()[$this->group_type] ?? (string) $this->group_type
        );
    }
}
