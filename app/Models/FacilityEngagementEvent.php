<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityEngagementEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['facility_id', 'type', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
