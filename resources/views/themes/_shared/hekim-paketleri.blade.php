<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hekimler için İnternet Sitesi Paketleri | bakimevleri.com</title>
<meta name="robots" content="noindex,nofollow">
{{-- 30 Ekim 2026: kullanıcının talebi - hekim site paketleri (3 paket, hedef ortadaki).
     Bağımsız sayfa, kendi stili var (Tailwind derlemesine bağlı değil). Paket içerikleri
     yalnızca sistemde gerçekten olan özelliklerdir; TTB gereği yorum/puan/reklam yoktur. --}}
<style>
  :root{--lacivert:#13233a;--yesil:#0d6e6a;--altin:#b08d57;--altin-acik:#f6efe2;--gri:#5b6472;--cizgi:#e6e8ec;--zemin:#f6f7f9}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:var(--zemin);color:#111827;line-height:1.5}
  a{color:inherit}
  .kap{max-width:1120px;margin:0 auto;padding:0 16px}
  header.ust{background:var(--lacivert);color:#fff;padding:48px 0 96px;text-align:center}
  .etiket{display:inline-block;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#e9d3ab}
  h1{font-size:clamp(26px,4.4vw,40px);line-height:1.15;margin:10px 0 12px;font-weight:900}
  .alt{max-width:640px;margin:0 auto;color:rgba(255,255,255,.82);font-size:16.5px}
  .kisisel{display:inline-block;margin-top:18px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:7px 16px;font-size:14px;font-weight:700}
  .kartlar{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:-64px;align-items:stretch}
  .kart{background:#fff;border-radius:20px;border:1.5px solid var(--cizgi);padding:26px 22px;display:flex;flex-direction:column;box-shadow:0 10px 30px rgba(19,35,58,.07);position:relative}
  .kart.one{border:2.5px solid var(--altin);transform:translateY(-10px);box-shadow:0 18px 44px rgba(176,141,87,.25)}
  .rozet{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:var(--altin);color:#fff;font-size:12.5px;font-weight:900;border-radius:999px;padding:5px 14px;white-space:nowrap;letter-spacing:.02em}
  .kart h2{margin:0;font-size:22px;font-weight:900;color:var(--lacivert)}
  .tasarim{font-size:13.5px;color:var(--gri);font-weight:700;margin-top:2px}
  .fiyat{font-size:30px;font-weight:900;color:var(--lacivert);margin:16px 0 4px;letter-spacing:-.01em}
  .kart.one .fiyat{color:#8a6a35}
  .not{font-size:12.5px;color:var(--gri)}
  ul.ozellik{list-style:none;padding:0;margin:18px 0 20px;display:grid;gap:9px;font-size:14.5px}
  ul.ozellik li{display:flex;gap:9px;align-items:flex-start}
  ul.ozellik li .i{flex:0 0 20px;height:20px;border-radius:50%;display:inline-grid;place-items:center;font-size:12px;font-weight:900;background:#e7f3f2;color:var(--yesil)}
  ul.ozellik li.yok{color:#9ca3af}
  ul.ozellik li.yok .i{background:#f1f2f4;color:#b5bac2}
  .dugmeler{margin-top:auto;display:grid;gap:8px}
  .dg{display:block;text-align:center;text-decoration:none;font-weight:800;border-radius:12px;padding:12px 14px;font-size:15px}
  .dg.ana{background:var(--yesil);color:#fff}
  .kart.one .dg.ana{background:var(--altin)}
  .dg.ikinci{border:1.5px solid var(--cizgi);color:var(--lacivert);background:#fff}
  section.blok{margin:44px auto 0;background:#fff;border-radius:20px;border:1.5px solid var(--cizgi);padding:26px 24px}
  section.blok h3{margin:0 0 14px;font-size:20px;color:var(--lacivert);font-weight:900}
  table.kars{width:100%;border-collapse:collapse;font-size:14.5px}
  table.kars th,table.kars td{padding:11px 10px;border-bottom:1px solid var(--cizgi);text-align:center}
  table.kars th:first-child,table.kars td:first-child{text-align:left;color:#374151;font-weight:600}
  table.kars thead th{font-size:15px;color:var(--lacivert);font-weight:900}
  table.kars .orta{background:var(--altin-acik)}
  .tablo-sar{overflow-x:auto}
  .ortak{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px}
  .ortak div{background:var(--zemin);border-radius:14px;padding:14px 16px;font-size:14.5px}
  .ortak b{display:block;color:var(--lacivert);margin-bottom:3px}
  .sss details{border-bottom:1px solid var(--cizgi);padding:12px 0}
  .sss summary{cursor:pointer;font-weight:800;color:var(--lacivert)}
  .sss p{margin:8px 0 0;color:#374151}
  footer{text-align:center;color:var(--gri);font-size:13px;padding:36px 16px 48px}
  @media (max-width:900px){.kartlar{grid-template-columns:1fr;margin-top:-56px}.kart.one{transform:none;order:-1}}
</style>
</head>
<body>
@php
  $varMi = fn ($paket, $alt) => array_search($paket, array_keys($paketler)) >= array_search($alt, array_keys($paketler));
  $waLink = fn ($paket) => $wa ? 'https://wa.me/'.$wa.'?text='.rawurlencode('Merhaba, hekim internet sitesi "'.$paket.'" paketi hakkında bilgi almak istiyorum.'.($site ? ' ('.$site->fullName().')' : '')) : null;
  // 31 Ekim 2026: kullanıcının talebi - paketler ek iş gücü çıkarmayacak şekilde sadeleştirildi:
  // aylık rapor ve telefon desteği kaldırıldı, güncellemeler yıllık hakka bağlandı,
  // "rakamlar" (sağlık reklamı riski) satış vaadinden çıkarıldı.
  $ozellikler = [
      ['Kendi alan adınız ve güvenlik sertifikası (https)', 'temel'],
      ['Unvan, branş, adres, muayene saatleri, telefon ve WhatsApp', 'temel'],
      ['Telefonda kusursuz görünüm', 'temel'],
      ['Adres, telefon ve muayene saati değişikliği her zaman ücretsiz', 'temel'],
      ['Çok bölümlü profesyonel tasarım', 'profesyonel'],
      ['Logo, kendi renginiz ve yazı stiliniz', 'profesyonel'],
      ['Sık sorulan sorular, duyurular ve tanıtım videosu', 'profesyonel'],
      ['Kaydırınca canlanan hareketli tasarım', 'prestij'],
      ['Değişiklikleriniz öncelikli, aynı iş günü yapılır', 'prestij'],
  ];
  $guncelleme = ['temel' => 'Yılda 2 içerik değişikliği', 'profesyonel' => 'Yılda 6 içerik değişikliği', 'prestij' => 'Yılda 12 içerik değişikliği'];
  $destek = ['temel' => 'WhatsApp · mesai saatleri', 'profesyonel' => 'WhatsApp · mesai saatleri', 'prestij' => 'WhatsApp · öncelikli'];
@endphp

<header class="ust">
  <div class="kap">
    <span class="etiket">bakimevleri.com · Hekimler için</span>
    <h1>Muayenehaneniz için sade, güvenilir<br>ve kurallara uygun bir internet sitesi</h1>
    <p class="alt">Hasta ve yakınları sizi aradığında doğru adrese, doğru telefona ve güncel muayene saatlerine ulaşsın. Siteniz Türk Tabipleri Birliği tanıtım kılavuzu esas alınarak hazırlanır.</p>
    @if($site)<div class="kisisel">👋 {{ $site->fullName() }} — paketlerin örneklerini kendi sitenizde görebilirsiniz</div>@endif
  </div>
</header>

<main class="kap">
  <div class="kartlar">
    @foreach($paketler as $key => $p)
      @php $one = $key === 'profesyonel'; @endphp
      <div class="kart {{ $one ? 'one' : '' }}">
        @if($one)<span class="rozet">★ En çok tercih edilen</span>@endif
        <h2>{{ $p['ikon'] }} {{ $p['ad'] }}</h2>
        <div class="tasarim">{{ \App\Models\ExpertWebsite::DEMO_TASARIMLAR[$p['tasarim']] }}</div>
        <div class="fiyat">{{ $p['fiyat'] }}</div>
        <div class="not">Alan adı, barındırma ve güvenlik sertifikası dahil</div>
        <ul class="ozellik">
          @foreach($ozellikler as [$metin, $gerekli])
            @php $var = $varMi($key, $gerekli); @endphp
            <li class="{{ $var ? '' : 'yok' }}"><span class="i">{{ $var ? '✓' : '–' }}</span><span>{{ $metin }}</span></li>
          @endforeach
          <li><span class="i">↻</span><span><b>{{ $guncelleme[$key] }}</b></span></li>
          <li><span class="i">☎</span><span>Destek: <b>{{ $destek[$key] }}</b></span></li>
        </ul>
        <div class="dugmeler">
          @if($site)<a class="dg ikinci" href="{{ $site->demoUrl($p['tasarim']) }}" target="_blank" rel="noopener">👁 Kendi sitemde gör</a>@endif
          @if($waLink($p['ad']))<a class="dg ana" href="{{ $waLink($p['ad']) }}" target="_blank" rel="noopener">{{ $one ? 'Bu paketi seçiyorum' : 'Bilgi al' }}</a>@endif
        </div>
      </div>
    @endforeach
  </div>

  <section class="blok">
    <h3>Paketleri karşılaştırın</h3>
    <div class="tablo-sar">
      <table class="kars">
        <thead><tr><th></th>@foreach($paketler as $key => $p)<th class="{{ $key === 'profesyonel' ? 'orta' : '' }}">{{ $p['ikon'] }} {{ $p['ad'] }}</th>@endforeach</tr></thead>
        <tbody>
          <tr><td>Yıllık ücret</td>@foreach($paketler as $key => $p)<td class="{{ $key === 'profesyonel' ? 'orta' : '' }}"><b>{{ $p['fiyat'] }}</b></td>@endforeach</tr>
          <tr><td>Tasarım</td>@foreach($paketler as $key => $p)<td class="{{ $key === 'profesyonel' ? 'orta' : '' }}">{{ ['standart' => 'Standart, tek sayfa', 'klasik' => 'Profesyonel – Klasik', 'hareketli' => 'Profesyonel – Hareketli'][$p['tasarim']] }}</td>@endforeach</tr>
          @foreach($ozellikler as [$metin, $gerekli])
            <tr><td>{{ $metin }}</td>@foreach($paketler as $key => $p)<td class="{{ $key === 'profesyonel' ? 'orta' : '' }}">{!! $varMi($key, $gerekli) ? '<b style="color:#0d6e6a">✓</b>' : '<span style="color:#c0c4cb">–</span>' !!}</td>@endforeach</tr>
          @endforeach
          <tr><td>İçerik değişikliği</td>@foreach($paketler as $key => $p)<td class="{{ $key === 'profesyonel' ? 'orta' : '' }}">{{ $guncelleme[$key] }}</td>@endforeach</tr>
          <tr><td>Destek</td>@foreach($paketler as $key => $p)<td class="{{ $key === 'profesyonel' ? 'orta' : '' }}">{{ $destek[$key] }}</td>@endforeach</tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="blok">
    <h3>Her pakette</h3>
    <div class="ortak">
      <div><b>⚖️ Kurallara uygun</b>Türk Tabipleri Birliği tanıtım kılavuzu esas alınır; sitede zorunlu bilgilendirme cümlesi yer alır.</div>
      <div><b>🚫 Yorum, puan, reklam yok</b>Hasta yorumu, puanlama ve reklam niteliğinde ifade kullanılmaz.</div>
      <div><b>📞 Hasta doğrudan size ulaşır</b>Sitede aracı form yoktur; hasta muayenehanenizi telefonla veya WhatsApp'tan arar.</div>
      <div><b>📋 Rehberde yeriniz ücretsiz</b>bakimevleri.com rehberinde listelenme tüm hekimler için ücretsiz ve alfabetiktir; site paketiyle ilgisi yoktur, sıralamayı etkilemez.</div>
    </div>
  </section>

  <section class="blok sss">
    <h3>Sık sorulan sorular</h3>
    <details><summary>Alan adını kim alıyor?</summary><p>Alan adı (ör. drayseyilmaz.com) paket ücretine dahildir; sizin adınıza biz alır ve sitenize bağlarız. Halihazırda bir alan adınız varsa onu kullanabiliriz.</p></details>
    <details><summary>Bilgilerimi nasıl güncelleyeceğim?</summary><p>Değişikliği WhatsApp'tan iletmeniz yeterli, biz yaparız. Adres, telefon ve muayene saati değişiklikleri her pakette sınırsız ve ücretsizdir. Metin, fotoğraf, sık sorulan soru veya duyuru gibi içerik değişiklikleri paketinizdeki yıllık hak kadar yapılır; bir mesajda birden fazla değişiklik iletebilirsiniz.</p></details>
    <details><summary>Ziyaretçi sayısı veya hasta garantisi var mı?</summary><p>Hayır. Siteniz, sizi arayan hastanın doğru bilgiye ulaşması içindir; ziyaretçi, arama veya hasta sayısı taahhüt edilmez.</p></details>
    <details><summary>Sitede hasta yorumu neden yok?</summary><p>Türk Tabipleri Birliği'nin tanıtım kılavuzu hasta beyanı ve yorumlarıyla tanıtımı yasaklar. Siteniz bu kurala uygun hazırlanır.</p></details>
  </section>
</main>

<footer>bakimevleri.com · Sayfa içeriği bilgilendirme amaçlıdır.</footer>
</body>
</html>
