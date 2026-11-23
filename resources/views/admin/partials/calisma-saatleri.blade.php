{{-- 23 Kasım 2026: kullanıcının talebi - doktor ve kurum çalışma saatleri admin düzenleme ekranından değiştirilebilsin
     (önceden yalnızca taramayla geliyordu). Kayıt: App\Models\Concerns\CalismaSaatleri::formdanSaatler(). --}}
@php($saatler = old('opening_hours', is_array($kayit->opening_hours) ? $kayit->opening_hours : []))
<div data-saat-kutu style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
  <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
    <span style="font-size:14px;font-weight:600;">🕒 Çalışma saatleri</span>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <button type="button" onclick="this.closest('[data-saat-kutu]').querySelectorAll('input').forEach(function(i){i.value='24 saat açık';})" style="font-size:12px;font-weight:700;padding:5px 10px;border-radius:8px;background:#dcfce7;color:#166534;border:1px solid #86efac;cursor:pointer;">7/24 açık</button>
      <button type="button" onclick="this.closest('[data-saat-kutu]').querySelectorAll('input').forEach(function(i){i.value='';})" style="font-size:12px;font-weight:700;padding:5px 10px;border-radius:8px;background:#fff;color:#6b7280;border:1px solid #d1d5db;cursor:pointer;">Temizle</button>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;">
    @foreach($kayit::GUNLER as $gun)
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
        <span style="width:72px;flex-shrink:0;color:#374151;font-weight:600;">{{ $gun }}</span>
        <input type="text" name="opening_hours[{{ $gun }}]" value="{{ $saatler[$gun] ?? '' }}" placeholder="09:00–18:00" maxlength="60" style="flex:1;min-width:0;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;">
      </label>
    @endforeach
  </div>
  <p style="font-size:12px;color:#6b7280;margin:8px 0 0;">Örnek: <b>09:00–18:00</b>, öğle arası varsa <b>09:00–12:30, 13:30–18:00</b>, çalışılmayan gün <b>Kapalı</b>, sürekli açıksa <b>24 saat açık</b>. Boş bırakılan gün "Kapalı" kaydedilir; hepsi boşsa sitede saat gösterilmez.</p>
</div>
