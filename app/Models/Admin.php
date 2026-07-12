<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Admin extends Model
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'last_login_at', 'two_factor_code', 'two_factor_expires_at'];

    protected $hidden = ['password', 'two_factor_code'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime', 'two_factor_expires_at' => 'datetime'];
    }

    public function pushSubscriptions()
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }
}
