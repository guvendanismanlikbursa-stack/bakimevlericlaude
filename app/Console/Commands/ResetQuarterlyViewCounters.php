<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 11 Ekim 2026: kullanicinin talebi - admin/satis-firsatlari sayfasinda
// "bu ceyrek" goruntulenme sayaci icin - ResetMonthlyViewCounters/
// ResetWeeklyViewCounters ile AYNI desen, sadece takvim ceyregi bazinda.
class ResetQuarterlyViewCounters extends Command
{
    protected $signature = 'platform:reset-quarterly-view-counters';

    protected $description = 'Her takvim ceyreginin basinda, "bu ceyrek" goruntulenme sayacini sifirlar (gercek lifetime views_count etkilenmez)';

    public function handle(): int
    {
        $n = DB::table('facilities')
            ->whereNull('deleted_at')
            ->update(['views_count_at_quarter_start' => DB::raw('views_count')]);

        $this->info("{$n} kurumun ceyreklik goruntulenme sayaci sifirlandi.");

        return self::SUCCESS;
    }
}
