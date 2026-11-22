<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookStatus extends Model
{
    use HasFactory;

    const START = 1;
    const FINISHED = 2;
    const FAILED = 3;

    const STATES = [
        self::START => ['value' => 'Start', 'color' => '#664D03'],
        self::FINISHED => ['value' => 'Finished', 'color' => '#3B7459'],
        self::FAILED => ['value' => 'Failed', 'color' => '#842029']
    ];

    protected $fillable = ['book_id', 'status'];

    protected $table = 'book_status';


}
