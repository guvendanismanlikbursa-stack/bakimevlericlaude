<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

// 14 Agustos 2026: kullanicinin talebi - "hizli yanit veren kurum" rozeti
// icin, her sahiplenilmis kurumun SON 90 GUNDE gonderdigi tekliflerde,
// talep (OfferRequest.created_at) ile teklif (Quote.created_at) arasindaki
// ortalama dakikayi hesaplar. En az 3 ornek olmadan ortalama GUVENILIR
// sayilmaz - bu yuzden response_sample_count < 3 olan kurumlarda
// avg_response_minutes bilerek null birakilir (bkz. Facility::hasFastResponseBadge()).
class CalculateFacilityResponseTime extends Command
{
    protected $signature = 'facility:calculate-response-time';

    protected $description = 'Sahiplenilmis her kurumun son 90 gundeki ortalama teklif yanit suresini (dakika) hesaplar';

    public function handle(): int
    {
        $since = now()->subDays(90);
        $count = 0;

        Facility::where('is_claimed', true)->chunkById(200, function ($facilities) use ($since, &$count) {
            foreach ($facilities as $facility) {
                $rows = Quote::query()
                    ->join('offer_requests', 'offer_requests.id', '=', 'quotes.offer_request_id')
                    ->where('quotes.facility_id', $facility->id)
                    ->where('quotes.created_at', '>=', $since)
                    ->select('offer_requests.created_at as requested_at', 'quotes.created_at as quoted_at')
                    ->get();

                $sampleCount = $rows->count();
                $avgMinutes = $sampleCount > 0
                    ? (int) round($rows->avg(fn ($r) => max(0, Carbon::parse($r->requested_at)->diffInMinutes(Carbon::parse($r->quoted_at), false))))
                    : null;

                $facility->forceFill([
                    'avg_response_minutes' => $sampleCount >= 3 ? $avgMinutes : null,
                    'response_sample_count' => $sampleCount,
                ])->saveQuietly();

                $count++;
            }
        });

        $this->info("{$count} kurum icin yanit suresi hesaplandi.");

        return self::SUCCESS;
    }
}
