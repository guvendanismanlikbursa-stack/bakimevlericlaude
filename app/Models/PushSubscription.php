<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'subscribable_type', 'subscribable_id', 'endpoint', 'endpoint_hash',
        'public_key', 'auth_token', 'content_encoding', 'user_agent',
    ];

    public function subscribable()
    {
        return $this->morphTo();
    }
}
