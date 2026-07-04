<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilySavedFacility extends Model
{
    protected $fillable = ['family_user_id', 'facility_id', 'brand', 'list_type', 'notes'];

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
