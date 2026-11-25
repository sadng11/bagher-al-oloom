<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    public const SECTION_WRITER = 'writer';
    public const SECTION_SCHOLAR = 'scholar';

    protected $fillable = [
        'title',
        'writer_id',
        'section',
        'author_name',
        'file',
        'pdf_file',
        'views',
        'downloads',
    ];

    protected $appends = [
        'downloadable', 'human_time'
    ];

    protected static function booted(): void
    {
        static::creating(function (self $book): void {
            if (empty($book->section)) {
                $book->section = self::SECTION_WRITER;
            }
        });
    }

    public function writer(): BelongsTo
    {
        return $this->belongsTo(Writer::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(BookContent::class, 'book_id');
    }

    public function isScholarWork(): bool
    {
        return $this->section === self::SECTION_SCHOLAR;
    }

    protected function displayAuthor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->isScholarWork()
                ? (string) ($this->author_name ?? '')
                : (string) ($this->writer?->full_name ?? $this->author_name ?? '')
        );
    }

    /**
     * Get the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $content = BookContent::where('book_id', $this->id)->orderBy('id')->paginate(20);
                $s = '';
                foreach ($content as $item) {
                    $s .= $item->content;
                }
                return $s . ' ...';
            }
        );
    }


    /**
     * Get the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $content = BookStatus::where('book_id', $this->id)->first();
                return $content ? '<span style="color:' . BookStatus::STATES[$content->status]['color'] . ';">' . __(BookStatus::STATES[$content->status]['value']) . '</span>' : null;
            }
        );
    }

    protected function downloadable(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!is_null($this->pdf_file) && $this->pdf_file != ''){
                    return 1;
                }
                return 0;
            }
        );
    }

    protected function humanTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (new \Carbon\Carbon($this->created_at))->diffForHumans()
        );
    }


    public static function getWriterBooks($writer_id, $field, $type)
    {
        return self::query()->with('writer')->where('writer_id', $writer_id)->orderBy('title', 'asc')->get();
    }

    public static function getBook($book_id): self
    {
        return self::query()->with('writer')->findOrFail($book_id);
    }

    public static function getScholarWorks(
        ?string $search = null,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $allowedSortFields = ['id', 'title', 'author_name', 'views', 'downloads'];
        $column = in_array($sortField, $allowedSortFields, true) ? $sortField : 'id';
        $normalizedDirection = strtolower($sortDirection);
        $direction = in_array($normalizedDirection, ['asc', 'desc'], true)
            ? $normalizedDirection
            : 'desc';

        $query = self::query()->where('section', self::SECTION_SCHOLAR);
        $term = $search !== null ? trim($search) : '';

        if ($term !== '') {
            $query->where(function (Builder $subQuery) use ($term): void {
                $subQuery->where('title', 'like', "%{$term}%")
                    ->orWhere('author_name', 'like', "%{$term}%");
            });
        }

        return $query->orderBy($column, $direction)->paginate(max(1, $perPage));
    }
}
