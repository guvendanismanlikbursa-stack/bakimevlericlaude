<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTopup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'facility_id', 'facility_user_id', 'subscription_package_id', 'amount', 'receipt_path',
        'note', 'status', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'reviewed_at' => 'datetime'];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function facilityUser()
    {
        return $this->belongsTo(FacilityUser::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }
}
