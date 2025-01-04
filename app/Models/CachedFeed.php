<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CachedFeed extends Model
{
    protected $fillable = [
        'section_name',
        'data'
    ];
}
