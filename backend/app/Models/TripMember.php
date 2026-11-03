<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripMember extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(GroupTrip::class, 'trip_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
