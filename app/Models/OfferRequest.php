<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'facility_id', 'brand', 'brand_id', 'family_user_id', 'city_id', 'district_id', 'facility_category_id',
        'full_name', 'phone', 'email', 'message', 'patient_name', 'care_for',
        'status', 'accepted_quote_id', 'batch_id',
    ];

    public function brandModel()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function acceptedQuote()
    {
        return $this->belongsTo(Quote::class, 'accepted_quote_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Bu talep, dogrudan tek bir kuruma mi yoksa sehir/kategorideki TUM
     * sahiplenilmis kurumlara mi (Armut tipi yayin talep) acik, onu belirler.
     */
    public function isBroadcast(): bool
    {
        return is_null($this->facility_id);
    }
}
