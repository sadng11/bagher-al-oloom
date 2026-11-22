<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sound extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'link', 'category_id', 'description'];

    protected $appends = [
        'human_time'
    ];

    public function category()
    {
        return $this->belongsTo(SoundCategory::class);
    }

    public static function getAllSound()
    {
        return self::query()->orderByDesc('id')->paginate();
    }

    public static function getSoundCategory($id, $field, $type)
    {
        return self::query()->where('category_id', $id)->orderBy($field, $type)->get();
    }

    protected function humanTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (new \Carbon\Carbon($this->created_at))->diffForHumans()
        );
    }

}
