<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUser extends Model
{
    protected $fillable = [
        'facility_id', 'name', 'email', 'phone', 'password', 'must_change_password', 'status', 'email_verified_at',
        'signup_lat', 'signup_lng', 'signup_city_name', 'signup_ip',
    ];

    protected $hidden = ['password', 'signup_lat', 'signup_lng'];

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function notifications()
    {
        return $this->morphMany(PlatformNotification::class, 'notifiable')->latest();
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }
}
