<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitRequest extends Model
{
    protected $fillable = [
        'facility_id', 'type', 'brand', 'full_name', 'phone', 'email', 'preferred_day', 'preferred_time', 'message', 'status',
    ];

    protected function casts(): array
    {
        return ['facility_id' => 'integer'];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}