<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilySavedFacility extends Model
{
    protected $fillable = ['family_user_id', 'facility_id', 'brand', 'list_type', 'notes'];

    protected function casts(): array
    {
        return ['family_user_id' => 'integer', 'facility_id' => 'integer'];
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
