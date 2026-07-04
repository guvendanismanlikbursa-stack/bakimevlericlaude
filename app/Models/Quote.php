<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'offer_request_id', 'facility_id', 'facility_user_id',
        'price', 'price_period', 'message', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'float'];
    }

    public function offerRequest()
    {
        return $this->belongsTo(OfferRequest::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function facilityUser()
    {
        return $this->belongsTo(FacilityUser::class);
    }
}
