<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Admin extends Model
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'last_login_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime'];
    }

    public function pushSubscriptions()
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }
}
