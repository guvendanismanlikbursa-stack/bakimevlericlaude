<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyUser extends Model
{
    protected $fillable = [
        'registered_brand', 'name', 'email', 'phone', 'password', 'email_verified_at', 'status',
        'consent_accepted_at', 'consent_ip', 'signup_lat', 'signup_lng', 'signup_city_name',
    ];

    protected $hidden = ['password', 'consent_ip', 'signup_lat', 'signup_lng'];

    protected function casts(): array
    {
        return [
            'consent_accepted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'signup_lat' => 'float',
            'signup_lng' => 'float',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function offerRequests()
    {
        return $this->hasMany(OfferRequest::class);
    }

    public function savedFacilities()
    {
        return $this->hasMany(FamilySavedFacility::class);
    }

    public function notifications()
    {
        return $this->morphMany(PlatformNotification::class, 'notifiable')->latest();
    }
}
