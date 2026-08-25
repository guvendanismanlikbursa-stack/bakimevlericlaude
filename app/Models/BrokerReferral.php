<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerReferral extends Model
{
    protected $fillable = [
        'facility_id', 'family_name', 'family_phone', 'status',
        'fee_amount', 'fee_status', 'referred_at', 'placed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'float',
            'referred_at' => 'date',
            'placed_at' => 'date',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
