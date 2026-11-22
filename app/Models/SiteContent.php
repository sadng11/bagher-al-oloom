<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    use HasFactory;

    const ABOUT_US = 'about_us';
    const ADVICE = 'advices';
    const CONTACT = 'contact_us';
    const PUBLISHED = 1;
    const UN_PUBLISHED = 0;
    protected $fillable = ['type', 'content', 'published'];

    public static function getContent($type)
    {
        return self::query()->where('type', $type)->where('published', self::PUBLISHED)->orderByDesc('id')->get();
    }
}
