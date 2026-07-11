<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatWorkingHour extends Model
{
    protected $fillable = ['weekday', 'open_time', 'close_time', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Su an operator musait mi? (Europe/Istanbul uygulama saat dilimine gore.)
     */
    public static function isCurrentlyOpen(): bool
    {
        $now = now();
        $today = static::where('weekday', (int) $now->format('w'))->first();

        if (! $today || ! $today->is_active) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $today->open_time && $currentTime <= $today->close_time;
    }
}
