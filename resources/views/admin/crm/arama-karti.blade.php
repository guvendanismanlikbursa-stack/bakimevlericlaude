@extends('admin.layout')
@section('title', 'Arama kartı - '.$f->name)

@section('content')
{{-- 24 Kasım 2026: kullanıcının talebi - ilk aramada satışı kapatmak için "mümkün olan maksimum bilgi" (bkz. App\Support\AramaKarti).
     Inline stil: yeni Tailwind sınıfları FTP deploy'da derlenmiyor. Hiçbir tablo yatay kaydırma gerektirmez. --}}
<style>
  .ak-kart{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);padding:16px 18px;margin-bottom:14px}
  .ak-h{font-size:16px;font-weight:900;color:#111827;margin:0 0 10px}
  .ak-izgara{display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}
  .ak-sayi{border:1.5px solid #eef0f3;border-radius:12px;padding:10px 12px;min-width:0}
  .ak-sayi b{display:block;font-size:22px;color:#111827;line-height:1.1}
  .ak-sayi span{font-size:12.5px;color:#6b7280;font-weight:700}
  .ak-satir{font-size:14px;color:#374151;line-height:1.65;overflow-wrap:anywhere}
  .ak-satir b{color:#111827}
  .ak-soz{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:10px 12px;font-size:14.5px;color:#14532d;line-height:1.55;margin-bottom:8px}
  .ak-uyari{background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:10px 12px;font-size:14px;color:#991b1b}
  .ak-dg{display:inline-flex;align-items:center;gap:4px;border:1.5px solid #e5e7eb;border-radius:10px;padding:7px 12px;font-size:13.5px;font-weight:800;text-decoration:none;color:#374151;background:#fff}
  .ak-rozet{display:inline-block;font-size:12px;font-weight:700;border-radius:999px;padding:2px 9px;background:#f3f4f6;color:#374151;margin:2px 4px 2px 0}
  .ak-liste{margin:0;padding-left:18px;list-style:disc;font-size:14px;color:#374151;line-height:1.7}
  .ak-itiraz{border:1.5px solid #eef0f3;border-radius:12px;padding:10px 12px;margin-bottom:8px}
  .ak-itiraz summary{cursor:pointer;font-weight:900;color:#111827;font-size:14px}
  .ak-itiraz div{font-size:14px;color:#374151;line-height:1.6;margin-top:6px}
  .ak-katman{border:1.5px solid #eef0f3;border-radius:12px;padding:10px 12px;min-width:0}
  .ak-etk{display:block;font-size:12.5px;font-weight:800;color:#6b7280;margin-bottom:3px}
  .ak-girdi{width:100%;box-sizing:border-box;border:1.5px solid #d6dde0;border-radius:10px;padding:7px 10px;font-size:14px;background:#fff;color:#111827}
  .ak-btn{border:0;border-radius:10px;padding:9px 16px;font-size:14px;font-weight:900;background:#111827;color:#fff;cursor:pointer}
  .ak-aile{border-bottom:1px dashed #e5e7eb;padding:8px 0;font-size:14px;color:#374151;line-height:1.55}
</style>
@php
  $kr = $k['kurum']; $il = $k['ilgi']; $bo = $k['bolge']; $ra = $k['rakip']; $yo = $k['yorum']; $ek = $k['eksik']; $ge = $k['gecmis']; $te = $k['teklif'];
  $s = fn ($n) => number_format((float) $n, 0, ',', '.');
  $p1 = fn ($n) => number_format((float) $n, 1, ',', '.');
  $ziyaret = max((int) $il['ay']['ziyaret'], (int) $il['ay']['sunucu']);
  $ziyaret90 = max((int) $il['uc_ay']['ziyaret'], (int) $il['uc_ay']['sunucu']);
  $temas = $il['ay']['arama'] + $il['ay']['whatsapp'] + $il['ay']['yol'];
  $enUcuz = $te ? collect($te['paketler'])->sortBy('fiyat')->first() : null;
  $sahipsiz = ! $f->is_claimed && ! $f->is_broker_managed;
  $turKucuk = mb_strtolower($kr['tur']);
  $bolge90 = $bo['ilce_90'] ?? $bo['il_90'];
  $kapsamAdi = ['mahalle' => 'Mahalle', 'çevre' => 'Yakın çevre', 'ilçe' => 'İlçe', 'il' => 'İl'];
  $kapsamde = ['mahalle' => 'Mahallenizde', 'çevre' => '2 km çevrenizde', 'ilçe' => 'İlçenizde', 'il' => 'İlinizde'];
  $uk = $ra['uye_kapsam'];
  // Açılış cümleleri: yalnız gerçek ve sıfırdan büyük rakamlar
  $soz = [];
  if ($ziyaret > 0) {
      $soz[] = 'Bu ay sayfanıza <b>'.$s($ziyaret).' kişi</b> baktı'.($temas > 0 ? ', bunlardan <b>'.$s($temas).' kişi</b> sizi aramak, WhatsApp\'tan yazmak ya da yol tarifi almak için tıkladı' : '').'.';
  }
  if ($il['ulasamayan_90'] > 0 && $sahipsiz) {
      $soz[] = 'Son 3 ayda <b>'.$s($il['ulasamayan_90']).' aile</b> size ulaşmak istedi ama numaranız sayfada kapalı olduğu için ulaşamadı'.($il['son_ulasmak_isteyen'] ? ' (en son '.$il['son_ulasmak_isteyen'].')' : '').'.';
  }
  if (($bo['ilce_30'] ?? 0) > 0) {
      $soz[] = 'Son 30 günde <b>'.e($kr['yer']).'</b> bölgesinde <b>'.$s($bo['ilce_30']).' aile</b> '.e($turKucuk).' için fiyat teklifi istedi.';
  } elseif ($bo['il_30'] > 0) {
      $soz[] = 'Son 30 günde ilinizde <b>'.$s($bo['il_30']).' aile</b> '.e($turKucuk).' için fiyat teklifi istedi.';
  }
  if ($uk) {
      $soz[] = $kapsamde[$uk['kapsam']].'ki <b>'.$s($uk['uye_sayisi']).' kurum</b> ('.e($uk['etiket']).') bizimle çalışıyor; aileler listede önce onları görüyor.';
  }
  if ($yo['ovgu']) {
      $soz[] = 'Ailelerin yorumlarda en çok övdüğü şey: <b>'.e(mb_strtolower($yo['ovgu'][0]['ad'])).'</b>. Bunu sayfanızda öne çıkaralım.';
  }
  if ($ek['foto'] < 5) {
      $soz[] = 'Sayfanızda sadece <b>'.$ek['foto'].' fotoğraf</b> var'.($ra['ort_foto'] ? ', rakiplerinizde ortalama <b>'.$s($ra['ort_foto']).'</b>' : '').'.';
  }
  $telTuru = ['mobile' => 'cep', 'landline' => 'sabit hat'][$kr['telefon_turu']] ?? $kr['telefon_turu'];
@endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
  <div style="min-width:0;">
    <div style="font-size:13px;color:#6b7280;font-weight:700;">🎯 Arama kartı · {{ now()->format('d.m.Y H:i') }} itibarıyla canlı veri</div>
    <h1 style="font-size:22px;font-weight:900;color:#111827;margin:2px 0 4px;">{{ $kr['ad'] }}</h1>
    <div style="font-size:14px;color:#4b5563;">{{ $kr['tur'] }} · {{ $kr['yer'] }}@if($kr['puan']) · ★ {{ $p1($kr['puan']) }} ({{ $s($kr['yorum_sayisi']) }} yorum)@endif</div>
    <div style="margin-top:6px;">
      <span class="ak-rozet" style="background:#111827;color:#fff;">{{ $kr['durum'] }}</span>
      @if($kr['uye'])<span class="ak-rozet" style="background:#fef3c7;color:#92400e;">Üye{{ $kr['uyelik_bitis'] ? ' · bitiş '.$kr['uyelik_bitis'] : '' }}</span>@endif
      @if(! $kr['yayinda'])<span class="ak-rozet" style="background:#fee2e2;color:#991b1b;">Yayında değil</span>@endif
      @if($ge['durum'])<span class="ak-rozet">Son CRM durumu: {{ \App\Http\Controllers\Marketer\CrmController::STATUSES[$ge['durum']] ?? $ge['durum'] }}{{ $ge['durum_tarihi'] ? ' ('.$ge['durum_tarihi'].')' : '' }}</span>@else<span class="ak-rozet">Hiç aranmadı</span>@endif
    </div>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:6px;">
    @if($kr['telefon'])<a href="tel:{{ preg_replace('/\s+/', '', $kr['telefon']) }}" class="ak-dg" style="border-color:#93c5fd;color:#1d4ed8;">📞 {{ $kr['telefon'] }}</a>@endif
    <a href="{{ route('admin.crm.index') }}" class="ak-dg">← CRM</a>
  </div>
</div>

@if($k['saglik'])<div class="ak-uyari" style="margin-bottom:14px;">⚕️ Sağlık kuruluşu: CRM'de bu kurumlara normalde üyelik teklif edilmiyor (sağlık reklam kuralları). Paketler yine de aşağıda; teklif ederken dikkatli olun.</div>@endif

{{-- 1. Kimi, ne zaman --}}
<div class="ak-kart">
  <h2 class="ak-h">1 · Kimi ve ne zaman arayacaksınız</h2>
  <div class="ak-satir">
    @if($kr['yetkililer'])👤 Panel hesabındaki yetkili: <b>{{ implode(', ', $kr['yetkililer']) }}</b><br>@endif
    @if($kr['basvuranlar'])📝 Sahiplenme başvurusu yapan: <b>{{ implode(', ', $kr['basvuranlar']) }}</b><br>@endif
    @if($kr['son_temas'])☎️ Daha önce görüşülen kişi: <b>{{ $kr['son_temas'] }}</b><br>@endif
    @if(! $kr['yetkililer'] && ! $kr['basvuranlar'] && ! $kr['son_temas'])👤 Yetkili adı sistemde yok. İlk cümle: <b>"Kurum sahibi ya da müdürünüzle görüşebilir miyim?"</b><br>@endif
    🕒 Bugün ({{ $k['saat']['bugun'] }}): <b>{{ $k['saat']['bugun_saat'] ?: 'çalışma saati bilinmiyor' }}</b>@if($k['saat']['kapali']) <span style="color:#b91c1c;font-weight:800;">· bugün kapalı, başka gün arayın</span>@endif<br>
    ✅ En uygun arama saati: <b>{{ $k['saat']['oneri'] }}</b><br>
    @if($kr['telefon'])📱 Numara: <b>{{ $kr['telefon'] }}</b> ({{ $telTuru }})@if($kr['whatsapp']) · <a href="https://wa.me/{{ $kr['whatsapp'] }}" target="_blank" rel="noopener" style="color:#166534;font-weight:800;">WhatsApp</a>@endif<br>@else<span style="color:#b91c1c;font-weight:800;">Telefon numarası yok.</span><br>@endif
    @if($kr['eposta'])✉️ {{ $kr['eposta'] }}<br>@endif
    @if($kr['adres'])📍 {{ $kr['adres'] }}@endif
  </div>
</div>

{{-- 1b. Sahip / yetkili hakkında (24 Kasım 2026: kullanıcının talebi - elle girilir, otomatik toplanmaz) --}}
@php $sp = $k['sahip']['profil']; @endphp
<div class="ak-kart" id="sahip">
  <h2 class="ak-h">👤 Kurum sahibi / yetkili hakkında</h2>
  @if($sp)
    <div class="ak-satir" style="margin-bottom:10px;">
      @foreach(\App\Support\AramaKarti::SAHIP_ALANLARI as $alan => $etiket)
        @if(! empty($sp[$alan]))<div><b>{{ $etiket }}:</b> <span style="white-space:pre-line;">{{ $sp[$alan] }}</span></div>@endif
      @endforeach
      @if(! empty($sp['guncelleme']))<div style="font-size:12px;color:#9ca3af;margin-top:4px;">Son güncelleme: {{ $sp['guncelleme'] }}</div>@endif
    </div>
  @else
    <div class="ak-satir" style="margin-bottom:8px;color:#6b7280;">Henüz bilgi girilmemiş. Aşağıdaki bağlantılarla araştırıp bulduklarınızı kaydedin.</div>
  @endif
  <div style="font-size:13px;font-weight:800;color:#374151;margin-bottom:4px;">🔎 Araştırma bağlantıları (yeni sekmede açılır)</div>
  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
    @foreach($k['sahip']['baglantilar'] as $b)<a href="{{ $b['url'] }}" target="_blank" rel="noopener noreferrer" class="ak-dg" style="font-size:12.5px;padding:5px 10px;">{{ $b['ad'] }}</a>@endforeach
  </div>
  <details @if(! $sp) open @endif>
    <summary style="cursor:pointer;font-size:14px;font-weight:900;color:#0d6e6a;">✍️ {{ $sp ? 'Bilgileri düzenle' : 'Bilgi ekle' }}</summary>
    <form method="POST" action="{{ route('admin.crm.owner-profile', $f) }}" style="margin-top:10px;display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
      @csrf
      @foreach(\App\Support\AramaKarti::SAHIP_ALANLARI as $alan => $etiket)
        <label style="display:block;min-width:0;"><span class="ak-etk">{{ $etiket }}</span>
          @if(in_array($alan, ['ad', 'gorev'], true))
            <input name="{{ $alan }}" value="{{ old($alan, $sp[$alan] ?? ($alan === 'ad' ? ($k['sahip']['adlar'][0] ?? '') : '')) }}" maxlength="2000" class="ak-girdi">
          @else
            <textarea name="{{ $alan }}" rows="2" maxlength="2000" class="ak-girdi">{{ old($alan, $sp[$alan] ?? '') }}</textarea>
          @endif
        </label>
      @endforeach
      <div style="grid-column:1/-1;"><button type="submit" class="ak-btn">💾 Kaydet</button> <span style="font-size:12px;color:#6b7280;">Yalnız admin görür. Alanı boşaltıp kaydederseniz silinir.</span></div>
    </form>
  </details>
</div>

{{-- 2. Açılış --}}
<div class="ak-kart">
  <h2 class="ak-h">2 · İlk 30 saniye: söylenecek somut rakamlar</h2>
  @forelse($soz as $c)
    <div class="ak-soz">💬 {!! $c !!}</div>
  @empty
    <div class="ak-satir">Bu kurum için henüz güçlü bir rakam yok; bölge talebi ve eksiklerle açın.</div>
  @endforelse
</div>

{{-- 3. Sayfasına gelen ilgi --}}
<div class="ak-kart">
  <h2 class="ak-h">3 · Sayfasına gelen gerçek ilgi</h2>
  <div class="ak-izgara">
    <div class="ak-sayi"><b>{{ $s($ziyaret) }}</b><span>Ziyaretçi (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($ziyaret90) }}</b><span>Ziyaretçi (90 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['ay']['arama']) }}</b><span>"Ara"ya basan (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['ay']['whatsapp']) }}</b><span>WhatsApp'a basan (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['ay']['yol']) }}</b><span>Yol tarifi alan (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['ay']['istek']) }}</b><span>Numara kapalı, forma düşen (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['ulasamayan_90']) }}</b><span>Ulaşmak isteyen aile (90 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['teklif_istegi_90']) }}</b><span>Fiyat teklifi isteyen (90 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($il['favori']) }}</b><span>Favorilere ekleyen</span></div>
    <div class="ak-sayi"><b>{{ $s($il['foto_bakilma']) }}</b><span>Fotoğraflarına bakılma</span></div>
  </div>
  <div class="ak-satir" style="font-size:12.5px;color:#6b7280;margin-top:8px;">Ziyaretçi sayısı gerçek tarayıcı ölçümüdür ({{ $il['olcum_basi'] }}'den beri), şişirilmiş görüntülenme kullanılmaz. Tüm zamanlarda teklif isteği: {{ $s($il['teklif_istegi']) }}, kurumun verdiği teklif: {{ $s($il['verilen_teklif']) }}.</div>
</div>

{{-- 3b. Kuruma ulaşmak isteyen aileler (24 Kasım 2026: kullanıcının talebi - ad ve telefon da görünür) --}}
<div class="ak-kart">
  <h2 class="ak-h">👨‍👩‍👧 Bu kuruma ulaşmak / teklif almak isteyen aileler (son 6 ay)</h2>
  @forelse($k['aileler'] as $a)
    <div class="ak-aile">
      <b>{{ $a['tarih'] }}</b> · <span class="ak-rozet" style="{{ $a['tur'] === 'Ulaşmak istedi' ? 'background:#fef3c7;color:#92400e;' : 'background:#e0f2fe;color:#075985;' }}">{{ $a['tur'] }}</span>
      <b>{{ $a['ad'] ?: '—' }}</b>
      @if($a['telefon']) · <a href="tel:{{ preg_replace('/\s+/', '', $a['telefon']) }}" style="color:#1d4ed8;font-weight:800;">{{ $a['telefon'] }}</a>@endif
      @if($a['eposta']) · {{ $a['eposta'] }}@endif
      @if($a['iletildi']) · <span style="color:#166534;">kuruma iletildi {{ $a['iletildi'] }}</span>@else · <span style="color:#b91c1c;">kuruma iletilmedi</span>@endif
      @if($a['mesaj'])<div style="color:#6b7280;font-size:13px;">“{{ $a['mesaj'] }}”</div>@endif
    </div>
  @empty
    <div class="ak-satir" style="color:#6b7280;">Son 6 ayda bu kuruma doğrudan talep yok.</div>
  @endforelse
</div>

{{-- 4. Bölge talebi --}}
<div class="ak-kart">
  <h2 class="ak-h">4 · Bölgedeki aile talebi ({{ $kr['tur'] }})</h2>
  <div class="ak-izgara">
    @if($bo['ilce_30'] !== null)
      <div class="ak-sayi"><b>{{ $s($bo['ilce_30']) }}</b><span>İlçede teklif isteyen aile (30 gün)</span></div>
      <div class="ak-sayi"><b>{{ $s($bo['ilce_90']) }}</b><span>İlçede teklif isteyen aile (90 gün)</span></div>
    @endif
    <div class="ak-sayi"><b>{{ $s($bo['il_30']) }}</b><span>İlde teklif isteyen aile (30 gün)</span></div>
    <div class="ak-sayi"><b>{{ $s($bo['il_90']) }}</b><span>İlde teklif isteyen aile (90 gün)</span></div>
    @if($bo['ilce_ulasma_30'] !== null)<div class="ak-sayi"><b>{{ $s($bo['ilce_ulasma_30']) }}</b><span>İlçedeki kurumlara ulaşmak isteyen (30 gün)</span></div>@endif
    <div class="ak-sayi"><b>{{ $s($bo['il_arama_30']) }}</b><span>İlde bu türde site içi arama (30 gün)</span></div>
  </div>
  @if($bo['butce'])<div class="ak-satir" style="margin-top:8px;">💰 Ailelerin yazdığı ortalama bütçe: <b>{{ $bo['butce'] }}</b>@if($kr['fiyat']) · Kurumun fiyatı: <b>{{ $kr['fiyat'] }}</b>@endif</div>@endif
</div>

{{-- 5. Rakipler: mahalle / 2 km çevre / ilçe / il (24 Kasım 2026: kullanıcının talebi - il verisi silinmeden dar katmanlar eklendi) --}}
<div class="ak-kart">
  <h2 class="ak-h">5 · Rakipler (mahalle, çevre, ilçe ve il)</h2>
  @if($ra['ilce_tahmin'])<div class="ak-satir" style="font-size:12.5px;color:#92400e;margin-bottom:8px;">Bu kurumun ilçesi kayıtlı değil; ilçe, en yakın kurumların ilçesinden tahmin edildi: <b>{{ $ra['ilce'] }}</b>.</div>@endif
  <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));">
    @foreach($ra['seviyeler'] as $sv)
      <div class="ak-katman" style="{{ $sv['kapsam'] === $ra['kapsam'] ? 'border-color:#111827;' : '' }}">
        <div style="font-size:12.5px;font-weight:900;color:#6b7280;text-transform:uppercase;">{{ $kapsamAdi[$sv['kapsam']] ?? $sv['kapsam'] }}</div>
        <div style="font-weight:900;color:#111827;font-size:15px;">{{ $sv['etiket'] }}</div>
        <div class="ak-satir" style="font-size:13.5px;margin-top:4px;">
          Kurum: <b>{{ $s($sv['toplam']) }}</b> · bizimle çalışan: <b>{{ $s($sv['uye_sayisi']) }}</b><br>
          Bu kurumun sırası: puan <b>{{ $sv['sira_puan'] ?? '—' }}.</b> · yorum <b>{{ $sv['sira_yorum'] ?? '—' }}.</b> · ilgi <b>{{ $sv['sira_ilgi'] ?? '—' }}.</b><br>
          Rakip ort.: puan <b>{{ $sv['ort_puan'] !== null ? $p1($sv['ort_puan']) : '—' }}</b> · yorum <b>{{ $sv['ort_yorum'] !== null ? $s($sv['ort_yorum']) : '—' }}</b> · foto <b>{{ $sv['ort_foto'] !== null ? $s($sv['ort_foto']) : '—' }}</b>@if($sv['ort_fiyat']) · fiyat <b>{{ $s($sv['ort_fiyat']) }} ₺</b>@endif @if($sv['site_orani'] !== null) · sitesi olan <b>%{{ $sv['site_orani'] }}</b>@endif
        </div>
        @if($sv['uyeler'] || $sv['en_iyi'])
          <details style="margin-top:6px;" @if($sv['kapsam'] === $ra['kapsam']) open @endif>
            <summary style="cursor:pointer;font-size:13px;font-weight:800;color:#0d6e6a;">İsimler</summary>
            @if($sv['uyeler'])
              <div style="font-size:12.5px;font-weight:800;color:#374151;margin-top:6px;">Bizimle çalışanlar:</div>
              <ul class="ak-liste" style="font-size:13px;">@foreach($sv['uyeler'] as $u)<li><b>{{ $u['ad'] }}</b>{{ $sv['kapsam'] === 'il' && $u['ilce'] ? ' ('.$u['ilce'].')' : '' }} · ★ {{ $u['puan'] ? $p1($u['puan']) : '—' }} · {{ $u['yorum'] }} yorum · {{ $u['foto'] }} foto</li>@endforeach</ul>
            @endif
            @if($sv['en_iyi'])
              <div style="font-size:12.5px;font-weight:800;color:#374151;margin-top:6px;">En çok yorum alanlar:</div>
              <ul class="ak-liste" style="font-size:13px;">@foreach($sv['en_iyi'] as $u)<li>{{ $u['ad'] }} · ★ {{ $u['puan'] ? $p1($u['puan']) : '—' }} · {{ $u['yorum'] }} yorum{{ $u['uye'] ? ' · bizimle' : '' }}</li>@endforeach</ul>
            @endif
          </details>
        @endif
      </div>
    @endforeach
  </div>
  <div class="ak-satir" style="font-size:12.5px;color:#6b7280;margin-top:8px;">Siyah çerçeveli katman, en az 4 kurum bulunan en dar bölgedir; itiraz cevaplarındaki ortalamalar ondan gelir.</div>
</div>

{{-- 6. Yorumlar --}}
<div class="ak-kart">
  <h2 class="ak-h">6 · Ailelerin yorumları: güçlü ve zayıf yanlar ({{ $s($yo['sayi']) }} yorum)</h2>
  @if($yo['sayi'])
    <div class="ak-satir">Puan dağılımı: @foreach($yo['dagilim'] as $p => $n)<span class="ak-rozet">{{ $p }}★ {{ $n }}</span>@endforeach @if($yo['sira_etiketi'])<span class="ak-rozet" style="background:#dcfce7;color:#166534;">{{ $yo['sira_etiketi'] }}</span>@endif</div>
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-top:10px;">
      <div><div style="font-weight:900;color:#166534;">👍 Övülen (konuşmada vurgulayın)</div>
        @if($yo['ovgu'])<ul class="ak-liste">@foreach($yo['ovgu'] as $x)<li>{{ $x['ad'] }} <span style="color:#6b7280;">({{ $x['n'] }} yorum)</span></li>@endforeach</ul>@else<div class="ak-satir" style="color:#6b7280;">Belirgin bir konu yok.</div>@endif
      </div>
      <div><div style="font-weight:900;color:#b91c1c;">👎 Şikâyet (çözüm olarak sunun)</div>
        @if($yo['sikayet'])<ul class="ak-liste">@foreach($yo['sikayet'] as $x)<li>{{ $x['ad'] }} <span style="color:#6b7280;">({{ $x['n'] }} yorum)</span></li>@endforeach</ul>@else<div class="ak-satir" style="color:#6b7280;">Belirgin şikâyet yok.</div>@endif
        @if($yo['yanitsiz_kotu'])<div class="ak-satir" style="color:#b91c1c;">{{ $yo['yanitsiz_kotu'] }} olumsuz yoruma kurum cevap vermemiş (panelden cevap verebilir).</div>@endif
      </div>
    </div>
    @if($yo['iyi_alinti'])<div class="ak-soz" style="margin-top:10px;">⭐ {{ $yo['iyi_alinti']['puan'] }}★ “{{ $yo['iyi_alinti']['metin'] }}” <span style="color:#6b7280;font-size:12px;">{{ $yo['iyi_alinti']['zaman'] }}</span></div>@endif
    @if($yo['kotu_alinti'])<div class="ak-uyari" style="margin-top:6px;">{{ $yo['kotu_alinti']['puan'] }}★ “{{ $yo['kotu_alinti']['metin'] }}” <span style="color:#6b7280;font-size:12px;">{{ $yo['kotu_alinti']['zaman'] }}</span></div>@endif
  @else
    <div class="ak-satir">Yorum yok. Söylenebilecek: "Aileler karar verirken yorumlara bakıyor; memnun ailelerinizden yorum toplamanıza yardım ediyoruz."</div>
  @endif
</div>

{{-- 7. Eksikler --}}
<div class="ak-kart">
  <h2 class="ak-h">7 · Sayfasındaki eksikler (doluluk {{ $ek['puan'] }}/100)</h2>
  @if($ek['liste'])<ul class="ak-liste">@foreach($ek['liste'] as $x)<li>{{ $x }}</li>@endforeach</ul>@else<div class="ak-satir">Sayfası eksiksiz.</div>@endif
  <div class="ak-satir" style="margin-top:8px;">
    🌐 Kendi sitesi: <b>{{ $kr['site'] ?: 'yok' }}</b>@if($kr['site_durumu']) ({{ $kr['site_durumu'] }})@endif @if($kr['site'])· <a href="{{ $kr['site'] }}" target="_blank" rel="noopener nofollow" style="font-weight:800;">aç, "hakkımızda" kısmına göz atın</a>@endif<br>
    🖥 Bizim hazırladığımız site: <b>{{ $kr['bizim_site'] ?? 'yok' }}</b>
    @if($kr['kapasite']) · 🛏 Kapasite: <b>{{ $kr['kapasite'] }}</b>@endif @if($kr['bos_yer']) · Boş yer: <b>{{ $kr['bos_yer'] }}</b>@endif @if($kr['fiyat']) · Fiyat: <b>{{ $kr['fiyat'] }}</b>@endif
    @if($kr['saatler'])<br>🕒 Çalışma saatleri: {{ $kr['saatler'] }}@endif
  </div>
</div>

{{-- 8. Teklif --}}
@if($te)
<div class="ak-kart">
  <h2 class="ak-h">8 · Bu kuruma uygun teklif</h2>
  @if($te['paketler'])
    <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));">
      @foreach($te['paketler'] as $p)
        <div class="ak-sayi">
          <div style="font-weight:900;color:#111827;">{{ $p['ad'] }}</div>
          <b style="margin-top:4px;">{{ $s($p['fiyat']) }} ₺ @if($p['liste'])<s style="font-size:13px;color:#9ca3af;font-weight:700;">{{ $s($p['liste']) }} ₺</s>@endif</b>
          <span>{{ $p['ay'] }} ay · ayda ≈ {{ $s($p['aylik']) }} ₺{{ $p['bedava'] ? ' · '.$p['bedava'] : '' }}{{ $p['site'] ? ' · site dahil' : '' }}</span>
          @if($p['ozellik'])<ul class="ak-liste" style="font-size:13px;margin-top:4px;">@foreach($p['ozellik'] as $o)<li>{{ is_array($o) ? ($o['ad'] ?? $o['label'] ?? $o['text'] ?? '') : $o }}</li>@endforeach</ul>@endif
        </div>
      @endforeach
    </div>
    @if($enUcuz && $f->price_min)
      <div class="ak-soz" style="margin-top:10px;">💡 Tek bir ailenin aylık ücreti (≈ {{ $s($f->price_min) }} ₺), en uygun paketin ({{ $s($enUcuz['fiyat']) }} ₺) <b>%{{ (int) round($f->price_min / max(1, $enUcuz['fiyat']) * 100) }}</b>'i kadar: <b>tek bir aile bile paketi karşılar.</b></div>
    @endif
  @else
    <div class="ak-satir">Aktif üyelik paketi yok.</div>
  @endif
  <div class="ak-satir" style="margin-top:6px;">🖥 Site paketleri: @foreach($te['site_paketleri'] as $ad => $u)<span class="ak-rozet">{{ $ad }}{{ $u ? ' · '.$u.' ₺' : '' }}</span>@endforeach</div>
</div>
@endif

{{-- 9. İtirazlar --}}
<div class="ak-kart">
  <h2 class="ak-h">9 · İtiraz gelirse</h2>
  <details class="ak-itiraz"><summary>"Pahalı / bütçemiz yok"</summary><div>
    @if($enUcuz)Ayda yaklaşık <b>{{ $s($enUcuz['aylik']) }} ₺</b>.@endif
    @if($f->price_min) Size gelecek tek bir aile ayda <b>{{ $s($f->price_min) }} ₺</b> getiriyor; yılda bir aile bile paketi fazlasıyla karşılar.@endif
    @if($bolge90 > 0) Bölgenizde son 3 ayda <b>{{ $s($bolge90) }} aile</b> teklif istedi; bu ailelerin karşısına ilk siz çıkarsınız.@endif
  </div></details>
  <details class="ak-itiraz"><summary>"Zaten doluyuz / aileye ihtiyacımız yok"</summary><div>
    Dolu olmanız çok iyi. Gelen aileler arasından size en uygun olanı seçersiniz, bekleme listeniz olur; boş yer açıldığında beklemezsiniz.
    @if($yo['ovgu']) Ailelerin övdüğü <b>{{ mb_strtolower($yo['ovgu'][0]['ad']) }}</b> konusunu sayfanızda öne çıkarır, fiyatınızı rahatça korursunuz.@endif
  </div></details>
  <details class="ak-itiraz"><summary>"Düşüneyim / sonra dönerim"</summary><div>
    Hedef tek arama: o an karar verdirecek adım. <b>Teklif sayfasını şimdi WhatsApp'tan gönderin</b> ve birlikte bakın; sayfasındaki ziyaretçi ve aile talebi rakamları orada canlı yazıyor.
    @if($uk) "{{ $kapsamde[$uk['kapsam']] }} {{ $s($uk['uye_sayisi']) }} kurum şu an bizimle; öne çıkmak için bugün başlamanız yeterli."@endif
  </div></details>
  <details class="ak-itiraz"><summary>"Bize zaten tavsiyeyle geliyorlar"</summary><div>
    Tavsiyeyle gelen aile de önce internetten bakıyor: bu ay sayfanıza <b>{{ $s($ziyaret) }} kişi</b> baktı.
    @if($kr['puan'])Puanınız <b>{{ $p1($kr['puan']) }}</b>@if($ra['ort_puan']), bölge ortalaması {{ $p1($ra['ort_puan']) }}@endif; bunu doğru gösterelim.@endif
  </div></details>
  <details class="ak-itiraz"><summary>"Kendi sitemiz / Instagram'ımız var"</summary><div>
    Aileler kurum ararken karşılaştırma yapıyor; sizi rakiplerinizin yanında görüyorlar. @if($ra['site_orani'] !== null)Bölgedeki kurumların %{{ $ra['site_orani'] }}'inin kendi sitesi var.@endif Üyelik sitenizin yerine değil, sitenize aile getiren bir kapı.
  </div></details>
  <details class="ak-itiraz"><summary>"Daha önce böyle bir şeye para verdik, işe yaramadı"</summary><div>
    Rakamlar tahmin değil, sizin sayfanızın gerçek ölçümü: son 90 günde <b>{{ $s($ziyaret90) }} ziyaretçi</b>, <b>{{ $s($il['ulasamayan_90'] + $il['teklif_istegi_90']) }} aile talebi</b>. Üye olunca bu aileler doğrudan size ulaşır.
  </div></details>
</div>

{{-- 10. Görüşmede gönderilecekler --}}
<div class="ak-kart">
  <h2 class="ak-h">10 · Görüşme sırasında gönderin ve kapatın</h2>
  <div style="display:flex;flex-wrap:wrap;gap:8px;">
    @if($te)<a href="{{ $te['teklif_linki'] }}" target="_blank" rel="noopener" class="ak-dg" style="border-color:#e9d5ff;color:#a21caf;">💛 Teklif sayfası</a>@endif
    @if($te)<a href="{{ $te['demo_linki'] }}" target="_blank" class="ak-dg" style="border-color:#86efac;color:#166534;">💬 Demo siteyi WhatsApp'tan gönder</a>@endif
    <a href="{{ $kr['sayfa'] }}" target="_blank" rel="noopener" class="ak-dg">👁 Kurumun sayfası</a>
    <a href="{{ route('admin.facilities.edit', $f) }}" target="_blank" class="ak-dg">📝 Kaydı aç</a>
    <a href="{{ route('admin.crm.index', ['ara' => $f->name]) }}#k{{ $f->id }}" target="_blank" class="ak-dg" style="border-color:#86efac;color:#166534;">📄 Site sözleşmesi (CRM'de)</a>
  </div>
  <div class="ak-satir" style="margin-top:10px;">
    @if($ge['teklif_acildi'])💛 Teklif sayfasını daha önce açtı: <b>{{ $ge['teklif_acildi'] }}</b><br>@endif
    @if($ge['sozlesme'])📄 Daha önce site sözleşmesi gönderildi: <b>{{ \Illuminate\Support\Carbon::parse($ge['sozlesme'])->format('d.m.Y') }}</b><br>@endif
    @if($ge['panel_girisi'])🔑 Kurum paneline en son giriş: <b>{{ $ge['panel_girisi'] }}</b><br>@endif
    @if($ge['gorusme'])☎️ Önceki görüşme kaydı: <b>{{ $ge['gorusme'] }}</b> · @endif
    @if($kr['sistemde'])Sistemde {{ $kr['sistemde'] }} tarihinden beri @endif
    @if($kr['bakiye'] > 0) · Bakiye: {{ $s($kr['bakiye']) }} ₺@endif
  </div>
</div>

{{-- 11. Görüşme notu ve takip (24 Kasım 2026: kullanıcının talebi; CRM'deki görüşme kaydıyla aynı tablo) --}}
<div class="ak-kart" id="k{{ $f->id }}">
  <h2 class="ak-h">11 · Görüşme notu ve takip</h2>
  <form method="POST" action="{{ route('admin.crm.store-note', $f) }}" style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
    @csrf
    <label style="min-width:0;"><span class="ak-etk">Sonuç</span>
      <select name="status" required class="ak-girdi">@foreach(\App\Http\Controllers\Marketer\CrmController::STATUSES as $sv => $sl)<option value="{{ $sv }}" @selected(($ge['durum'] ?: 'called') === $sv)>{{ $sl }}</option>@endforeach</select>
    </label>
    <label style="min-width:0;"><span class="ak-etk">Görüşülen kişi</span><input name="contact_name" maxlength="120" class="ak-girdi" value="{{ $k['sahip']['profil']['ad'] ?? '' }}"></label>
    <label style="min-width:0;"><span class="ak-etk">Görevi</span>
      <select name="contact_role" class="ak-girdi"><option value="">—</option><option value="sahibi">Sahibi</option><option value="mudur">Müdür</option><option value="calisan">Çalışan</option></select>
    </label>
    <label style="min-width:0;"><span class="ak-etk">Tekrar aranacak zaman</span><input type="datetime-local" name="callback_at" class="ak-girdi"></label>
    <label style="grid-column:1/-1;"><span class="ak-etk">Not</span><textarea name="note" rows="3" maxlength="2000" class="ak-girdi" placeholder="Ne konuşuldu, ne dedi, neye ilgi gösterdi…"></textarea></label>
    <div style="grid-column:1/-1;"><button type="submit" class="ak-btn">💾 Görüşmeyi kaydet</button></div>
  </form>
  @if($k['notlar'])
    <div style="font-size:13px;font-weight:800;color:#374151;margin-top:14px;">Görüşme geçmişi</div>
    @foreach($k['notlar'] as $n)
      <div class="ak-aile"><b>{{ $n['durum'] }}</b> · {{ $n['tarih'] }} · {{ $n['kim'] }}@if($n['kisi']) · 👤 {{ $n['kisi'] }}@endif @if($n['tekrar']) · 📅 {{ $n['tekrar'] }}@endif
        @if($n['not'])<div style="color:#111827;white-space:pre-line;">“{{ $n['not'] }}”</div>@endif
      </div>
    @endforeach
  @endif
</div>
@endsection
