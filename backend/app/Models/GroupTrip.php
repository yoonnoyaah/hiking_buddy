<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupTrip extends Model
{
    protected $fillable = [
        'organizer_id',
        'mountain_id',
        'mountain_name',
        'title',
        'region',
        'difficulty',
        'trip_date',
        'trip_time',
        'pace',
        'fitness',
        'spots',
        'vehicle',
        'meeting',
        'telegram',
        'phone',
        'emergency',
        'description',
        'status',
    ];

    protected $casts = [
        'trip_date' => 'date:Y-m-d',
        'spots' => 'integer',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function mountain()
    {
        return $this->belongsTo(Mountain::class);
    }

    public function members()
    {
        return $this->hasMany(TripMember::class, 'trip_id');
    }
}
