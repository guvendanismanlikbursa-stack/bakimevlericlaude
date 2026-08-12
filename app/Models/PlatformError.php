<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformError extends Model
{
    protected $fillable = ['source', 'title', 'message', 'context', 'resolved_at'];

    protected $casts = [
        'context' => 'array',
        'resolved_at' => 'datetime',
    ];
}
