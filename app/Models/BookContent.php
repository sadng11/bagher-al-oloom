<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class BookContent extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['book_id', 'content'];

    public $timestamps = false;

    public function toSearchableArray()
    {
        return [
            'id'          => $this->id,
            'book_id'     => $this->book_id,
            'book_title'  => $this->book?->title ?? '',     // ← این فیلد مهم است
            'content'     => $this->content,
        ];
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }


}
