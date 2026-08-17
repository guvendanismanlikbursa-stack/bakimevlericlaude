<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledJobRun extends Model
{
    protected $fillable = [
        'job_name',
        'expected_frequency_minutes',
        'last_success_at',
        'last_failure_at',
        'consecutive_failures',
        'last_output',
    ];

    protected $casts = [
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    /**
     * 17 Agustos 2026: son basarili calismadan bu yana gecen sure, beklenen
     * sikligin 1.5 katini asiyorsa "gecikmede" sayilir - tam sinirda kucuk
     * gecikmeleri (sunucu yogunlugu vb.) yanlis alarm olarak isaretlememek
     * icin makul bir tolerans payi.
     */
    public function isOverdue(): bool
    {
        if (! $this->last_success_at) {
            return true;
        }

        return $this->last_success_at->diffInMinutes(now()) > $this->expected_frequency_minutes * 1.5;
    }
}
