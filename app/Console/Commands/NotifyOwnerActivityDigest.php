<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Facility;
use App\Models\FacilityQuestion;
use App\Models\Message;
use App\Models\OfferRequest;
use App\Models\VisitRequest;
use Illuminate\Console\Command;

// 18 Ekim 2026: kullanıcının talebi - sahipli kurumlara gelen talepler ve
// kurumların hareketleri eskiden yalnızca kuruma bildiriliyordu; admin elle
// bakmadıkça bir kurumun günlerce cevap vermediğini fark etmiyordu. Her sabah
// tek bir özet bildirim: dünkü hareket sayıları + 24 saattir teklif
// verilmemiş ücret talepleri + cevapsız sorular. Hiç hareket yoksa
// bildirim gönderilmez. Otomatik kontrolün QATEST kurumları hariç.
class NotifyOwnerActivityDigest extends Command
{
    protected $signature = 'owners:daily-digest';

    protected $description = 'Sahipli kurum hareketlerinin günlük özetini adminlere bildirir';

    public function handle(): int
    {
        $ownerFacility = fn ($q) => $q->where('is_claimed', true)->where('is_broker_managed', false)->where('name', 'not like', 'QATEST%');
        $from = now()->subDay()->startOfDay();
        $to = now()->subDay()->endOfDay();

        $requests = OfferRequest::whereNotNull('facility_id')->whereBetween('created_at', [$from, $to])->whereHas('facility', $ownerFacility)->withCount('quotes')->get();
        $visits = VisitRequest::whereBetween('created_at', [$from, $to])->whereHas('facility', $ownerFacility)->count();
        $questions = FacilityQuestion::whereBetween('created_at', [$from, $to])->whereHas('facility', $ownerFacility)->count();
        $facilityMessages = Message::where('sender_type', 'facility')->whereBetween('created_at', [$from, $to])
            ->whereHas('offerRequest.facility', $ownerFacility)->count();

        // Son 7 gün içinde gelmiş, 24 saati geçmiş ve hâlâ hiç teklif verilmemiş ücret talepleri.
        $waiting = OfferRequest::whereNotNull('facility_id')
            ->whereBetween('created_at', [now()->subDays(7), now()->subDay()])
            ->whereHas('facility', $ownerFacility)
            ->whereDoesntHave('quotes')
            ->with('facility:id,name')
            ->get();
        $unansweredQuestions = FacilityQuestion::pending()->where('created_at', '<=', now()->subDay())
            ->where('created_at', '>=', now()->subDays(7))->whereHas('facility', $ownerFacility)->count();

        if ($requests->isEmpty() && ! $visits && ! $questions && ! $facilityMessages && $waiting->isEmpty() && ! $unansweredQuestions) {
            $this->info('Sahipli kurumlarda bildirilecek hareket yok.');

            return self::SUCCESS;
        }

        $lines = ['Dün ('.$from->format('d.m.Y').') sahipli kurumlarda:'];
        $lines[] = "- Ücret/teklif talebi: {$requests->count()} (teklif verilen: ".$requests->where('quotes_count', '>', 0)->count().')';
        $lines[] = "- Ziyaret/kontenjan talebi: {$visits}";
        $lines[] = "- Aile sorusu: {$questions}";
        $lines[] = "- Kurumların ailelere yazdığı mesaj: {$facilityMessages}";

        if ($waiting->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "⚠ 24 saati geçtiği halde teklif verilmemiş {$waiting->count()} talep:";
            foreach ($waiting->groupBy('facility_id')->take(10) as $items) {
                $lines[] = '- '.($items->first()->facility->name ?? 'Kurum').': '.$items->count().' talep';
            }
        }
        if ($unansweredQuestions) {
            $lines[] = "⚠ 24 saati geçmiş cevapsız aile sorusu: {$unansweredQuestions}";
        }

        $body = implode("\n", $lines);
        $title = 'Sahipli kurumlar günlük özet'.($waiting->isNotEmpty() ? " · {$waiting->count()} cevapsız talep" : '');

        foreach (Admin::all() as $admin) {
            try {
                notify_user($admin, 'owner_daily_digest', $title, $body, []);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Sahipli kurum ozet bildirimi gonderilemedi: '.$e->getMessage(), ['admin_id' => $admin->id]);
            }
        }

        $this->info('Ozet gonderildi.');

        return self::SUCCESS;
    }
}
