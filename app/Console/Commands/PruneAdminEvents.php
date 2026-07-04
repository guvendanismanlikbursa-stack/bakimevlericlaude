<?php

namespace App\Console\Commands;

use App\Models\AdminEvent;
use App\Models\PlatformNotification;
use Illuminate\Console\Command;

// Bakim: admin_events (islem gunlugu) ve platform_notifications (okunmus
// bildirimler) tablolari surekli buyur. Bu komut periyodik temizlik yapar.
class PruneAdminEvents extends Command
{
    protected $signature = 'admin-events:prune {--days=180 : Bu gunden eski kayitlar silinir}';

    protected $description = 'Belirtilen gunden eski admin islem gunlugu ve okunmus bildirim kayitlarini siler';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deletedEvents = AdminEvent::where('created_at', '<', $cutoff)->delete();
        $deletedNotifications = PlatformNotification::whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $this->info("{$deletedEvents} eski islem gunlugu kaydi, {$deletedNotifications} okunmus bildirim silindi (>{$days} gun).");

        return self::SUCCESS;
    }
}
