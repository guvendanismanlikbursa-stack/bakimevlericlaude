<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityMarketingNote;
use App\Models\Marketer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// 27 Eylul 2026: kullanicinin talebi - CRM'e girecek pazarlamaci
// hesaplarini (telefon+sifre) sadece admin olusturur/yonetir, ayri bir
// self-servis kayit YOKTUR.
class MarketerController extends Controller
{
    public function index()
    {
        $marketers = Marketer::withCount('notes')->orderBy('name')->get();
        $cities = \App\Models\City::orderBy('name')->get();
        $categories = \App\Models\FacilityCategory::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->id => districts_for_city($city->name)]);

        return view('admin.marketers.index', compact('marketers', 'cities', 'categories', 'districtMap'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
        ]);

        if (Marketer::all()->contains(fn (Marketer $m) => normalize_phone_digits($m->phone) === normalize_phone_digits($data['phone']))) {
            return back()->withErrors(['phone' => 'Bu telefon numarasıyla zaten bir pazarlamacı hesabı var.'])->withInput();
        }

        $password = Str::password(10, symbols: false);

        $marketer = Marketer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        log_admin_event('marketer_created', $marketer);

        return back()->with('success', "{$marketer->name} eklendi. Giriş şifresi: {$password} (bu şifre sadece şimdi gösterilir, not alın).");
    }

    public function toggleActive(Marketer $marketer)
    {
        $marketer->update(['is_active' => ! $marketer->is_active, 'is_logged_in' => false]);

        log_admin_event('marketer_status_toggled', $marketer, ['is_active' => $marketer->is_active]);

        return back()->with('success', $marketer->is_active ? "{$marketer->name} tekrar aktif edildi." : "{$marketer->name} devre dışı bırakıldı.");
    }

    /**
     * 15 Ekim 2026: kullanicinin talebi - artik calismayan pazarlamacilarin
     * hesabini TAMAMEN silebilsin ve bu kisiler BIR DAHA ASLA giris
     * yapamasin. Once is_active=false + is_logged_in=false (acik oturum
     * varsa MarketerAuth middleware'i bir sonraki istekte hemen duser),
     * sonra soft-delete (bkz. Marketer::class use SoftDeletes) - Marketer::
     * find()/all() silinmis hesabi bir daha asla bulamaz, giris ve acik
     * oturum kontrolu BAŞKA BİR KOD DEĞİŞİKLİĞİNE GEREK KALMADAN kendiliginden
     * calisir. Soft-delete (hard degil) secildi ki FacilityMarketingNote
     * gecmisi (admin "Tum Gorusmeler" ekrani) kaybolmasin - bkz.
     * FacilityMarketingNote::marketer() withTrashed().
     */
    public function destroy(Marketer $marketer)
    {
        $marketer->update(['is_active' => false, 'is_logged_in' => false]);
        $marketer->delete();

        log_admin_event('marketer_deleted', $marketer);

        return back()->with('success', "{$marketer->name} silindi, bir daha giriş yapamaz.");
    }

    /**
     * 28 Eylul 2026: kullanicinin talebi - "admin hangi pazarlamacının
     * hangi kurumlarla görüşüp sonucu nasıl görecek". Tum pazarlamacilarin
     * TUM notlarinin kronolojik gecmisi, pazarlamaci bazinda filtrelenebilir.
     * Notlar zaten kurumun kendisinde (facilities.marketing_status) tutuldugu
     * icin TUM pazarlamacilar arasinda ZATEN canli paylasilir - bu ekran
     * sadece admin'in ayni bilgiyi "kim ne yapti" detayinda gormesini saglar.
     */
    public function conversations(Request $request)
    {
        // 25 Ekim 2026: ?tur=doktor ile doktor görüşmeleri (CRM'e doktorlar eklendi).
        $doktor = $request->query('tur') === 'doktor';
        $query = $doktor
            ? \App\Models\ExpertMarketingNote::with(['expert.city', 'marketer'])->latest()
            : FacilityMarketingNote::with(['facility.city', 'facility.category', 'marketer'])->latest();

        if ($request->filled('marketer')) {
            $query->where('marketer_id', $request->integer('marketer'));
        }

        $notes = $query->paginate(30)->withQueryString();
        $marketers = Marketer::orderBy('name')->get();

        // 19 Ekim 2026: "kurum sitesi" fikri ölçümü - her kurumun EN SON cevabı sayılır.
        $answers = FacilityMarketingNote::where(fn ($q) => $q->whereNotNull('website_status')->orWhereNotNull('site_offer_interest'))
            ->orderByDesc('id')->get(['facility_id', 'website_status', 'site_offer_interest']);
        $latestWebsite = $answers->whereNotNull('website_status')->unique('facility_id');
        $latestInterest = $answers->whereNotNull('site_offer_interest')->unique('facility_id');
        $siteSurvey = [
            'asked' => $latestWebsite->count(),
            'var' => $latestWebsite->where('website_status', 'var')->count(),
            'yok' => $latestWebsite->where('website_status', 'yok')->count(),
            'sosyal' => $latestWebsite->where('website_status', 'sosyal')->count(),
            'offered' => $latestInterest->count(),
            'ilgilendi' => $latestInterest->where('site_offer_interest', 'ilgilendi')->count(),
        ];

        return view('admin.marketers.conversations', compact('notes', 'marketers', 'siteSurvey', 'doktor'));
    }

    // 29 Eylul 2026: kullanicinin talebi - satis hunisi ayrintili sayfasi,
    // hesaplama mantigi FacilityMarketingNote::funnelStages()'te (admin
    // ana sayfasindaki ozet kutucukla AYNI kaynak, iki yerde ayri hesap yok).
    public function funnel()
    {
        $stages = \App\Models\FacilityMarketingNote::funnelStages();

        return view('admin.marketers.funnel', compact('stages'));
    }

    public function resetPassword(Marketer $marketer)
    {
        $password = Str::password(10, symbols: false);
        $marketer->update(['password' => Hash::make($password)]);

        log_admin_event('marketer_password_reset', $marketer);

        return back()->with('success', "{$marketer->name} için yeni şifre: {$password} (bu şifre sadece şimdi gösterilir, not alın).");
    }

    /**
     * 15 Ekim 2026: kullanicinin talebi - "pazarlamacilar icin il/ilce/
     * kategori sinirlamasi koyabilir miyiz". Hicbir kutu secilmezse alan
     * null kaydedilir (Marketer::hasScopeRestriction() false doner) ve
     * pazarlamaci ESKISI GIBI sinirsiz gorur - geriye donuk uyumluluk
     * bozulmaz, bu TAMAMEN opsiyonel bir kisitlama.
     */
    public function updateScope(Request $request, Marketer $marketer)
    {
        $data = $request->validate([
            'city_ids' => 'nullable|array',
            'city_ids.*' => 'integer',
            'districts' => 'nullable|array',
            'districts.*' => 'string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer',
            'can_view_offer_page' => 'nullable|boolean',
        ]);

        $marketer->update([
            'assigned_city_ids' => $data['city_ids'] ?? null,
            'assigned_districts' => $data['districts'] ?? null,
            'assigned_category_ids' => $data['category_ids'] ?? null,
            // 18 Ekim 2026: "Teklif Sayfasını Gör" düğmesi yetkisi (bkz. Marketer\CrmController::offerPage()).
            'can_view_offer_page' => $request->boolean('can_view_offer_page'),
        ]);

        log_admin_event('marketer_scope_updated', $marketer, $data);

        return back()->with('success', "{$marketer->name} için kapsam güncellendi.");
    }
}
