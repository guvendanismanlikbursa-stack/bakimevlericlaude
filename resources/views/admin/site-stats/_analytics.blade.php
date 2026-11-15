{{-- 15 Kasım 2026: kullanıcının kararı - "bizim sayaçlarımıza güvenmiyorum", ziyaret rakamları Google Analytics'ten,
     4 bölüm ayrı ayrı, grafikli, tüm istatistikler tek sayfada. Veri her sabah çekilir (bkz. google:veri-yenile).
     Not: Tailwind FTP yüklemesinde yeniden derlenmiyor; yeni görünüm kuralları satır içi stil ile yazıldı. --}}
@php
  $renk = ['bakimevleri.com' => '#0b5d8c', 'bakimevibul.com' => '#d97706', 'bakimeviara.com' => '#1e6f5c'];
  $sayi = fn ($n) => number_format((float) $n, 0, ',', '.');
  $yuzde = fn ($n) => '%'.number_format((float) $n * 100, 1, ',', '.');
  $sure = function ($sn) { $sn = (int) round($sn); return $sn >= 60 ? intdiv($sn, 60).' dk '.($sn % 60).' sn' : $sn.' sn'; };
  $degisim = function ($simdi, $once) {
      if (! $once) { return ''; }
      $d = ($simdi - $once) / $once * 100;
      $r = $d >= 0 ? '#15803d' : '#b91c1c';
      return '<span style="font-size:12px;font-weight:800;color:'.$r.';">'.($d >= 0 ? '▲ ' : '▼ ').number_format(abs($d), 0, ',', '.').'%</span>';
  };
  $kanalAd = ['Direct' => 'Doğrudan giriş', 'Organic Search' => 'Google / arama motoru', 'Unassigned' => 'Belirlenemeyen', 'Referral' => 'Başka siteden bağlantı',
      'AI Assistant' => 'Yapay zekâ (ChatGPT vb.)', 'Organic Social' => 'Sosyal medya', 'Paid Search' => 'Ücretli arama reklamı', 'Paid Social' => 'Ücretli sosyal medya',
      'Email' => 'E-posta', 'Organic Video' => 'Video (YouTube vb.)', 'Display' => 'Görüntülü reklam', 'Cross-network' => 'Google reklam ağı', 'Organic Shopping' => 'Alışveriş'];
  $olayAd = ['page_view' => 'Sayfa görüntüleme', 'session_start' => 'Ziyaret başlangıcı', 'first_visit' => 'İlk kez gelen', 'scroll' => 'Sayfanın sonuna kadar kaydırma',
      'scroll_depth' => 'Sayfa kaydırma (derinlik)', 'time_on_page' => 'Sayfada kalma ölçümü', 'user_engagement' => 'Sayfayla etkileşim', 'internal_link_click' => 'Site içi bağlantıya tıklama',
      'outbound_link_click' => 'Dış bağlantıya tıklama (telefon/WhatsApp/site)', 'click' => 'Dış bağlantı tıklaması', 'form_start' => 'Form doldurmaya başladı',
      'form_submit_attempt' => 'Formu göndermeyi denedi', 'form_submit' => 'Form gönderildi', 'file_download' => 'Dosya indirme', 'view_search_results' => 'Site içi arama',
      'video_start' => 'Video başlatıldı', 'video_progress' => 'Video izleniyor', 'video_complete' => 'Video tamamlandı'];
  $kart = 'background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:18px 20px;margin-bottom:22px;box-shadow:0 1px 2px rgba(0,0,0,.04);';
  $h2 = 'font-size:17px;font-weight:800;color:#111827;margin:0 0 4px;';
  $acik = 'font-size:12.5px;color:#6b7280;margin:0 0 14px;line-height:1.55;';
  $th = 'padding:8px 10px;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;white-space:nowrap;text-align:right;';
  $td = 'padding:8px 10px;font-size:13.5px;border-bottom:1px solid #f1f5f9;text-align:right;white-space:nowrap;';
  $sec = fn ($aktif) => 'display:inline-block;padding:7px 12px;font-size:13px;font-weight:800;text-decoration:none;border-right:1px solid #e5e7eb;'.($aktif ? 'background:#0b5d8c;color:#fff;' : 'color:#374151;background:#fff;');
  $q = fn (array $ek) => request()->fullUrlWithQuery(array_merge(['yenile' => null], $ek));
@endphp

<div id="analytics" style="{{ $kart }}">
  <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
    <div>
      <h2 style="{{ $h2 }}font-size:19px;">📊 Ziyaretçi İstatistikleri <span style="font-size:12px;font-weight:700;color:#6b7280;">(Google Analytics)</span></h2>
      @if($gaHam)
        <p style="{{ $acik }}margin:0;">{{ \Illuminate\Support\Carbon::parse($gaHam['bas'])->format('d.m.Y') }} – {{ \Illuminate\Support\Carbon::parse($gaHam['son'])->format('d.m.Y') }} arası (bugün dahil değil).
          Veri {{ \Illuminate\Support\Carbon::parse($gaHam['tarih'])->format('d.m.Y H:i') }} tarihinde çekildi, her sabah yenilenir.
          <a href="{{ $q(['yenile' => 1]) }}#analytics" style="color:#0b5d8c;font-weight:700;" onclick="this.textContent='Çekiliyor, 30-40 sn sürebilir…';">Şimdi yenile</a></p>
      @endif
    </div>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
    <div style="display:inline-flex;flex-wrap:wrap;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
      <a href="{{ $q(['site' => null]) }}#analytics" style="{{ $sec($gaSite === 'hepsi') }}">3 site toplam</a>
      @foreach(\App\Services\GoogleVeriServisi::SITELER as $s)
        <a href="{{ $q(['site' => $s]) }}#analytics" style="{{ $sec($gaSite === $s) }}">{{ $s }}</a>
      @endforeach
    </div>
    <div style="display:inline-flex;flex-wrap:wrap;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
      @foreach($gaDonemler as $g => $ad)
        <a href="{{ $q(['gun' => $g]) }}#analytics" style="{{ $sec($gaGun === $g) }}">{{ $ad }}</a>
      @endforeach
    </div>
  </div>
</div>

@if(! $gaHazir)
  <div style="{{ $kart }}background:#fffbeb;border-color:#fde68a;color:#92400e;font-size:14px;">Google Analytics bağlantısı ayarlı değil (GA4_PROPERTY_ID ya da hizmet hesabı dosyası eksik).</div>
@elseif($gaHata)
  <div style="{{ $kart }}background:#fef2f2;border-color:#fecaca;color:#991b1b;font-size:14px;">Google'dan veri alınamadı: {{ \Illuminate\Support\Str::limit($gaHata, 300) }}</div>
@elseif($ga)
  @php
    $t = $ga['toplam']['simdi'] ?? []; $o = $ga['toplam']['once'] ?? [];
    $etkOran = fn ($x) => ($x['sessions'] ?? 0) > 0 ? ($x['engagedSessions'] ?? 0) / $x['sessions'] : 0;
    $ortSure = fn ($x) => ($x['activeUsers'] ?? 0) > 0 ? ($x['userEngagementDuration'] ?? 0) / $x['activeUsers'] : 0;
  @endphp

  {{-- Özet kartları --}}
  <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:22px;">
    @foreach([
      ['Etkileşimli ziyaret', $t['engagedSessions'] ?? 0, $o['engagedSessions'] ?? 0, '10 sn+ kalan, 2+ sayfa gezen ya da işlem yapan ziyaretler — en güvenilir rakam', '#15803d'],
      ['Ziyaretçi', $t['activeUsers'] ?? 0, $o['activeUsers'] ?? 0, 'Farklı kişi (tarayıcı) sayısı', '#0b5d8c'],
      ['Yeni ziyaretçi', $t['newUsers'] ?? 0, $o['newUsers'] ?? 0, 'Siteye ilk kez gelenler', '#0b5d8c'],
      ['Ziyaret (oturum)', $t['sessions'] ?? 0, $o['sessions'] ?? 0, 'Toplam ziyaret sayısı', '#374151'],
      ['Sayfa görüntüleme', $t['screenPageViews'] ?? 0, $o['screenPageViews'] ?? 0, 'Açılan toplam sayfa', '#374151'],
    ] as [$ad, $simdi, $once, $acikla, $rk])
      <div style="background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:14px 16px;">
        <div style="font-size:12.5px;font-weight:800;color:#6b7280;">{{ $ad }}</div>
        <div style="font-size:26px;font-weight:900;color:{{ $rk }};line-height:1.2;margin-top:4px;">{{ $sayi($simdi) }}</div>
        <div>{!! $degisim($simdi, $once) !!} <span style="font-size:11.5px;color:#9ca3af;">önceki dönem: {{ $sayi($once) }}</span></div>
        <div style="font-size:11.5px;color:#9ca3af;margin-top:4px;line-height:1.4;">{{ $acikla }}</div>
      </div>
    @endforeach
    <div style="background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:14px 16px;">
      <div style="font-size:12.5px;font-weight:800;color:#6b7280;">İlgi oranı / ort. süre</div>
      <div style="font-size:26px;font-weight:900;color:#7c3aed;line-height:1.2;margin-top:4px;">{{ $yuzde($etkOran($t)) }}</div>
      <div style="font-size:12px;color:#374151;font-weight:700;">kişi başı {{ $sure($ortSure($t)) }}</div>
      <div style="font-size:11.5px;color:#9ca3af;margin-top:4px;">önceki: {{ $yuzde($etkOran($o)) }} · {{ $sure($ortSure($o)) }}</div>
    </div>
  </div>

  {{-- Site karşılaştırma --}}
  @if($gaSite === 'hepsi')
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">Sitelere Göre</h2>
      <p style="{{ $acik }}">İlgi oranı %15'in altındaysa ziyaretlerin çoğu birkaç saniyede çıkıyor demektir; bu genellikle bot ya da yanlış yönlendirilmiş trafiktir.</p>
      <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;min-width:720px;">
        <thead><tr>@foreach(['Site', 'Etkileşimli ziyaret', 'Ziyaretçi', 'Değişim', 'Ziyaret', 'Sayfa', 'İlgi oranı', 'Ort. süre'] as $b)<th style="{{ $th }}{{ $loop->first ? 'text-align:left;' : '' }}">{{ $b }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach(\App\Services\GoogleVeriServisi::SITELER as $s)
            @php $x = $ga['ozet'][$s]['simdi'] ?? []; $y = $ga['ozet'][$s]['once'] ?? []; @endphp
            <tr>
              <td style="{{ $td }}text-align:left;font-weight:800;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $renk[$s] }};margin-right:6px;"></span>{{ $s }}
                @if(($x['sessions'] ?? 0) > 100 && $etkOran($x) < 0.15)<span style="margin-left:6px;font-size:11px;font-weight:800;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:1px 6px;">⚠ bot şüphesi</span>@endif
              </td>
              <td style="{{ $td }}font-weight:900;color:#15803d;">{{ $sayi($x['engagedSessions'] ?? 0) }}</td>
              <td style="{{ $td }}">{{ $sayi($x['activeUsers'] ?? 0) }}</td>
              <td style="{{ $td }}">{!! $degisim($x['activeUsers'] ?? 0, $y['activeUsers'] ?? 0) !!}</td>
              <td style="{{ $td }}">{{ $sayi($x['sessions'] ?? 0) }}</td>
              <td style="{{ $td }}">{{ $sayi($x['screenPageViews'] ?? 0) }}</td>
              <td style="{{ $td }}font-weight:800;color:{{ $etkOran($x) < 0.15 ? '#b91c1c' : '#374151' }};">{{ $yuzde($etkOran($x)) }}</td>
              <td style="{{ $td }}">{{ $sure($ortSure($x)) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  @endif

  {{-- Günlük grafik --}}
  <div style="{{ $kart }}">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
      <h2 style="{{ $h2 }}">Günlük Ziyaretçi</h2>
      <div style="display:inline-flex;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;" id="gunluk-secim">
        <button type="button" data-m="etk" style="{{ $sec(true) }}border:0;cursor:pointer;">Etkileşimli ziyaret</button>
        <button type="button" data-m="kisi" style="{{ $sec(false) }}border:0;cursor:pointer;">Ziyaretçi</button>
      </div>
    </div>
    <div style="height:280px;margin-top:12px;"><canvas id="ga-gunluk"></canvas></div>
  </div>

  {{-- 4 bölüm --}}
  @php $bolTop = max(1, collect($ga['bolumler'])->sum('goruntulenme')); @endphp
  <div style="{{ $kart }}">
    <h2 style="{{ $h2 }}">Bölümlere Giriş</h2>
    <p style="{{ $acik }}">Kurum sayfaları (kurum sitesi dahil) kurumun bölümüne, doktor sayfaları Doktorlar bölümüne sayılır. <b>Kişi</b>: her sayfanın farklı ziyaretçi sayılarının toplamı (aynı kişi iki kurumu açtıysa iki kez sayılır).</p>
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:14px;">
      @foreach($ga['bolumler'] as $b)
        <div style="border:1.5px solid #e5e7eb;border-radius:14px;padding:12px 14px;">
          <div style="font-weight:800;color:#111827;">{{ $b['baslik'] === 'Doktorlar' ? '🩺 ' : '' }}{{ $b['baslik'] }}</div>
          <div style="font-size:24px;font-weight:900;color:#0b5d8c;line-height:1.2;margin-top:6px;">{{ $sayi($b['goruntulenme']) }}</div>
          <div style="font-size:12px;font-weight:700;color:#6b7280;">sayfa görüntüleme · %{{ number_format($b['goruntulenme'] / $bolTop * 100, 1, ',', '.') }}</div>
          <div style="font-size:16px;font-weight:800;color:#15803d;margin-top:6px;">{{ $sayi($b['kisi']) }} <span style="font-size:12px;color:#6b7280;">kişi</span></div>
        </div>
      @endforeach
    </div>
    <div style="height:240px;"><canvas id="ga-bolum"></canvas></div>
  </div>

  {{-- Kurum türü + en çok bakılan kurumlar (alt alta, tam genişlik) --}}
  <div>
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">Kurum Türüne Göre İlgi</h2>
      <p style="{{ $acik }}">Kurum sayfası görüntülenmesinin türlere dağılımı.</p>
      <div style="height:{{ max(200, count($ga['turler']) * 34) }}px;"><canvas id="ga-tur"></canvas></div>
    </div>
    <div style="{{ $kart }}" id="en-cok-bakilan">
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;">
        <h2 style="{{ $h2 }}">En Çok Bakılan Kurumlar</h2>
        <form method="GET" action="{{ url()->current() }}#en-cok-bakilan" style="display:flex;gap:6px;flex-wrap:wrap;">
          <input type="hidden" name="gun" value="{{ $gaGun }}">@if($gaSite !== 'hepsi')<input type="hidden" name="site" value="{{ $gaSite }}">@endif
          <select name="il" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:8px;padding:4px 6px;font-size:12.5px;">
            <option value="">Türkiye geneli</option>
            @foreach($gaIller as $il)<option value="{{ $il->id }}" @selected($gaIl === $il->id)>{{ $il->name }}</option>@endforeach
          </select>
          <select name="bolum" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:8px;padding:4px 6px;font-size:12.5px;">
            <option value="">Tüm bölümler</option>
            @foreach(service_sections() as $s)<option value="{{ $s['slug'] }}" @selected($gaBolum === $s['slug'])>{{ $s['title'] }}</option>@endforeach
          </select>
        </form>
      </div>
      @if(empty($ga['kurumlar']))
        <p style="font-size:13.5px;color:#9ca3af;margin-top:10px;">Bu filtreyle görüntülenen kurum yok.</p>
      @else
        <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;margin-top:8px;min-width:520px;">
          <thead><tr><th style="{{ $th }}text-align:left;">Kurum</th><th style="{{ $th }}">Görüntüleme</th><th style="{{ $th }}">Kişi</th></tr></thead>
          <tbody>
            @foreach($ga['kurumlar'] as $i => $f)
              <tr>
                <td style="{{ $td }}text-align:left;white-space:normal;"><span style="color:#9ca3af;font-size:11.5px;">{{ $i + 1 }}.</span>
                  <a href="{{ route('admin.facilities.edit', $f['id']) }}" style="color:#111827;font-weight:700;text-decoration:none;">{{ $f['ad'] }}</a>
                  <span style="font-size:11.5px;color:#9ca3af;">{{ $f['il'] }} · {{ $f['tur'] }}</span></td>
                <td style="{{ $td }}font-weight:800;">{{ $sayi($f['goruntulenme']) }}</td>
                <td style="{{ $td }}">{{ $sayi($f['kisi']) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table></div>
      @endif
    </div>
  </div>

  {{-- Trafik kaynakları --}}
  <div style="{{ $kart }}">
    <h2 style="{{ $h2 }}">Ziyaretçiler Nereden Geliyor?</h2>
    <p style="{{ $acik }}"><b>Doğrudan giriş</b> yüksek ama ilgi oranı çok düşükse bu trafik büyük olasılıkla bottur. Google'dan gelenler en değerli ziyaretçilerdir.</p>
    <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));align-items:start;">
      <div style="height:260px;"><canvas id="ga-kanal"></canvas></div>
      <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;">
        <thead><tr><th style="{{ $th }}text-align:left;">Kaynak</th><th style="{{ $th }}">Ziyaret</th><th style="{{ $th }}">Etkileşimli</th><th style="{{ $th }}">İlgi oranı</th></tr></thead>
        <tbody>
          @foreach($ga['kanallar'] as $ad => [$oturum, $etk, $kisi])
            <tr><td style="{{ $td }}text-align:left;font-weight:700;">{{ $kanalAd[$ad] ?? $ad }}</td><td style="{{ $td }}">{{ $sayi($oturum) }}</td>
              <td style="{{ $td }}font-weight:800;color:#15803d;">{{ $sayi($etk) }}</td>
              <td style="{{ $td }}font-weight:700;color:{{ $oturum > 50 && $etk / max(1, $oturum) < 0.15 ? '#b91c1c' : '#374151' }};">{{ $yuzde($etk / max(1, $oturum)) }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
    <details style="margin-top:12px;">
      <summary style="cursor:pointer;font-size:13px;font-weight:800;color:#0b5d8c;">Ayrıntılı kaynak listesi (site / araç)</summary>
      <table style="width:100%;border-collapse:collapse;margin-top:8px;">
        <thead><tr><th style="{{ $th }}text-align:left;">Kaynak / araç</th><th style="{{ $th }}">Ziyaret</th><th style="{{ $th }}">Etkileşimli</th><th style="{{ $th }}">Ziyaretçi</th></tr></thead>
        <tbody>@foreach($ga['kaynaklar'] as $ad => [$oturum, $etk, $kisi])<tr><td style="{{ $td }}text-align:left;">{{ $ad }}</td><td style="{{ $td }}">{{ $sayi($oturum) }}</td><td style="{{ $td }}">{{ $sayi($etk) }}</td><td style="{{ $td }}">{{ $sayi($kisi) }}</td></tr>@endforeach</tbody>
      </table>
    </details>
  </div>

  {{-- Şehir + cihaz --}}
  <div style="display:grid;gap:22px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">Ziyaretçilerin Şehirleri</h2>
      <p style="{{ $acik }}">Ziyaretçinin bağlandığı şehir (internet sağlayıcısına göre, yaklaşık).</p>
      <div style="height:{{ max(220, count($ga['sehirler']) * 26) }}px;"><canvas id="ga-sehir"></canvas></div>
    </div>
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">Cihaz Türü</h2>
      <p style="{{ $acik }}">Ziyaretçilerin telefon / bilgisayar / tablet dağılımı.</p>
      <div style="height:240px;"><canvas id="ga-cihaz"></canvas></div>
    </div>
  </div>

  {{-- Olaylar --}}
  <div style="{{ $kart }}">
    <h2 style="{{ $h2 }}">Ziyaretçilerin Yaptığı İşlemler</h2>
    <p style="{{ $acik }}">Sitede ölçülen işlemler. Form ve dış bağlantı (telefon / WhatsApp) tıklamaları talebe en yakın göstergelerdir.</p>
    <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;">
      <thead><tr><th style="{{ $th }}text-align:left;">İşlem</th><th style="{{ $th }}">Sayı</th><th style="{{ $th }}">Kişi</th></tr></thead>
      <tbody>
        @foreach($ga['olaylar'] as $ad => [$adet, $kisi])
          @php $onemli = in_array($ad, ['form_submit', 'form_submit_attempt', 'form_start', 'outbound_link_click', 'click'], true); @endphp
          <tr style="{{ $onemli ? 'background:#f0fdf4;' : '' }}"><td style="{{ $td }}text-align:left;font-weight:{{ $onemli ? '800' : '600' }};">{{ $olayAd[$ad] ?? $ad }} <span style="font-size:11px;color:#9ca3af;">{{ $ad }}</span></td>
            <td style="{{ $td }}font-weight:800;">{{ $sayi($adet) }}</td><td style="{{ $td }}">{{ $sayi($kisi) }}</td></tr>
        @endforeach
      </tbody>
    </table></div>
  </div>

  {{-- En çok görüntülenen sayfalar --}}
  <details style="{{ $kart }}">
    <summary style="cursor:pointer;{{ $h2 }}">En Çok Görüntülenen Sayfalar (ilk 25)</summary>
    <table style="width:100%;border-collapse:collapse;margin-top:10px;">
      <thead><tr><th style="{{ $th }}text-align:left;">Sayfa</th><th style="{{ $th }}">Görüntüleme</th><th style="{{ $th }}">Kişi</th></tr></thead>
      <tbody>@foreach($ga['enCokSayfa'] as $yol => [$gor, $kisi])<tr><td style="{{ $td }}text-align:left;white-space:normal;word-break:break-all;">{{ $yol }}</td><td style="{{ $td }}font-weight:800;">{{ $sayi($gor) }}</td><td style="{{ $td }}">{{ $sayi($kisi) }}</td></tr>@endforeach</tbody>
    </table>
  </details>

  {{-- Search Console --}}
  <div style="{{ $kart }}" id="google-arama">
    <h2 style="{{ $h2 }}">🔍 Google Aramasında Görünme <span style="font-size:12px;font-weight:700;color:#6b7280;">(Search Console)</span></h2>
    <p style="{{ $acik }}">{{ \Illuminate\Support\Carbon::parse($gaHam['sc_bas'])->format('d.m.Y') }} – {{ \Illuminate\Support\Carbon::parse($gaHam['sc_son'])->format('d.m.Y') }} (Google bu veriyi 2-3 gün geriden verir).
      <b>Gösterim</b>: aramalarda sitemizin çıkma sayısı, <b>Tıklama</b>: aramadan siteye gelen, <b>Sıra</b>: ortalama arama sırası (1 = en üst).</p>
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:14px;">
      @foreach($ga['sc'] as $s => $v)
        <div style="border:1.5px solid #e5e7eb;border-radius:14px;padding:12px 14px;">
          <div style="font-weight:800;color:{{ $renk[$s] }};">{{ $s }}</div>
          @if($v)
            <div style="font-size:22px;font-weight:900;color:#111827;margin-top:4px;">{{ $sayi($v['clicks']) }} <span style="font-size:12px;font-weight:700;color:#6b7280;">tıklama</span></div>
            <div style="font-size:12.5px;color:#374151;">{{ $sayi($v['impressions']) }} gösterim · tıklama oranı %{{ number_format($v['ctr'] * 100, 1, ',', '.') }} · ort. sıra {{ number_format($v['position'], 1, ',', '.') }}</div>
          @else
            <div style="font-size:13px;color:#9ca3af;margin-top:4px;">Veri yok</div>
          @endif
        </div>
      @endforeach
    </div>
    <div style="height:240px;"><canvas id="ga-sc"></canvas></div>
    <details style="margin-top:12px;" open>
      <summary style="cursor:pointer;font-size:13px;font-weight:800;color:#0b5d8c;">En çok tıklanan aramalar</summary>
      <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;margin-top:8px;">
        <thead><tr><th style="{{ $th }}text-align:left;">Aranan kelime</th><th style="{{ $th }}">Tıklama</th><th style="{{ $th }}">Gösterim</th><th style="{{ $th }}">Sıra</th></tr></thead>
        <tbody>@foreach($ga['sorgular'] as $kelime => $v)<tr><td style="{{ $td }}text-align:left;white-space:normal;">{{ $kelime }}</td><td style="{{ $td }}font-weight:800;">{{ $sayi($v['tiklama']) }}</td><td style="{{ $td }}">{{ $sayi($v['gosterim']) }}</td><td style="{{ $td }}">{{ number_format($v['sira'], 1, ',', '.') }}</td></tr>@endforeach</tbody>
      </table></div>
    </details>
  </div>

  @php
    $gunler = array_keys($ga['gunluk']);
    $siteListe = $gaSite === 'hepsi' ? \App\Services\GoogleVeriServisi::SITELER : [$gaSite];
    $seri = [];
    foreach ($siteListe as $s) {
        $seri[] = ['ad' => $s, 'renk' => $renk[$s], 'kisi' => array_map(fn ($g) => $ga['gunluk'][$g][$s]['kisi'] ?? 0, $gunler), 'etk' => array_map(fn ($g) => $ga['gunluk'][$g][$s]['etk'] ?? 0, $gunler)];
    }
    $grafik = [
      'gunler' => array_map(fn ($g) => \Illuminate\Support\Carbon::parse($g)->format('d.m'), $gunler), 'seri' => $seri,
      'bolum' => ['ad' => array_column($ga['bolumler'], 'baslik'), 'gor' => array_column($ga['bolumler'], 'goruntulenme'), 'kisi' => array_column($ga['bolumler'], 'kisi')],
      'tur' => ['ad' => array_keys($ga['turler']), 'deger' => array_values($ga['turler'])],
      'kanal' => ['ad' => array_map(fn ($k) => $kanalAd[$k] ?? $k, array_keys($ga['kanallar'])), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['kanallar']))],
      'sehir' => ['ad' => array_keys($ga['sehirler']), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['sehirler']))],
      'cihaz' => ['ad' => array_map(fn ($k) => ['mobile' => 'Telefon', 'desktop' => 'Bilgisayar', 'tablet' => 'Tablet', 'smart tv' => 'Akıllı TV'][$k] ?? $k, array_keys($ga['cihazlar'])), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['cihazlar']))],
      'sc' => ['gun' => array_map(fn ($g) => \Illuminate\Support\Carbon::parse($g)->format('d.m'), array_keys($ga['scGunluk'])), 'tik' => array_column($ga['scGunluk'], 'tiklama'), 'gos' => array_column($ga['scGunluk'], 'gosterim')],
    ];
  @endphp
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script>
  (function () {
    if (!window.Chart) return;
    var G = @json($grafik);
    var palet = ['#0b5d8c', '#1e6f5c', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#65a30d', '#b91c1c', '#6b7280', '#ca8a04', '#4f46e5', '#059669'];
    var bicim = function (v) { return Number(v).toLocaleString('tr-TR'); };
    Chart.defaults.font.family = 'inherit';

    var gunluk = new Chart(document.getElementById('ga-gunluk'), {
      type: 'line',
      data: { labels: G.gunler, datasets: G.seri.map(function (s) { return { label: s.ad, data: s.etk, borderColor: s.renk, backgroundColor: s.renk + '22', fill: G.seri.length === 1, tension: .3, pointRadius: G.gunler.length > 60 ? 0 : 2, borderWidth: 2 }; }) },
      options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true, ticks: { callback: bicim } } } }
    });
    document.querySelectorAll('#gunluk-secim button').forEach(function (b) {
      b.addEventListener('click', function () {
        document.querySelectorAll('#gunluk-secim button').forEach(function (x) { x.style.background = '#fff'; x.style.color = '#374151'; });
        b.style.background = '#0b5d8c'; b.style.color = '#fff';
        gunluk.data.datasets.forEach(function (d, i) { d.data = G.seri[i][b.dataset.m]; });
        gunluk.update();
      });
    });

    new Chart(document.getElementById('ga-bolum'), {
      type: 'bar',
      data: { labels: G.bolum.ad, datasets: [
        { label: 'Sayfa görüntüleme', data: G.bolum.gor, backgroundColor: '#0b5d8c', borderRadius: 6 },
        { label: 'Kişi', data: G.bolum.kisi, backgroundColor: '#1e6f5c', borderRadius: 6 }] },
      options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: bicim } } } }
    });

    var yatay = function (id, d, renk) {
      new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels: d.ad, datasets: [{ label: id === 'ga-tur' ? 'Görüntüleme' : 'Ziyaretçi', data: d.deger, backgroundColor: renk, borderRadius: 5 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: bicim } } } }
      });
    };
    yatay('ga-tur', G.tur, '#0b5d8c');
    yatay('ga-sehir', G.sehir, '#1e6f5c');

    var pasta = function (id, d) {
      new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: { labels: d.ad, datasets: [{ data: d.deger, backgroundColor: palet, borderWidth: 1 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: function (c) {
          var top = c.dataset.data.reduce(function (a, b) { return a + b; }, 0);
          return ' ' + c.label + ': ' + bicim(c.parsed) + ' (%' + (top ? (c.parsed / top * 100).toFixed(1).replace('.', ',') : 0) + ')';
        } } } } }
      });
    };
    pasta('ga-kanal', G.kanal);
    pasta('ga-cihaz', G.cihaz);

    new Chart(document.getElementById('ga-sc'), {
      type: 'line',
      data: { labels: G.sc.gun, datasets: [
        { label: 'Tıklama', data: G.sc.tik, borderColor: '#0b5d8c', backgroundColor: '#0b5d8c22', fill: true, tension: .3, pointRadius: 0, borderWidth: 2, yAxisID: 'y' },
        { label: 'Gösterim', data: G.sc.gos, borderColor: '#7c3aed', tension: .3, pointRadius: 0, borderWidth: 2, yAxisID: 'y2' }] },
      options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Tıklama' } }, y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Gösterim' } } } }
    });
  })();
  </script>
@endif
