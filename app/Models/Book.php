<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $appends = [
        'downloadable', 'human_time'
    ];

    public function writer()
    {
        return $this->belongsTo(Writer::class);
    }

    public function contents()
    {
        return $this->hasMany(BookContent::class);
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

    public static function getBook($book_id)
    {
        return self::query()->findOrFail($book_id);
    }
}
