<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityInterestLead;
use Illuminate\Http\Request;

// 20 Eylul 2026: kullanicinin talebi - bkz. migration'daki ayni tarihli
// yorum. SADECE on kayitli (sahiplenilmemis VE anlasmali olmayan)
// kurumlarda gorunen "Bilgi ve Fiyat Talep Et" formu.
class FacilityInterestLeadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['website' => 'max:0']);

        $validated = $request->validate([
            'facility_id' => 'required|integer|exists:facilities,id',
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'message' => 'nullable|string|max:1000',
        ]);

        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->findOrFail($validated['facility_id']);

        // 28 Ekim 2026: kullanıcının bildirdiği gerçek hata - bir veli aynı formu 37 kez gönderdi
        // (her biri admin'e ayrı bildirim). Aynı oturumda aynı kuruma ikinci talep oluşturulmaz;
        // oturum kaybolsa bile aynı telefonla 24 saat içinde aynı kuruma tekrar kayıt açılmaz.
        // Kişiye her durumda "talebiniz alındı" gösterilir.
        $gonderilen = (array) $request->session()->get('ilgi_talebi_gonderildi', []);
        $telefon = preg_replace('/\D+/', '', $validated['phone']);
        $tekrar = in_array($facility->id, $gonderilen, true)
            || ($telefon !== '' && FacilityInterestLead::where('facility_id', $facility->id)
                ->where('created_at', '>=', now()->subDay())
                ->get(['phone'])->contains(fn ($l) => preg_replace('/\D+/', '', (string) $l->phone) === $telefon));
        if ($tekrar) {
            $request->session()->put('ilgi_talebi_gonderildi', array_values(array_unique([...$gonderilen, $facility->id])));

            return redirect()->to(url()->previous().'#ilgi-formu')
                ->with('success', 'Talebiniz zaten alındı — ortalama 30 dakika - 1 saat içinde size dönüş yapılacaktır.');
        }

        $lead = FacilityInterestLead::create([
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
        ]);

        // 30 Eylul 2026: kullanicinin bildirdigi gercek hata - bu talepler
        // ADMIN'E HICBIR sekilde bildirilmiyordu (ne panel bildirimi ne
        // mail), admin gunlerce panele girmezse hic haberi olmuyordu.
        // notify_user() hem uygulama-ici bildirimi hem maili tek cagriyla
        // yapar (bkz. helpers.php).
        // 28 Ekim 2026: bildirim (mail + telefon) veliye yanıt döndükten SONRA gönderilir; önceden
        // gönderim bitene kadar sayfa bekliyordu ve veli "olmadı" sanıp tekrar basıyordu.
        \Illuminate\Support\defer(fn () => \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin, 'facility_interest_lead', 'Yeni kurum ilgi talebi',
            "\"{$facility->name}\" (ön kayıtlı kurum) için {$lead->full_name} bilgi/fiyat talebi gönderdi.",
            ['facility_id' => $facility->id, 'lead_id' => $lead->id]
        )));
        $request->session()->put('ilgi_talebi_gonderildi', array_values(array_unique([...$gonderilen, $facility->id])));

        // 15 Ekim 2026: kullanicinin talebi - "en kisa surede" belirsizligi
        // yerine, formun kendisinde de gosterilen somut taahhut (bkz.
        // facilities/show.blade.php ayni tarihli "30 dk-1 sa" metni) burada
        // da tekrarlanir - gonderim SONRASI da ayni guven devam etsin.
        return redirect()->to(url()->previous().'#ilgi-formu')->with('success', 'Talebiniz alındı — ortalama 30 dakika - 1 saat içinde size dönüş yapılacaktır.');
    }
}
