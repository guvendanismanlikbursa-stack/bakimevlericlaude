<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitServiceRequest extends Model
{
    public const STATUSES = [
        'yeni' => 'Yeni',
        'iletisime_gecildi' => 'İletişime Geçildi',
        'aktif' => 'Aktif',
        'pasif' => 'Pasif',
    ];

    protected $fillable = [
        'family_user_id', 'facility_id', 'brand', 'patient_name', 'patient_age',
        'patient_condition', 'desired_frequency', 'phone', 'status', 'admin_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'patient_age' => 'integer',
            'admin_reviewed_at' => 'datetime',
        ];
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function reports()
    {
        return $this->hasMany(VisitServiceReport::class)->orderByDesc('visited_at');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
