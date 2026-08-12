<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUser extends Model
{
    protected $fillable = [
        'facility_id', 'name', 'email', 'phone', 'password', 'must_change_password', 'status', 'email_verified_at',
        'signup_lat', 'signup_lng', 'signup_city_name', 'signup_ip', 'google_id', 'avatar_url',
    ];

    protected $hidden = ['password', 'signup_lat', 'signup_lng'];

    // 28 Temmuz 2026: facility_id cast'siz oldugu icin MySQL/PDO'dan STRING
    // donuyordu; Facility\QuoteController::store() bunu OfferRequest::
    // facility_id (ORADA cast'li, int) ile === (strict) kiyaslayinca
    // int(6844) === string("6844") HER ZAMAN false donup kurum yetkilisi
    // kendisine dogrudan gelen HICBIR teklif talebine fiyat teklifi
    // veremiyordu (403) - canli uctan uca testte bulundu. Ayni hata sinifi
    // OfferRequest::family_user_id icin 13 Temmuz 2026'da zaten bir kere
    // bulunup duzeltilmisti (bkz. o modeldeki yorum), bu kez diger taraftaki
    // (FacilityUser) eksik cast'ti.
    protected function casts(): array
    {
        return [
            'facility_id' => 'integer',
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

    public function pushSubscriptions()
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }
}
