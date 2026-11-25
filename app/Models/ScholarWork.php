<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarWork extends Book
{
    protected $table = 'books';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('scholar', function (Builder $query): void {
            $query->where($query->qualifyColumn('section'), self::SECTION_SCHOLAR);
        });

        static::creating(function (self $work): void {
            $work->section = self::SECTION_SCHOLAR;
            $work->writer_id = null;
        });

        static::updating(function (self $work): void {
            $work->section = self::SECTION_SCHOLAR;
            $work->writer_id = null;
        });
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ScholarWorkContent::class, 'book_id');
    }
}
