<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BottomText extends Model
{
    use HasFactory;

    const ENABLE = 1;
    const DISABLE = 0;

    protected $table = 'bottom_text';

    protected $fillable = ['text', 'status'];

    public static function getActive()
    {
        return self::query()->where('status', self::ENABLE)->orderByDesc('id')->get();
    }

}
