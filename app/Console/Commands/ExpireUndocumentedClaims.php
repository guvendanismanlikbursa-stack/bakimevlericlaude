<?php

namespace App\Console\Commands;

use App\Models\FacilityClaim;
use Illuminate\Console\Command;

// 13 Agustos 2026: kullanicinin talebi - belge yuklemesi artik basvuru
// aninda ZORUNLU DEGIL (surtunmeyi azaltmak icin), ama bu suistimale
// (baskasinin kurumunu belgesiz sahiplenmeye kalkma) acik kapi birakmamali.
// Bu komut, 24 saattir hala BELGESIZ olan bekleyen basvurulari TAMAMEN
// siler - kurum zaten hicbir zaman is_claimed=true olmamisti (bu SADECE
// admin onayinda gerceklesir, bkz. Admin\FacilityClaimController::approve
// - artik document_path bos oldukca onaylamayi da reddediyor), bu yuzden
// "geri dondurme" asil olarak invitation_status'u (kurum davetleri
// ekranindaki takip durumu) basvuru ONCESI haline sifirlamak anlamina gelir.
class ExpireUndocumentedClaims extends Command
{
    protected $signature = 'claims:expire-undocumented {--hours=24 : Bu saatten eski, hala belgesiz bekleyen basvurular silinir}';

    protected $description = 'Belirtilen saatten eski, hala belge yuklenmemis bekleyen sahiplenme basvurularini siler ve kurumu on-kayitli durumuna dondurur';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $claims = FacilityClaim::where('status', 'pending')
            ->whereNull('document_path')
            ->where('created_at', '<=', $cutoff)
            ->with('facility')
            ->get();

        $count = 0;
        foreach ($claims as $claim) {
            $facility = $claim->facility;

            if ($facility && ! $facility->is_claimed && $facility->invitation_status === 'claimed') {
                $phoneType = classify_phone_type($facility->phone);
                $facility->update([
                    'invitation_status' => match ($phoneType) {
                        'mobile' => 'not_started',
                        'landline' => 'landline_only',
                        default => 'contact_missing',
                    },
                    'invitation_status_at' => now(),
                ]);
            }

            // 13 Agustos 2026: kullanicinin acik talebi - "bilgileri silip"
            // dedigi icin soft-delete (FacilityClaim SoftDeletes kullanir)
            // yetmez, dogrulanmamis basvuru sahibinin ad/e-posta/telefonu
            // kalici olarak kaldirilir.
            $claim->forceDelete();
            $count++;
        }

        $this->info("{$count} belgesiz basvuru {$hours} saat sonra otomatik iptal edildi.");

        return self::SUCCESS;
    }
}
