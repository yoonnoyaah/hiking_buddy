<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'region',
        'difficulty',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}