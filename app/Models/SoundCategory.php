<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoundCategory extends Model
{
    use HasFactory;

    protected $fillable = ['title'];
    protected $appends = [
        'human_time'
    ];

    public static function getAllCategory($sort_field, $sort_type)
    {
        return self::query()->orderBy($sort_field, $sort_type)->paginate(100);
    }

    protected function HumanTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (new \Carbon\Carbon($this->created_at))->diffForHumans()
        );
    }

}
