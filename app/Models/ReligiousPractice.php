<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligiousPractice extends Model
{
    use HasFactory;

    protected $fillable = [
        'religious_section_id',
        'day_number',
        'title',
        'content',
        'sort_order',
        'published',
    ];

    protected $casts = [
        'religious_section_id' => 'integer',
        'day_number' => 'integer',
        'sort_order' => 'integer',
        'published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ReligiousPractice $practice): void {
            if ($practice->religious_section_id) {
                $section = $practice->relationLoaded('section')
                    ? $practice->section
                    : ReligiousSection::find($practice->religious_section_id);

                if ($section && ! $section->isMonth()) {
                    $practice->day_number = null;
                }
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReligiousSection::class, 'religious_section_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
