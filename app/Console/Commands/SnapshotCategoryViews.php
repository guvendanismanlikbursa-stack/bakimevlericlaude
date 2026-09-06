<?php

namespace App\Console\Commands;

use App\Models\CategoryViewSnapshot;
use App\Models\Facility;
use Illuminate\Console\Command;

// 6 Eylul 2026: kullanicinin talebi - bkz. 2026_09_06_090000_create_
// category_view_snapshots_table migration'i ayni tarihli yorum. Her gece,
// HER bolum (brand_scope) icin o anki toplam goruntulenmeyi (TUM yayindaki
// kurumlar, sahiplenme sarti yok) tek satir olarak kaydeder.
class SnapshotCategoryViews extends Command
{
    protected $signature = 'category:snapshot-views';

    protected $description = 'Her bolumun (yasli-bakim/cocuk-bakim/vb.) toplam goruntulenme sayisini gunluk anlik goruntu olarak kaydeder';

    public function handle(): int
    {
        $today = now()->toDateString();

        $rows = Facility::query()
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->whereNull('facilities.deleted_at')
            ->where('facilities.is_published', true)
            ->selectRaw('facility_categories.brand_scope, sum(facilities.views_count) as toplam, count(*) as kurum_sayisi')
            ->groupBy('facility_categories.brand_scope')
            ->get();

        foreach ($rows as $row) {
            CategoryViewSnapshot::updateOrCreate(
                ['brand_scope' => $row->brand_scope, 'date' => $today],
                ['total_views' => (int) $row->toplam, 'facility_count' => (int) $row->kurum_sayisi]
            );
        }

        $this->info("{$rows->count()} bolum icin gunluk goruntulenme anlik goruntusu kaydedildi.");

        return self::SUCCESS;
    }
}
