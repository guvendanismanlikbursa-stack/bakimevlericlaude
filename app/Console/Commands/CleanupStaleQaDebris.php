<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 15 Agustos 2026: kullanicinin talebi - "testler hata bulunca otomatik
// duzeltebilecek bir script yazabilir misin". Kapsam bilerek DAR tutuldu:
// bu komut sadece CheckUserFlows'un (platform:check-user-flows) BASARISIZ
// bir akista "elle incelenmek uzere" kasitli olarak veritabaninda BIRAKTIGI
// test-veri kalintilarini (qatest.daily.*@example.com ile isaretli, GUNE
// OZEL e-postali kayitlar) siler - GERCEK kod hatalarina asla dokunmaz,
// hicbir kod degisikligi yapmaz. Amac: bir hata bulunup (platform_errors'a
// dusup, admin mail alip) incelendikten SONRA, kalintinin veritabaninda
// sonsuza kadar birikmesini onlemek. 2 gunluk bir pencere birakilir ki
// (bkz. --days secenegi) inceleme icin yeterli zaman olsun.
//
// Kalici QA fixture'lari (qatest-daily-{brand}-claimed/-unclaimed kurumlari,
// qatest.daily.{brand}.facility@example.com kurum yetkilisi - GUNE OZEL
// DEGIL, her gun idempotent olarak yeniden kullanilir) bu komutun kapsami
// DISINDA - hicbir zaman silinmez.
class CleanupStaleQaDebris extends Command
{
    protected $signature = 'platform:cleanup-stale-qa-debris {--days=2 : Bu gunden eski, gune-ozel e-postali gunluk kontrol kalintilari silinir}';

    protected $description = 'CheckUserFlows (platform:check-user-flows) basarisiz akislarda inceleme icin biraktigi eski test-veri kalintilarini (qatest.daily.*@example.com) temizler - kod veya gercek kullanici verisine dokunmaz';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $pattern = 'qatest.daily.%@example.com';

        $counts = [];

        // offer_requests once (bagli quotes/messages once silinmeli - FK).
        $staleOfferRequestIds = DB::table('offer_requests')
            ->where('email', 'like', $pattern)
            ->where('created_at', '<', $cutoff)
            ->pluck('id');
        if ($staleOfferRequestIds->isNotEmpty()) {
            DB::table('messages')->whereIn('offer_request_id', $staleOfferRequestIds)->delete();
            DB::table('quotes')->whereIn('offer_request_id', $staleOfferRequestIds)->delete();
            $counts['offer_requests'] = DB::table('offer_requests')->whereIn('id', $staleOfferRequestIds)->delete();
        } else {
            $counts['offer_requests'] = 0;
        }

        $counts['family_users'] = DB::table('family_users')
            ->where('email', 'like', $pattern)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $counts['facility_claims'] = DB::table('facility_claims')
            ->where('applicant_email', 'like', $pattern)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $counts['facility_registrations'] = DB::table('facility_registrations')
            ->where('applicant_email', 'like', $pattern)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $counts['contact_messages'] = DB::table('contact_messages')
            ->where('email', 'like', $pattern)
            ->where('created_at', '<', $cutoff)
            ->delete();

        // visit_requests/facility_questions e-posta tutmuyor - CheckUserFlows
        // icindeki ayni sabit isaretleyicilerle eslestirilir (bkz. o dosyadaki
        // checkVisitRequest/checkQuestion).
        $counts['visit_requests'] = DB::table('visit_requests')
            ->where('phone', 'like', '05320000%')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $counts['facility_questions'] = DB::table('facility_questions')
            ->where('question', 'like', 'QATEST Daily Soru%')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $total = array_sum($counts);
        if ($total > 0) {
            $this->info("{$total} eski test-veri kalintisi temizlendi: ".json_encode($counts, JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Temizlenecek eski test-veri kalintisi yok.');
        }

        return self::SUCCESS;
    }
}
