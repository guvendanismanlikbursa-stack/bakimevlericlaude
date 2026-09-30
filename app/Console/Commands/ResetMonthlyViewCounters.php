<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 30 Eylul 2026: kullanicinin talebi - kurum sayfalarinda gosterilen ANA/
// BUYUK goruntulenme sayaci "bu AY" sayacidir, her ayin 1'inde aksam
// sifirlanir. GERCEK views_count (lifetime, ic analitik) HICBIR ZAMAN
// sifirlanmaz - sadece "ay basi neydi" isaretcisi guncellenir, aylik
// eklenen sayac (boosted_views_count) 0'lanir. Haftalik trend rozetine
// (boosted_views_count_weekly) DOKUNMAZ - o ResetWeeklyViewCounters'in isi.
class ResetMonthlyViewCounters extends Command
{
    protected $signature = 'platform:reset-monthly-view-counters';

    protected $description = 'Her ayin basinda, kurum sayfalarinda gosterilen ana "bu ay" goruntulenme sayacini sifirlar (gercek lifetime views_count etkilenmez)';

    public function handle(): int
    {
        $n = DB::table('facilities')
            ->whereNull('deleted_at')
            ->update([
                'views_count_at_month_start' => DB::raw('views_count'),
                'boosted_views_count' => 0,
            ]);

        $this->info("{$n} kurumun aylik goruntulenme sayaci sifirlandi.");

        return self::SUCCESS;
    }
}
