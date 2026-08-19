<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'requestable_type', 'requestable_id', 'requested_at', 'status', 'processed_at', 'processed_by', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function requestable()
    {
        return $this->morphTo();
    }

    public function processedBy()
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
}
