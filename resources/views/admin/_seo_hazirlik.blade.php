{{-- 25 Ekim 2026: kullanıcının talebi - kurum / doktor sitesinin arama motoru hazırlığı.
     Eksikler kırmızı görünür; Google'da nasıl görüneceğinin önizlemesi.
     $tur: 'kurum' | 'doktor', $site: FacilityWebsite | ExpertWebsite. --}}
@php
  $kelime = fn ($m) => ($m = trim(preg_replace('/\s+/u', ' ', (string) $m))) === '' ? 0 : count(explode(' ', $m));
  if ($tur === 'kurum') {
      $k = $site->facility->loadMissing(['city', 'category', 'images']);
      [$galeri, $temsili] = \App\Models\FacilityWebsite::siteGallery($k);
      $gercekFoto = $temsili ? 0 : $galeri->count();
      $metinKelime = $kelime($site->aboutText());
      $maddeler = [
          ['İlçe girilmiş', (bool) $k->district, $k->district ?: 'Kurum kaydında ilçe yok — "ilçe + kurum türü" aramalarında çıkmak için şart.'],
          ['Kurum türü (kategori)', (bool) $k->category, $k->category->name ?? 'Kategori yok.'],
          ['Açık adres', (bool) $k->address, $k->address ?: 'Adres yok.'],
          ['Haritada konum', (bool) ($k->lat && $k->lng), $k->lat ? 'Var' : 'Konum (enlem/boylam) yok.'],
          ['Telefon', (bool) $k->phone, $k->phone ?: 'Telefon yok.'],
          ['Özgün tanıtım metni (en az 150 kelime)', $metinKelime >= 150, $metinKelime.' kelime'],
          ['Kurumun kendi fotoğrafları (en az 3)', $gercekFoto >= 3, $temsili ? 'Yalnızca temsili görseller var' : $gercekFoto.' fotoğraf'],
      ];
  } else {
      $k = $site->expert->loadMissing('city');
      $metinKelime = $kelime($site->aboutText());
      $maddeler = [
          ['İlçe girilmiş', (bool) $k->district, $k->district ?: 'Doktor kaydında ilçe yok — "ilçe + branş" aramalarında çıkmak için şart.'],
          ['Branş', (bool) ($k->branch || $k->branch_label), trim(($k->branch ?? '').' '.($k->branch_label ? '('.$k->branch_label.')' : '')) ?: 'Branş yok.'],
          ['Açık adres', (bool) $k->address, $k->address ?: 'Adres yok.'],
          ['Haritada konum', (bool) ($k->lat && $k->lng), $k->lat ? 'Var' : 'Konum (enlem/boylam) yok.'],
          ['Telefon', (bool) $k->phone, $k->phone ?: 'Telefon yok.'],
          ['Özgün "Hakkımda" metni (en az 100 kelime)', $metinKelime >= 100, $metinKelime.' kelime'],
          ['Fotoğraf', (bool) $site->photo_path, $site->photo_path ? 'Var' : 'Doktor fotoğrafı yok.'],
          ['Uzmanlık alanları (en az 3)', count($site->ilgiAlanlariListesi()) >= 3, count($site->ilgiAlanlariListesi()).' alan'],
          ['Çalışma saatleri', trim((string) $site->calisma_saatleri) !== '', trim((string) $site->calisma_saatleri) !== '' ? 'Var' : 'Yok'],
      ];
  }
  $tamam = collect($maddeler)->where(1, true)->count();
  $toplam = count($maddeler);
  $adres = $site->publicUrl();
@endphp
<div style="background:#fff;border-radius:16px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:18px;border-left:5px solid {{ $tamam === $toplam ? '#16a34a' : '#f59e0b' }};">
  <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center;">
    <h2 style="font-size:17px;font-weight:900;color:#111827;">🔎 Google hazırlığı <span style="color:{{ $tamam === $toplam ? '#16a34a' : '#b45309' }};">{{ $tamam }}/{{ $toplam }}</span></h2>
    <span style="font-size:12.5px;color:#6b7280;">Eksikler tamamlanınca "ilçe + {{ $tur === 'kurum' ? 'kurum türü' : 'branş' }}" aramalarında çıkma şansı artar.</span>
  </div>
  <div style="margin-top:12px;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;background:#fafafa;max-width:720px;">
    <div style="font-size:12px;color:#6b7280;">Google'da şöyle görünür:</div>
    <div style="font-size:13px;color:#202124;margin-top:4px;overflow-wrap:anywhere;">{{ preg_replace('#^https?://#', '', $adres) }}</div>
    <div style="font-size:18px;color:#1a0dab;line-height:1.3;margin-top:2px;overflow-wrap:anywhere;">{{ \Illuminate\Support\Str::limit($site->seoBaslik(), 70) }}</div>
    <div style="font-size:13.5px;color:#4d5156;margin-top:3px;line-height:1.45;">{{ $site->seoAciklama() }}</div>
  </div>
  <ul style="list-style:none;margin:12px 0 0;padding:0;display:grid;gap:6px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
    @foreach($maddeler as [$ad, $ok, $not])
      <li style="font-size:13.5px;border-radius:10px;padding:8px 10px;{{ $ok ? 'background:#f0fdf4;color:#166534;' : 'background:#fef2f2;color:#991b1b;' }}"><b>{{ $ok ? '✓' : '✗' }} {{ $ad }}</b><br><span style="opacity:.85;overflow-wrap:anywhere;">{{ $not }}</span></li>
    @endforeach
  </ul>
  <details style="margin-top:12px;font-size:13.5px;color:#374151;">
    <summary style="cursor:pointer;font-weight:800;">Yayına alırken yapılacaklar (her site için bir kez)</summary>
    <ol style="margin:8px 0 0 18px;display:grid;gap:4px;">
      <li>Alan adı varsa Google Search Console'a ekleyin ve <code>{{ rtrim($adres, '/') }}/sitemap.xml</code> adresini gönderin{{ $site->custom_domain ? '' : ' (alan adı yoksa site zaten bakimevleri.com haritasında)' }}.</li>
      <li>Search Console'da "URL denetimi" ile ana sayfa için "Dizine eklenmesini iste"ye basın.</li>
      <li>Google İşletme Profili'nde (Haritalar kaydı) "Web sitesi" alanına <b>{{ $adres }}</b> yazın.</li>
      <li>Adres ve telefonun İşletme Profili ile birebir aynı olduğunu kontrol edin.</li>
    </ol>
  </details>
</div>
