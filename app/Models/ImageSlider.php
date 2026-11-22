<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageSlider extends Model
{
    use HasFactory;

    protected $table = 'image_sliders';

    const ENABLE = 1;
    const DISABLE = 2;


    public static function getSlider()
    {
        return self::query()->where('status', self::ENABLE)->orderByDesc('id')->get();
    }
}
