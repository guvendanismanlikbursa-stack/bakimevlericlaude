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

        return view('admin.marketers.index', compact('marketers'));
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
     * 28 Eylul 2026: kullanicinin talebi - "admin hangi pazarlamacının
     * hangi kurumlarla görüşüp sonucu nasıl görecek". Tum pazarlamacilarin
     * TUM notlarinin kronolojik gecmisi, pazarlamaci bazinda filtrelenebilir.
     * Notlar zaten kurumun kendisinde (facilities.marketing_status) tutuldugu
     * icin TUM pazarlamacilar arasinda ZATEN canli paylasilir - bu ekran
     * sadece admin'in ayni bilgiyi "kim ne yapti" detayinda gormesini saglar.
     */
    public function conversations(Request $request)
    {
        $query = FacilityMarketingNote::with(['facility.city', 'facility.category', 'marketer'])->latest();

        if ($request->filled('marketer')) {
            $query->where('marketer_id', $request->integer('marketer'));
        }

        $notes = $query->paginate(30)->withQueryString();
        $marketers = Marketer::orderBy('name')->get();

        return view('admin.marketers.conversations', compact('notes', 'marketers'));
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
}
