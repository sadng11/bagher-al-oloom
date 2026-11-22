<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Writer extends Model
{
    protected $fillable = [
        'name',
        'nickname',
    ];
    protected $appends = [
        'full_name', 'human_time'
    ];

    public static function getAllWriter($fields = null, $paginate = true, $field = null, $type = null)
    {
        $fields = is_null($fields) ? ['*'] : $fields;
        if ($paginate)
            return self::query()->orderBy('id', 'asc')->withCount('books')->paginate(null, $fields);
        else
            return self::query()->orderBy('id', 'asc')->withCount('books')->get($fields);
    }


    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->name . ' ' . $this->nickname;
            }
        );
    }

    protected function humanTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (new \Carbon\Carbon($this->created_at))->diffForHumans()
        );
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
