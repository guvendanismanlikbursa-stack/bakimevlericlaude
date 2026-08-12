<?php

namespace App\Console\Commands;

use App\Models\FacilityReview;
use App\Models\OfferRequest;
use Illuminate\Console\Command;

// 12 Agustos 2026: kullanicinin talebi - "yorum yazmaya davet edilmiyorum,
// yorum sayisinin az kalmasinin bir nedeni bu olabilir". Teklifi kabul edip
// en az 3 gun gecen ama o kurum icin hic yorum yazmamis aileleri gunde bir
// kez tarayip tek seferlik bir davet (bildirim + e-posta, bkz. notify_user())
// gonderir. review_invited_at alani ayni talebe ikinci kez davet gitmesini engeller.
class InviteFamiliesToReview extends Command
{
    protected $signature = 'reviews:invite-families {--days=3 : Teklif kabul edildikten kac gun sonra davet gonderilir}';

    protected $description = 'Teklifi kabul edip henuz yorum yazmamis aileleri yorum birakmaya davet eder';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $candidates = OfferRequest::whereNotNull('accepted_quote_id')
            ->whereNotNull('family_user_id')
            ->whereNull('review_invited_at')
            ->where('updated_at', '<=', $cutoff)
            ->with(['familyUser', 'acceptedQuote.facility'])
            ->get();

        $invited = 0;

        foreach ($candidates as $offerRequest) {
            $facility = $offerRequest->acceptedQuote?->facility;
            $family = $offerRequest->familyUser;

            if (! $facility || ! $family) {
                continue;
            }

            $alreadyReviewed = FacilityReview::where('facility_id', $facility->id)
                ->where('family_user_id', $family->id)
                ->exists();

            if ($alreadyReviewed) {
                $offerRequest->update(['review_invited_at' => now()]);
                continue;
            }

            notify_user(
                $family,
                'review_invite',
                'Deneyiminizi paylaşır mısınız?',
                $facility->name.' ile olan deneyiminizi diğer ailelere yardımcı olması için değerlendirebilirsiniz.',
                ['facility_slug' => $facility->slug]
            );

            $offerRequest->update(['review_invited_at' => now()]);
            $invited++;
        }

        $this->info("{$invited} aileye yorum daveti gonderildi (taranan: {$candidates->count()}).");

        return self::SUCCESS;
    }
}
