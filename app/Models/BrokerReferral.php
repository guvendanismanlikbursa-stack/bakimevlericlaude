<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerReferral extends Model
{
    protected $fillable = [
        'facility_id', 'family_name', 'family_phone',
        'patient_name', 'patient_age', 'patient_mobility', 'wants_visit',
        'status', 'fee_amount', 'fee_status', 'referred_at', 'placed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'float',
            'patient_age' => 'integer',
            'wants_visit' => 'boolean',
            'referred_at' => 'date',
            'placed_at' => 'date',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
