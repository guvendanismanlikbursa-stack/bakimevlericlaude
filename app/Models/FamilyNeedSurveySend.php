<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyNeedSurveySend extends Model
{
    protected $fillable = ['family_need_survey_id', 'facility_id', 'sent_by_admin_id'];

    public function familyNeedSurvey()
    {
        return $this->belongsTo(FamilyNeedSurvey::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function sentByAdmin()
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id');
    }
}
