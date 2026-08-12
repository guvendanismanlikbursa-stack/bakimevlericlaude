<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\FacilityDailyStat;
use App\Models\OfferRequest;
use App\Models\Quote;
use Illuminate\Console\Command;

// 12 Agustos 2026: kullanicinin talebi - "performans panelim cok yuzeysel,
// gecen aya gore nasilim goremiyorum". Bu komut her gece HER sahiplenilmis
// kurum icin o GUNKU goruntulenme/favori (kumulatif anlik goruntu) ve
// talep/teklif (o gune ozel) sayilarini kaydeder - bkz.
// Facility\DashboardController performans bolumu, facility_daily_stats tablosu.
class SnapshotFacilityDailyStats extends Command
{
    protected $signature = 'facility:snapshot-daily-stats';

    protected $description = 'Sahiplenilmis her kurumun gunluk goruntulenme/favori/talep/teklif sayilarini anlik goruntu olarak kaydeder';

    public function handle(): int
    {
        $today = now()->toDateString();
        $count = 0;

        Facility::where('is_claimed', true)->chunkById(200, function ($facilities) use ($today, &$count) {
            foreach ($facilities as $facility) {
                FacilityDailyStat::updateOrCreate(
                    ['facility_id' => $facility->id, 'date' => $today],
                    [
                        'views_count' => $facility->views_count,
                        'favorites_count' => $facility->favorites_count,
                        'offer_requests_count' => OfferRequest::where('facility_id', $facility->id)->whereDate('created_at', $today)->count(),
                        'quotes_sent_count' => Quote::where('facility_id', $facility->id)->whereDate('created_at', $today)->count(),
                        'quotes_accepted_count' => Quote::where('facility_id', $facility->id)->where('status', 'accepted')->whereDate('updated_at', $today)->count(),
                    ]
                );
                $count++;
            }
        });

        $this->info("{$count} kurum icin gunluk performans anlik goruntusu kaydedildi.");

        return self::SUCCESS;
    }
}
