<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mountain extends Model
{
    protected $fillable = [
        'name',
        'region',
        'difficulty',
        'distance',
        'duration',
        'elevation',
        'pace',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}