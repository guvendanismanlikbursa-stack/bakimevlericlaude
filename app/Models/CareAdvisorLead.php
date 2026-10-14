<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareAdvisorLead extends Model
{
    protected $fillable = [
        'name', 'phone', 'patient_age', 'gender', 'condition', 'city_id', 'district',
        'facility_category_id', 'budget_max', 'concerns', 'brand', 'section', 'progress_token', 'completed_at',
        'notified_admin_at', 'notified_facility_at',
    ];

    protected function casts(): array
    {
        return ['concerns' => 'array', 'completed_at' => 'datetime', 'notified_admin_at' => 'datetime', 'notified_facility_at' => 'datetime'];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function sends()
    {
        return $this->hasMany(CareAdvisorLeadSend::class);
    }
}
