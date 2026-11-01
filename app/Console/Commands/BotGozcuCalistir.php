<?php

namespace App\Console\Commands;

use App\Support\BotGozcu;
use Illuminate\Console\Command;

// 1 Kasım 2026: saatlik bot gözcüsü - şüpheli ağları kaydeder, olağan dışı durumda adminlere uyarı gönderir.
class BotGozcuCalistir extends Command
{
    protected $signature = 'platform:bot-gozcu';

    protected $description = 'Erişim kayıtlarından şüpheli ağları bulur, gerekirse adminlere e-posta ve bildirim gönderir';

    public function handle(): int
    {
        $gonderilen = BotGozcu::calistir(fn ($m) => $this->line($m));
        $this->info('Gönderilen uyarı: '.count($gonderilen));

        return self::SUCCESS;
    }
}
