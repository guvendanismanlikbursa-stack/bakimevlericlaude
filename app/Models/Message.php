<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['offer_request_id', 'sender_type', 'sender_id', 'family_user_id', 'facility_user_id', 'admin_id', 'body', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function offerRequest()
    {
        return $this->belongsTo(OfferRequest::class);
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function facilityUser()
    {
        return $this->belongsTo(FacilityUser::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
