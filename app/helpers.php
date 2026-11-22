<?php

use Illuminate\Support\Str;

if (!function_exists('convert_char')) {
    function convert_char($string)
    {
        $string = Str::replace('ي', 'ی', $string);
        $string = Str::replace('ئ', 'ی', $string);
        $string = Str::replace('ك', 'ک', $string);
        $string = Str::replace('ة', 'ه', $string);
        $string = Str::replace('آ', 'ا', $string);
        $string = Str::replace('آ', 'ا', $string);
        $string = Str::replace('أ', 'ا', $string);
        $string = Str::replace('ؤ', 'و', $string);
        return $string;
    }
}
