<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 30 Eylul 2026: kullanicinin talebi - "kurumlar icin siteyi cazip
// duruma getirelim" - her yayindaki kuruma her gun farkli rastgele
// saatlerde toplam 3-10 arasi "eklenen" goruntulenme eklenir. Saatlik
// calisir (routes/console.php), her calistirmada HER kurum icin bugunun
// hedefine ulasilmadiysa rastgele bir sansla (~%35) kucuk bir parca eklenir -
// boylece gun icinde farkli saatlere yayilir, tek seferde toplu eklenmez.
// GERCEK views_count'a HICBIR ZAMAN DOKUNULMAZ. Eklenen miktar AYNI ANDA
// iki ayri sutuna yazilir: boosted_views_count (AYLIK kapsam, ay basinda
// sifirlanir) ve boosted_views_count_weekly (HAFTALIK kapsam, Pazar aksami
// sifirlanir) - ic analitik/en-cok-goruntulenen siralamasi etkilenmesin
// diye (bkz. Facility::monthlyDisplayViewsCount()/displayViewsCount()).
// updated_at'e DOKUNULMAZ (DB::table() ham update - "son guncellenen
// kurumlar" gibi tarih-bazli listeler binlerce kurumun ayni anda "yeni
// guncellendi" gorunmesiyle bozulmasin diye).
class BoostFacilityViews extends Command
{
    protected $signature = 'platform:boost-facility-views';

    protected $description = 'Yayindaki her kuruma gun icinde rastgele saatlerde, gunluk 3-10 araligina kadar "eklenen" goruntulenme ekler (gercek views_count ayri kalir)';

    public function handle(): int
    {
        // Gece yarisi - sabah 08 arasi views eklenmesi ("gece 3'te goruntulenme"
        // gibi) inandiricilikizi kacirir - sadece makul saatlerde calisir.
        $hour = (int) now()->format('G');
        if ($hour < 8 || $hour > 23) {
            $this->info('Gece saati, bu turda hic ekleme yapilmadi.');

            return self::SUCCESS;
        }

        $today = now()->toDateString();
        $facilities = DB::table('facilities')
            ->whereNull('deleted_at')
            ->where('is_published', true)
            ->select('id', 'view_boost_date', 'view_boost_daily_target', 'view_boost_daily_added')
            ->get();

        $updated = 0;
        $totalAdded = 0;

        foreach ($facilities as $f) {
            $date = $f->view_boost_date;
            $target = $f->view_boost_daily_target;
            $added = $f->view_boost_daily_added;
            $changed = false;

            if ($date !== $today) {
                $date = $today;
                $target = random_int(3, 10);
                $added = 0;
                $changed = true;
            }

            if ($added < $target && random_int(1, 100) <= 35) {
                $remaining = $target - $added;
                $add = min($remaining, random_int(1, 2));
                $added += $add;
                $totalAdded += $add;
                $changed = true;

                DB::table('facilities')->where('id', $f->id)->update([
                    'view_boost_date' => $date,
                    'view_boost_daily_target' => $target,
                    'view_boost_daily_added' => $added,
                    'boosted_views_count' => DB::raw('boosted_views_count + '.$add),
                    'boosted_views_count_weekly' => DB::raw('boosted_views_count_weekly + '.$add),
                ]);
                $updated++;

                continue;
            }

            if ($changed) {
                DB::table('facilities')->where('id', $f->id)->update([
                    'view_boost_date' => $date,
                    'view_boost_daily_target' => $target,
                    'view_boost_daily_added' => $added,
                ]);
            }
        }

        $this->info("{$updated} kuruma toplam {$totalAdded} eklenen goruntulenme dagitildi.");

        return self::SUCCESS;
    }
}
