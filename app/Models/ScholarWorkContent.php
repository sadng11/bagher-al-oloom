<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarWorkContent extends BookContent
{
    protected $table = 'book_contents';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('scholar', function (Builder $query): void {
            $query->whereHas('book', function (Builder $bookQuery): void {
                $bookQuery->where($bookQuery->qualifyColumn('section'), Book::SECTION_SCHOLAR);
            });
        });
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function scholarWork(): BelongsTo
    {
        return $this->belongsTo(ScholarWork::class, 'book_id');
    }
}
