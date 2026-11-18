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

@if(! empty($son24))
  {{-- 16 Kasım 2026: kullanıcının talebi - günlük giriş sayıları (son 24 saat, saat saat), 3 site ayrı. Her saat yenilenir.
       18 Kasım 2026: kullanıcının talebi - Google'ın Search Console ekranıyla aynı rakam (aramadan tıklama) da gösterilir,
       iki kaynağın ne saydığı kısaca açıklanır ("farklı sayılar kafa karıştırıyor"). --}}
  @php
    $s24 = $son24['siteler'];
    $s24Bas = isset($son24['bas']) ? \Illuminate\Support\Carbon::createFromFormat('YmdH', $son24['bas']) : null;
    $s24Bitis = $s24Bas ? $s24Bas->copy()->addHours(24) : null;
    $scVar = ! empty($son24['sc_var']);
    // 18 Kasım 2026: kart sayıları iki kaynağın da yayınladığı son saate kadar (ortak pencere) - bkz. AnalitikPanelVerisi::cekSon24.
    $ortak = ! empty($son24['kanal_var']) && ! empty($son24['ortak_son']);
    $saatBicim = fn ($k) => $k ? substr($k, 8, 2).':00' : '—';
    $sonrakiSaat = fn ($k) => $k ? \Illuminate\Support\Carbon::createFromFormat('YmdH', $k)->addHour()->format('H:00') : '—';
    // Her grafik: [kimlik, başlık, açıklama, saat anahtarı, gelen son saat, kesinleşmemiş ilk saat]
    $grafikler = [];
    if ($scVar) {
        $grafikler[] = ['sc', '🔍 Google aramasından tıklama — saat saat', 'Kaynak: Search Console. Soluk sütunlar: Google hâlâ sayıyor, rakam artabilir.', 'tik_saat', (string) ($son24['sc_son'] ?? ''), (string) ($son24['sc_eksik'] ?? '')];
    }
    $grafikler[] = ['ga', '👤 Siteye giren kişi — saat saat', 'Kaynak: Google Analytics.', 'saat', (string) ($son24['son'] ?? ''), ''];
  @endphp
  <div id="son24" style="{{ $kart }}">
    <h2 style="{{ $h2 }}">🕒 Son 24 Saat</h2>
    <p style="{{ $acik }}">@if($s24Bas && $s24Bitis){{ $s24Bas->format('d.m H:00') }} – {{ $s24Bitis->format('d.m H:00') }} arası (Türkiye saati).@endif
      Son yenileme: <b>{{ \Illuminate\Support\Carbon::parse($son24['tarih'])->format('H:i') }}</b> (her saat başını 5 geçe yenilenir). Hong Kong/Çin bot trafiği sayılmaz.</p>
    @if($ortak && $s24Bas)
      <p style="font-size:12.5px;color:#374151;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:8px 12px;margin:0 0 12px;">Kartlardaki sayılar <b>{{ $s24Bas->format('d.m H:00') }} – {{ $sonrakiSaat($son24['ortak_son']) }}</b> arasını kapsar: Google'ın tıklamaları <b>kesinleştirdiği</b> son saate kadar. Böylece tıklama ve giriş <b>aynı saatler</b> için karşılaştırılır. Daha yeni saatler aşağıdaki grafiklerde.</p>
    @endif

    <details style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:10px 14px;margin-bottom:14px;">
      <summary style="cursor:pointer;font-size:13.5px;font-weight:800;color:#0b5d8c;">Bu rakamlar nereden geliyor, neden farklı?</summary>
      <div style="font-size:13px;color:#374151;line-height:1.65;margin-top:8px;">
        @if($scVar)<div style="margin-bottom:6px;"><b>🔍 Google'dan tıklama</b> — kaynak <b>Google Search Console</b>. Google aramasında sitemize kaç kez tıklandığı. Search Console ekranında gördüğünüz rakamla <b>aynı kaynaktır</b>. SEO başarısını bu ölçer.</div>@endif
        <div style="margin-bottom:6px;"><b>👤 Siteye giriş</b> — kaynak <b>Google Analytics</b>. Siteye yapılan ziyaretlerin tamamı: Google'dan gelenler <b>ve</b> Bing/Yandex, doğrudan adres yazanlar, yapay zekâ asistanları, başka siteler. Bu yüzden Google tıklamasından <b>fazla</b> çıkabilir; altında kaynağına göre ayrılmış hali yazar. Çerezi reddedenler sayılmadığı için arama motorundan gelen giriş, Google tıklamasından <b>az</b> da çıkabilir.</div>
        <div style="margin-bottom:6px;">Karşılaştırma doğru olsun diye kartlardaki iki sayı da <b>aynı saatleri</b> kapsar: Google'ın tıklamaları kesinleştirdiği son saate kadar. Bu saatlerde Google tıklaması ile Google'dan gelen giriş birbirine çok yakındır; giriş biraz az çıkarsa sebep çerezi reddeden ziyaretçilerdir.</div>
        @if($scVar)<div style="margin-bottom:6px;"><b>👁 Google'da görünme</b> — sitemizin arama sonuçlarında <b>listelenme</b> sayısı. Ziyaretçi değildir; arananlarda ne kadar göründüğümüzü gösterir.</div>@endif
        <div style="color:#6b7280;">Google iki kaynağı da 2–3 saat geriden gönderir. Henüz gelmeyen saatler grafikte gri kesikli kutu olarak görünür.</div>
      </div>
    </details>

    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));margin-bottom:18px;">
      @foreach($s24 as $s => $x)
        <div style="border:1px solid #eef0f3;border-left:4px solid {{ $renk[$s] ?? '#6b7280' }};border-radius:12px;padding:12px 14px;">
          <div style="font-size:13px;font-weight:800;color:#374151;">{{ $s }}</div>
          <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:6px;">
            @if($scVar)
              <div><div style="font-size:26px;font-weight:900;color:{{ $renk[$s] ?? '#111827' }};line-height:1.1;">{{ ($x['tik'] ?? null) === null ? '—' : $sayi($ortak ? ($x['o_tik'] ?? $x['tik']) : $x['tik']) }}</div><div style="font-size:12px;color:#6b7280;">🔍 Google'dan tıklama</div></div>
            @endif
            @if($ortak)
              <div><div style="font-size:26px;font-weight:900;color:#374151;line-height:1.1;">{{ $sayi($x['o_giris']) }}</div><div style="font-size:12px;color:#6b7280;">👤 siteye giriş (tüm kaynaklar)</div></div>
            @else
              <div><div style="font-size:26px;font-weight:900;color:#374151;line-height:1.1;">{{ $sayi($x['kisi']) }}</div><div style="font-size:12px;color:#6b7280;">👤 siteye giren kişi</div></div>
            @endif
          </div>
          @if($ortak)
            <div style="font-size:12px;color:#4b5563;margin-top:4px;">Girişlerin kaynağı: <b>{{ $sayi($x['o_kanal']['arama']) }}</b> arama motoru (Google, Bing, Yandex) · <b>{{ $sayi($x['o_kanal']['dogrudan']) }}</b> doğrudan · <b>{{ $sayi($x['o_kanal']['diger']) }}</b> diğer</div>
          @endif
          <div style="font-size:12.5px;color:#4b5563;line-height:1.7;margin-top:6px;">
            @if($scVar && ($x['gos'] ?? null) !== null)👁 Google'da görünme: <b>{{ $sayi($x['gos']) }}</b><br>@endif
            Bakılan sayfa: <b>{{ $sayi($x['sayfa']) }}</b> · Ort. süre: <b>{{ $x['kisi'] ? $sure($x['sure'] / $x['kisi']) : '—' }}</b> · İlgi: <b>{{ $x['oturum'] ? $yuzde($x['etk'] / $x['oturum']) : '—' }}</b>
            @if($x['bot'])<br><span style="color:#b91c1c;">Ayıklanan bot (HK/Çin): {{ $sayi($x['bot']) }}</span>@endif
          </div>
        </div>
      @endforeach
    </div>

    @foreach($grafikler as [$gid, $gBaslik, $gAciklama, $gAnahtar, $gSon, $gEksik])
      @php
        $saatToplam = [];
        foreach ($s24 as $sv) { foreach ($sv[$gAnahtar] ?? [] as $k => $n) { $saatToplam[(string) $k] = ($saatToplam[(string) $k] ?? 0) + (int) $n; } }
        $saatEnCok = max(1, max($saatToplam ?: [0]));
        $birim = $gid === 'sc' ? 'tıklama' : 'kişi';
      @endphp
      {{-- 17 Kasım 2026: kullanıcının talebi - renkli bloklara gelince (ya da dokununca) o saatte hangi siteye kaç olduğu net gösterilir. --}}
      <div style="margin-top:{{ $loop->first ? 0 : 22 }}px;">
        <div style="font-size:13.5px;font-weight:800;color:#374151;">{{ $gBaslik }}</div>
        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">{{ $gAciklama }} @if($gSon)Son gelen saat: <b>{{ $saatBicim($gSon) }}–{{ $sonrakiSaat($gSon) }}</b>.@endif</div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;margin-bottom:8px;">
          @foreach($s24 as $s => $x)
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#4b5563;"><span style="width:10px;height:10px;border-radius:2px;background:{{ $renk[$s] ?? '#6b7280' }};"></span>{{ $s }}</span>
          @endforeach
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#4b5563;"><span style="width:10px;height:10px;border-radius:2px;border:1.5px dashed #94a3b8;background:#f1f5f9;"></span>henüz gelmedi</span>
          <span style="font-size:11.5px;color:#9ca3af;">— sayıları görmek için sütunun üzerine gelin ya da dokunun</span>
        </div>
        <div class="js-saat-grafik" style="position:relative;">
          <div style="display:flex;align-items:flex-end;gap:2px;height:110px;border-bottom:1px solid #e5e7eb;">
            @foreach($saatToplam as $k => $n)
              @php
                $saatBas = (int) substr($k, 8, 2);
                $detay = [];
                foreach ($s24 as $s => $x) { $detay[] = [$s, $renk[$s] ?? '#6b7280', (int) ($x[$gAnahtar][$k] ?? 0)]; }
                $islenmedi = $gSon === '' || $k > $gSon;
                $kesinDegil = ! $islenmedi && $gEksik !== '' && $k >= $gEksik;
              @endphp
              <div class="js-saat-sutun" tabindex="0" data-baslik="{{ substr($k, 6, 2) }}.{{ substr($k, 4, 2) }} · {{ sprintf('%02d:00–%02d:00', $saatBas, ($saatBas + 1) % 24) }}" data-toplam="{{ $n }}" data-birim="{{ $birim }}" data-detay='@json($detay)' data-islenmedi="{{ $islenmedi ? 1 : 0 }}" data-kesin-degil="{{ $kesinDegil ? 1 : 0 }}"
                   aria-label="{{ sprintf('%02d:00', $saatBas) }}: {{ $islenmedi ? 'henüz gelmedi' : $n.' '.$birim }}" style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:flex-end;height:100%;cursor:pointer;border-radius:3px 3px 0 0;{{ $kesinDegil ? 'opacity:.45;' : '' }}">
                @if($islenmedi)
                  <div style="height:100%;border:1.5px dashed #cbd5e1;border-bottom:none;border-radius:3px 3px 0 0;background:repeating-linear-gradient(135deg,#f8fafc 0 4px,#eef2f7 4px 8px);"></div>
                @elseif($n === 0)
                  <div style="height:3px;background:#d1d5db;"></div>
                @else
                  @foreach($s24 as $s => $x)
                    @if(($x[$gAnahtar][$k] ?? 0) > 0)<div style="height:{{ round($x[$gAnahtar][$k] / $saatEnCok * 100, 1) }}%;background:{{ $renk[$s] ?? '#6b7280' }};"></div>@endif
                  @endforeach
                @endif
              </div>
            @endforeach
          </div>
          <div class="js-saat-kutu" role="status" style="display:none;position:absolute;bottom:118px;z-index:20;min-width:200px;background:#111827;color:#fff;border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.6;box-shadow:0 8px 24px rgba(0,0,0,.25);pointer-events:none;"></div>
        </div>
        <div style="display:flex;gap:2px;font-size:10.5px;color:#9ca3af;margin-top:3px;">
          @foreach($saatToplam as $k => $n)
            <div style="flex:1;min-width:0;text-align:center;white-space:nowrap;overflow:visible;">{{ (int) substr($k, 8, 2) % 3 === 0 ? substr($k, 8, 2) : '' }}</div>
          @endforeach
        </div>
      </div>
    @endforeach
    <script>
      (function () {
        function yaz(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        document.querySelectorAll('#son24 .js-saat-grafik').forEach(function (kap) {
          var kutu = kap.querySelector('.js-saat-kutu');
          var secili = null;
          function goster(el) {
            var h = '<div style="font-weight:800;margin-bottom:4px;">' + yaz(el.getAttribute('data-baslik')) + '</div>';
            if (el.getAttribute('data-islenmedi') === '1') {
              h += '<div>Google bu saati henüz göndermedi.</div><div style="color:#cbd5e1;">2–3 saat içinde gelir.</div>';
            } else {
              var detay = []; try { detay = JSON.parse(el.getAttribute('data-detay') || '[]'); } catch (e) {}
              detay.forEach(function (d) {
                h += '<div style="display:flex;align-items:center;gap:7px;"><span style="width:10px;height:10px;border-radius:2px;flex:none;background:' + d[1] + ';"></span>'
                  + '<span style="flex:1;">' + yaz(d[0]) + '</span><b>' + d[2] + '</b></div>';
              });
              h += '<div style="border-top:1px solid rgba(255,255,255,.2);margin-top:5px;padding-top:4px;display:flex;justify-content:space-between;gap:10px;"><span>Toplam</span><b>' + yaz(el.getAttribute('data-toplam')) + ' ' + yaz(el.getAttribute('data-birim')) + '</b></div>';
              if (el.getAttribute('data-kesin-degil') === '1') { h += '<div style="color:#fcd34d;margin-top:3px;">Google hâlâ sayıyor, artabilir.</div>'; }
            }
            kutu.innerHTML = h; kutu.style.display = 'block';
            var kr = kap.getBoundingClientRect(), sr = el.getBoundingClientRect(), g = kutu.offsetWidth;
            kutu.style.left = Math.max(0, Math.min(sr.left - kr.left + sr.width / 2 - g / 2, kr.width - g)) + 'px';
            if (secili) { secili.style.outline = ''; }
            secili = el; el.style.outline = '2px solid #111827';
          }
          function gizle() { kutu.style.display = 'none'; if (secili) { secili.style.outline = ''; secili = null; } }
          kap.querySelectorAll('.js-saat-sutun').forEach(function (el) {
            // Fare: üzerine gelince açılır, çıkınca kapanır. Dokunmatik ekran: dokununca açılır, dışarı dokununca kapanır.
            el.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { goster(el); } });
            el.addEventListener('focus', function () { goster(el); });
            el.addEventListener('click', function (e) { e.stopPropagation(); goster(el); });
          });
          kap.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { gizle(); } });
          document.addEventListener('click', function (e) { if (!kap.contains(e.target)) { gizle(); } });
        });
      })();
    </script>
  </div>
@endif

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
      <p style="{{ $acik }}">İlgi oranı %15'in altındaysa ziyaretlerin çoğu birkaç saniyede çıkıyor demektir; bu genellikle bot ya da yanlış yönlendirilmiş trafiktir. Hong Kong ve Çin'den gelen bot trafiği tüm rakamlardan çıkarılmıştır.</p>
      <div><table style="width:100%;border-collapse:collapse;">
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
      <h2 style="{{ $h2 }}">Günlük Ziyaret</h2>
      <div style="display:inline-flex;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;" id="gunluk-secim">
        <button type="button" data-m="etk" style="{{ $sec(true) }}border:0;cursor:pointer;">Etkileşimli ziyaret</button>
        <button type="button" data-m="kisi" style="{{ $sec(false) }}border:0;cursor:pointer;">Tüm ziyaretler</button>
      </div>
    </div>
    {{-- 18 Kasım 2026 (denetim): grafik ziyaret sayar; günlerin toplamı üstteki "Ziyaret (oturum)" kartıyla aynıdır. --}}
    <p style="{{ $acik }}margin:8px 0 0;">Her gün siteye yapılan ziyaret sayısı (Türkiye saatine göre). Günlerin toplamı üstteki <b>Ziyaret</b> ve <b>Etkileşimli ziyaret</b> kartlarıyla aynıdır.</p>
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

  {{-- Tür + en çok bakılanlar (kurumlar ve doktorlar; alt alta, tam genişlik).
       15 Kasım 2026: kullanıcı "doktorlar bölümünün verileri yok, bütün istatistiklere eklemelisin" dedi. --}}
  @php
    $listeTablo = function (array $satirlar, string $tur) use ($th, $td, $sayi) {
        $h = '<table style="width:100%;border-collapse:collapse;margin-top:8px;"><thead><tr><th style="'.$th.'text-align:left;">'.($tur === 'doktor' ? 'Doktor' : 'Kurum').'</th><th style="'.$th.'">Görüntüleme</th><th style="'.$th.'">Kişi</th></tr></thead><tbody>';
        foreach ($satirlar as $i => $f) {
            $link = $tur === 'doktor' ? route('admin.experts.edit', $f['id']) : route('admin.facilities.edit', $f['id']);
            $h .= '<tr><td style="'.$td.'text-align:left;white-space:normal;"><span style="color:#9ca3af;font-size:11.5px;">'.($i + 1).'.</span> '
                .'<a href="'.e($link).'" style="color:#111827;font-weight:700;text-decoration:none;">'.e($f['ad']).'</a> '
                .'<span style="font-size:11.5px;color:#9ca3af;">'.e($f['il']).' · '.e($tur === 'doktor' ? $f['dal'] : $f['tur']).'</span></td>'
                .'<td style="'.$td.'font-weight:800;">'.$sayi($f['goruntulenme']).'</td><td style="'.$td.'">'.$sayi($f['kisi']).'</td></tr>';
        }

        return $h.'</tbody></table>';
    };
    $doktorSecili = $gaBolum === 'doktorlar';
  @endphp
  <div>
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">Türe Göre İlgi <span style="font-size:12px;font-weight:700;color:#6b7280;">(kurumlar ve doktorlar)</span></h2>
      <p style="{{ $acik }}">Kurum ve doktor sayfalarının görüntülenmesinin türlere dağılımı.</p>
      <div style="height:{{ max(200, count($ga['turler']) * 34) }}px;"><canvas id="ga-tur"></canvas></div>
    </div>
    <div style="{{ $kart }}">
      <h2 style="{{ $h2 }}">🩺 Doktorlar — Uzmanlık Alanına Göre İlgi</h2>
      <p style="{{ $acik }}">Doktor sayfalarının (doktor kartı ve doktor web sitesi) uzmanlık alanlarına dağılımı.</p>
      @if(empty($ga['dallar']))
        <p style="font-size:13.5px;color:#9ca3af;">Bu dönemde doktor sayfası görüntülenmesi yok.</p>
      @else
        <div style="height:{{ max(200, count($ga['dallar']) * 30) }}px;"><canvas id="ga-dal"></canvas></div>
      @endif
    </div>
    {{-- 15 Kasım 2026: kullanıcının talebi - "çok yer kaplıyor", iki liste akordiyon (tıklayınca açılır); filtre seçiliyse açık gelir. --}}
    @php $akordiyonBaslik = 'cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:10px;'; $filtreVar = $gaIl || $gaBolum; @endphp
    <details style="{{ $kart }}" id="en-cok-bakilan" @if($filtreVar) open @endif>
      <summary style="{{ $akordiyonBaslik }}">
        <h2 style="{{ $h2 }}margin:0;">{{ $doktorSecili ? '🩺 En Çok Bakılan Doktorlar' : '🏠 En Çok Bakılan Kurumlar' }} <span style="font-size:12px;font-weight:700;color:#6b7280;">({{ count($doktorSecili ? $ga['doktorlar'] : $ga['kurumlar']) }})</span></h2>
        <span style="font-size:13px;font-weight:800;color:#0b5d8c;white-space:nowrap;">Göster / gizle ▾</span>
      </summary>
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end;margin-top:12px;">
        <form method="GET" action="{{ url()->current() }}#en-cok-bakilan" style="display:flex;gap:6px;flex-wrap:wrap;">
          <input type="hidden" name="gun" value="{{ $gaGun }}">@if($gaSite !== 'hepsi')<input type="hidden" name="site" value="{{ $gaSite }}">@endif
          <select name="il" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:8px;padding:4px 6px;font-size:12.5px;">
            <option value="">Türkiye geneli</option>
            @foreach($gaIller as $il)<option value="{{ $il->id }}" @selected($gaIl === $il->id)>{{ $il->name }}</option>@endforeach
          </select>
          <select name="bolum" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:8px;padding:4px 6px;font-size:12.5px;">
            <option value="">Tüm bölümler (kurumlar)</option>
            @foreach(service_sections() as $s)<option value="{{ $s['slug'] }}" @selected($gaBolum === $s['slug'])>{{ $s['title'] }}</option>@endforeach
            <option value="doktorlar" @selected($doktorSecili)>🩺 Doktorlar</option>
          </select>
        </form>
      </div>
      @php $liste = $doktorSecili ? $ga['doktorlar'] : $ga['kurumlar']; @endphp
      @if(empty($liste))
        <p style="font-size:13.5px;color:#9ca3af;margin-top:10px;">Bu filtreyle görüntülenen {{ $doktorSecili ? 'doktor' : 'kurum' }} yok.</p>
      @else
        {!! $listeTablo($liste, $doktorSecili ? 'doktor' : 'kurum') !!}
      @endif
    </details>
    @unless($doktorSecili)
      <details style="{{ $kart }}" id="en-cok-bakilan-doktor" @if($gaIl) open @endif>
        <summary style="{{ $akordiyonBaslik }}">
          <h2 style="{{ $h2 }}margin:0;">🩺 En Çok Bakılan Doktorlar <span style="font-size:12px;font-weight:700;color:#6b7280;">({{ count($ga['doktorlar']) }})</span></h2>
          <span style="font-size:13px;font-weight:800;color:#0b5d8c;white-space:nowrap;">Göster / gizle ▾</span>
        </summary>
        <p style="{{ $acik }}margin-top:10px;">Kurumlar listesindeki il seçimi bu listeye de uygulanır.</p>
        @if(empty($ga['doktorlar']))
          <p style="font-size:13.5px;color:#9ca3af;">Bu filtreyle görüntülenen doktor yok.</p>
        @else
          {!! $listeTablo($ga['doktorlar'], 'doktor') !!}
        @endif
      </details>
    @endunless
  </div>

  {{-- Trafik kaynakları --}}
  <div style="{{ $kart }}">
    <h2 style="{{ $h2 }}">Ziyaretçiler Nereden Geliyor?</h2>
    <p style="{{ $acik }}"><b>Doğrudan giriş</b> yüksek ama ilgi oranı çok düşükse bu trafik büyük olasılıkla bottur. Google'dan gelenler en değerli ziyaretçilerdir.</p>
    <div style="display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));align-items:start;">
      <div style="height:260px;"><canvas id="ga-kanal"></canvas></div>
      <div><table style="width:100%;border-collapse:collapse;">
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
    <div><table style="width:100%;border-collapse:collapse;">
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
      <div><table style="width:100%;border-collapse:collapse;margin-top:8px;">
        <thead><tr><th style="{{ $th }}text-align:left;">Aranan kelime</th><th style="{{ $th }}">Tıklama</th><th style="{{ $th }}">Gösterim</th><th style="{{ $th }}">Sıra</th></tr></thead>
        <tbody>@foreach($ga['sorgular'] as $kelime => $v)<tr><td style="{{ $td }}text-align:left;white-space:normal;">{{ $kelime }}</td><td style="{{ $td }}font-weight:800;">{{ $sayi($v['tiklama']) }}</td><td style="{{ $td }}">{{ $sayi($v['gosterim']) }}</td><td style="{{ $td }}">{{ number_format($v['sira'], 1, ',', '.') }}</td></tr>@endforeach</tbody>
      </table></div>
    </details>
  </div>

  @include('admin.site-stats._seo_firsat')

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
      'dal' => ['ad' => array_keys($ga['dallar']), 'deger' => array_values($ga['dallar'])],
      'kanal' => ['ad' => array_map(fn ($k) => $kanalAd[$k] ?? $k, array_keys($ga['kanallar'])), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['kanallar']))],
      'sehir' => ['ad' => array_keys($ga['sehirler']), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['sehirler']))],
      'cihaz' => ['ad' => array_map(fn ($k) => ['mobile' => 'Telefon', 'desktop' => 'Bilgisayar', 'tablet' => 'Tablet', 'smart tv' => 'Akıllı TV'][$k] ?? $k, array_keys($ga['cihazlar'])), 'deger' => array_map(fn ($v) => $v[0], array_values($ga['cihazlar']))],
      'sc' => ['gun' => array_map(fn ($g) => \Illuminate\Support\Carbon::parse($g)->format('d.m'), array_keys($ga['scGunluk'])), 'tik' => array_column($ga['scGunluk'], 'tiklama'), 'gos' => array_column($ga['scGunluk'], 'gosterim'),
        'site' => array_map(fn ($s) => ['ad' => $s, 'renk' => $renk[$s], 'tik' => array_map(fn ($g) => $g['site'][$s] ?? 0, array_values($ga['scGunluk']))], array_keys($ga['sc']))],
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
        data: { labels: d.ad, datasets: [{ label: id === 'ga-sehir' ? 'Ziyaretçi' : 'Görüntüleme', data: d.deger, backgroundColor: renk, borderRadius: 5 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: bicim } } } }
      });
    };
    yatay('ga-tur', G.tur, '#0b5d8c');
    if (document.getElementById('ga-dal')) yatay('ga-dal', G.dal, '#7c3aed');
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
      // 15 Kasım 2026: 3 site seçiliyken her sitenin tıklaması ayrı renkli çizgi; mor kesikli çizgi toplam gösterim.
      data: { labels: G.sc.gun, datasets: (G.sc.site.length > 1
          ? G.sc.site.map(function (s) { return { label: s.ad + ' tıklama', data: s.tik, borderColor: s.renk, backgroundColor: s.renk, tension: .3, pointRadius: 0, borderWidth: 2, yAxisID: 'y' }; })
          : [{ label: 'Tıklama', data: G.sc.tik, borderColor: '#0b5d8c', backgroundColor: '#0b5d8c22', fill: true, tension: .3, pointRadius: 0, borderWidth: 2, yAxisID: 'y' }])
        .concat([{ label: G.sc.site.length > 1 ? 'Gösterim (3 site toplam)' : 'Gösterim', data: G.sc.gos, borderColor: '#7c3aed', borderDash: [5, 4], tension: .3, pointRadius: 0, borderWidth: 2, yAxisID: 'y2' }]) },
      options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Tıklama' } }, y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Gösterim' } } } }
    });
  })();
  </script>
@endif
