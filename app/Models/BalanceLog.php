<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceLog extends Model
{
    protected $fillable = [
        'facility_id', 'type', 'amount', 'credits_amount',
        'balance_after', 'credits_after', 'admin_id', 'note',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'balance_after' => 'float'];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
