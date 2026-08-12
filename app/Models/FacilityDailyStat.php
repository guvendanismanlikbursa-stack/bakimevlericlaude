<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityDailyStat extends Model
{
    protected $fillable = [
        'facility_id', 'date', 'views_count', 'favorites_count',
        'offer_requests_count', 'quotes_sent_count', 'quotes_accepted_count',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
