<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 30 Eylul 2026: kullanicinin talebi - kurum sayfalarinda ana/buyuk rakam
// ARTIK AYLIK (bkz. ResetMonthlyViewCounters), bu komut sadece onun
// yanindaki KUCUK "bu hafta" trend rozetini sifirlar. Aylik sayaclara
// (views_count_at_month_start, boosted_views_count) VE gunluk 3-10 hedef
// takibine (view_boost_date/target/added) DOKUNMAZ - o ikisi bu komuttan
// tamamen bagimsiz kendi takvimlerinde calisir.
class ResetWeeklyViewCounters extends Command
{
    protected $signature = 'platform:reset-weekly-view-counters';

    protected $description = 'Her Pazar aksami, kurum sayfalarindaki kucuk "bu hafta" trend rozetini sifirlar (aylik ana sayac ve gercek lifetime views_count etkilenmez)';

    public function handle(): int
    {
        $n = DB::table('facilities')
            ->whereNull('deleted_at')
            ->update([
                'views_count_at_week_start' => DB::raw('views_count'),
                'boosted_views_count_weekly' => 0,
            ]);

        $this->info("{$n} kurumun haftalik trend sayaci sifirlandi.");

        return self::SUCCESS;
    }
}
