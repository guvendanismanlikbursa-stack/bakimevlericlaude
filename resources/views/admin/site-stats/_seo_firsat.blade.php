{{-- 18 Kasım 2026: kullanıcının talebi - "kullanıcıların yaptığı aramalara göre SEO'muzu güçlendirmeliyiz".
     Search Console'un "aranan kelime + çıkan sayfamız" verisinden iş yapılabilir listeler (bkz. App\Support\SeoFirsatlari).
     Doktor/uzman sayfaları bilerek dahil edilmez (sağlık reklam yasağı). Satırlar dar ekranda alt alta dizilir, yatay kaydırma yok. --}}
@if(! empty($seo['siteler']))
  @php
    $seoSiteler = $gaSite === 'hepsi' ? array_keys($seo['siteler']) : array_values(array_intersect([$gaSite], array_keys($seo['siteler'])));
    $seoListe = function (string $tur) use ($seo, $seoSiteler) {
        $hepsi = [];
        foreach ($seoSiteler as $s) { foreach ($seo['siteler'][$s][$tur] ?? [] as $r) { $hepsi[] = $r + ['site' => $s]; } }
        usort($hepsi, fn ($a, $b) => $b['gos'] <=> $a['gos']);
        return array_slice($hepsi, 0, 25);
    };
    $seoSay = fn (string $tur) => array_sum(array_map(fn ($s) => $seo['siteler'][$s]['sayilar'][$tur] ?? 0, $seoSiteler));
    $seoBolumler = [
        ['genel', '🎯 Genel aramalar — asıl talep getiren aramalar', 'Kurum adı içermeyen aramalar (ör. "van kreş fiyatları", "kartepe bakım merkezi"). Aileler kurum ararken bunları yazar; bu aramalarda üstte olmak yeni talep demektir.'],
        ['az_tik', '👀 Görünüyoruz ama tıklanmıyoruz', 'İlk sayfadayız ama sıramıza göre beklenen tıklamanın yarısını bile alamıyoruz. Genelde sayfa başlığı ve açıklaması aramaya uymuyor; düzeltmesi kolay, etkisi hızlıdır.'],
        ['yakin', '📈 İlk sayfanın eşiğinde (8–20. sıra)', 'Biraz güçlendirilirse ilk sayfaya çıkabilecek aramalar. 2. sayfadaki sonuca neredeyse kimse tıklamaz; ilk sayfaya çıkmak tıklamayı katlar.'],
    ];
    $yanlisGos = array_sum(array_map(fn ($s) => $seo['siteler'][$s]['yanlis_adres_gos'] ?? 0, $seoSiteler));
  @endphp
  <div style="{{ $kart }}" id="seo-firsat">
    <h2 style="{{ $h2 }}">🚀 SEO Fırsatları <span style="font-size:12px;font-weight:700;color:#6b7280;">(Search Console)</span></h2>
    <p style="{{ $acik }}">Kaynak: <b>Google Search Console</b>, {{ \Illuminate\Support\Carbon::parse($seo['bas'])->format('d.m.Y') }} – {{ \Illuminate\Support\Carbon::parse($seo['son'])->format('d.m.Y') }} (son 4 hafta; Google bu veriyi 2–3 gün geriden verir, her sabah yenilenir).
      İnsanların Google'da gerçekten yazdığı aramalar ve o aramada çıkan sayfamız. <b>Görünme</b>: aramada listelendiğimiz sayı, <b>Tıklama</b>: siteye gelen, <b>Sıra</b>: ortalama sıramız (1 = en üst, 10'dan sonrası 2. sayfa).
      Doktor sayfaları bilerek dahil değil.</p>
    @foreach($seoBolumler as [$tur, $baslik, $aciklama])
      @php $liste = $seoListe($tur); @endphp
      <details style="border:1px solid #e5e7eb;border-radius:12px;padding:10px 14px;margin-bottom:10px;" {{ $loop->first ? 'open' : '' }}>
        <summary style="cursor:pointer;font-size:14.5px;font-weight:800;color:#111827;">{{ $baslik }} <span style="font-size:12.5px;font-weight:700;color:#6b7280;">({{ $sayi($seoSay($tur)) }} arama{{ $seoSay($tur) > 25 ? ', en çok görünen 25' : '' }})</span></summary>
        <div style="font-size:12.5px;color:#6b7280;margin:6px 0 8px;line-height:1.55;">{{ $aciklama }}</div>
        @forelse($liste as $r)
          <div style="border-top:1px solid #f1f5f9;padding:9px 0;">
            <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 10px;">
              <span style="font-size:14px;font-weight:800;color:#111827;overflow-wrap:anywhere;">“{{ $r['sorgu'] }}”</span>
              @if($gaSite === 'hepsi')<span style="font-size:11.5px;font-weight:800;color:{{ $renk[$r['site']] ?? '#6b7280' }};">{{ $r['site'] }}</span>@endif
            </div>
            <div style="font-size:12.5px;color:#374151;margin-top:2px;">
              Görünme <b>{{ $sayi($r['gos']) }}</b> · Tıklama <b>{{ $sayi($r['tik']) }}</b>@isset($r['beklenen']) <span style="color:#6b7280;">(sıramıza göre beklenen ~{{ $sayi($r['beklenen']) }})</span>@endisset · Sıra <b>{{ number_format($r['sira'], 1, ',', '.') }}</b>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;overflow-wrap:anywhere;">Çıkan sayfamız: <a href="{{ $r['sayfa'] }}" target="_blank" rel="noopener" style="color:#0b5d8c;">{{ $r['yol'] }}</a></div>
            <div style="font-size:12.5px;color:#065f46;margin-top:3px;">➜ {{ $r['oneri'] }}</div>
          </div>
        @empty
          <div style="font-size:13px;color:#9ca3af;">Bu listede arama yok.</div>
        @endforelse
      </details>
    @endforeach
    @if($yanlisGos)
      <div style="font-size:12.5px;color:#6b7280;margin-top:6px;line-height:1.55;">ℹ️ Google bazı aramalarda eski adreslerimizi (<b>/site/…</b>, <b>/public/…</b>) hâlâ gösteriyor (son 4 haftada {{ $sayi($yanlisGos) }} görünme). Bu adresler doğru sayfaya kalıcı olarak yönlendiriliyor; Google zamanla kendisi düzeltir, yapılacak iş yok.</div>
    @endif
  </div>
@endif
