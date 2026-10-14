<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareAdvisorLeadSend extends Model
{
    protected $fillable = ['care_advisor_lead_id', 'facility_id'];

    public function lead()
    {
        return $this->belongsTo(CareAdvisorLead::class, 'care_advisor_lead_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
