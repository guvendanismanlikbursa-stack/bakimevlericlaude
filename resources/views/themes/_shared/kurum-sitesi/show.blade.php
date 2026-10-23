{{-- 23 Ekim 2026: kullanıcının talebi - kurumun internet sitesi. Platform
     arayüzünden bağımsız, kendi başına bir site gibi görünür. İçerik kurum
     kaydından (fotoğraf, hizmet, menü, telefon, adres) ve siteye özel
     alanlardan (başlık, alt yazı, hakkımızda, renk, bölümler) gelir.
     Stiller satır içi: canlıda Tailwind yeniden derlenmiyor. --}}
@php
  $f = $facility;
  $ana = $renk['ana']; $koyu = $renk['koyu']; $acik = $renk['acik'];
  $baslik = $site->heroTitle();
  $altBaslik = $site->heroSubtitle();
  $hakkimizda = $site->aboutText();
  $telefon = $f->phone;
  $telLink = $telefon ? 'tel:'.preg_replace('/[^0-9+]/', '', $telefon) : null;
  $adres = trim(($f->address ?: '').($f->district && ! str_contains((string) $f->address, (string) $f->district) ? ', '.$f->district : '').($f->city ? ' / '.$f->city->name : ''), ' ,/');
  $haritaLink = $f->google_maps_url ?: ($f->lat && $f->lng ? 'https://www.google.com/maps?q='.$f->lat.','.$f->lng : null);
  $hizmetler = collect($f->services ?? [])->filter()->values();
  $bosYer = \App\Models\FacilityWebsite::vacancyText($f);
  $temsili = $temsili ?? false;
  $metaAciklama = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $hakkimizda ?: $altBaslik)), 155);
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $baslik }}{{ $f->city ? ' | '.$f->city->name : '' }}</title>
<meta name="description" content="{{ $metaAciklama }}">
<meta name="robots" content="{{ $isPreview ? 'noindex,nofollow' : 'index,follow' }}">
<link rel="canonical" href="{{ $site->publicUrl() }}">
<meta property="og:title" content="{{ $baslik }}">
<meta property="og:description" content="{{ $metaAciklama }}">
@if($hero && ! $temsili)<meta property="og:image" content="{{ facility_asset($hero->path) }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap">
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => $f->name, 'url' => $site->publicUrl(),
    'telephone' => $telefon ?: null, 'image' => ($hero && ! $temsili) ? facility_asset($hero->path) : null, 'description' => $metaAciklama ?: null,
    'address' => $adres ? ['@type' => 'PostalAddress', 'streetAddress' => $f->address, 'addressLocality' => $f->district, 'addressRegion' => $f->city->name ?? null, 'addressCountry' => 'TR'] : null,
    'geo' => ($f->lat && $f->lng) ? ['@type' => 'GeoCoordinates', 'latitude' => $f->lat, 'longitude' => $f->lng] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<style>
  :root{--ana:{{ $ana }};--koyu:{{ $koyu }};--acik:{{ $acik }};--metin:#1f2a2e;--soluk:#5b6b70;--cizgi:#e6ecea;--zemin:#f7f9f8}
  *{box-sizing:border-box}
  html{scroll-behavior:smooth}
  body{margin:0;font-family:"Nunito Sans","Segoe UI",system-ui,sans-serif;color:var(--metin);background:#fff;font-size:16px;line-height:1.65}
  a{color:inherit}
  img{max-width:100%;display:block}
  .kap{max-width:1120px;margin:0 auto;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
  .bolum{padding:56px 0}
  @media(min-width:768px){.bolum{padding:84px 0}}
  .ust-etiket{font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:var(--ana)}
  h1,h2,h3{line-height:1.2;margin:0;text-wrap:balance}
  h2{font-size:clamp(26px,4vw,36px);font-weight:900;margin:8px 0 16px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-weight:800;border-radius:14px;padding:14px 20px;text-decoration:none;border:0;cursor:pointer;font-size:15px}
  .btn-ana{background:var(--ana);color:#fff}
  .btn-ana:hover{background:var(--koyu)}
  .btn-wa{background:#1fa855;color:#fff}
  .btn-cerceve{background:#fff;color:var(--koyu);border:2px solid var(--cizgi)}
  /* Üst menü */
  .menu{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.96);backdrop-filter:saturate(1.4) blur(8px);border-bottom:1px solid var(--cizgi)}
  .menu .kap{display:flex;align-items:center;justify-content:space-between;gap:12px;height:64px}
  .logo{font-weight:900;font-size:18px;color:var(--koyu);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:60vw}
  .menu nav{display:none;gap:22px;font-weight:700;font-size:15px}
  .menu nav a{text-decoration:none;color:var(--soluk)}
  .menu nav a:hover{color:var(--ana)}
  @media(min-width:900px){.menu nav{display:flex}}
  /* Giriş */
  .giris{position:relative;color:#fff;background:linear-gradient(135deg,var(--koyu),var(--ana))}
  .giris-foto{position:absolute;inset:0;background-size:cover;background-position:center}
  .giris-foto:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(10,20,22,.82) 0%,rgba(10,20,22,.55) 55%,rgba(10,20,22,.25) 100%)}
  .giris .kap{position:relative;padding-top:clamp(64px,12vw,140px);padding-bottom:clamp(64px,12vw,140px)}
  .giris h1{font-size:clamp(32px,6vw,58px);font-weight:900;max-width:760px}
  .giris p{font-size:clamp(17px,2.2vw,20px);max-width:620px;margin:18px 0 28px;color:rgba(255,255,255,.92)}
  .giris .btnler{display:flex;flex-wrap:wrap;gap:12px}
  .rozet{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:7px 14px;font-size:13px;font-weight:800;margin-bottom:18px}
  /* Bilgi şeridi */
  .serit{background:var(--zemin);border-bottom:1px solid var(--cizgi)}
  .serit .kap{display:grid;grid-template-columns:1fr;gap:12px;padding-top:22px;padding-bottom:22px}
  @media(min-width:760px){.serit .kap{grid-template-columns:repeat(3,minmax(0,1fr))}}
  .serit div{display:flex;gap:12px;align-items:flex-start;font-size:15px}
  .serit b{display:block;color:var(--koyu)}
  .ikon{flex:none;width:40px;height:40px;border-radius:12px;background:var(--acik);display:flex;align-items:center;justify-content:center;font-size:19px}
  .ikili{display:grid;gap:36px;align-items:center}
  @media(min-width:880px){.ikili{grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);gap:56px}}
  .yazi{white-space:pre-line;color:var(--soluk);font-size:17px}
  .kart-foto{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:22px;box-shadow:0 22px 50px rgba(0,0,0,.14)}
  .hizmetler{display:grid;grid-template-columns:1fr;gap:12px;margin-top:8px}
  @media(min-width:640px){.hizmetler{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(min-width:960px){.hizmetler{grid-template-columns:repeat(3,minmax(0,1fr))}}
  .hizmet{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid var(--cizgi);border-radius:16px;padding:16px 18px;font-weight:700}
  .hizmet:before{content:"✓";flex:none;width:28px;height:28px;border-radius:50%;background:var(--acik);color:var(--ana);display:flex;align-items:center;justify-content:center;font-weight:900}
  .galeri{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  @media(min-width:760px){.galeri{grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}}
  .galeri img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:16px}
  .yorumlar{display:grid;grid-template-columns:1fr;gap:16px}
  @media(min-width:760px){.yorumlar{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(min-width:1040px){.yorumlar{grid-template-columns:repeat(3,minmax(0,1fr))}}
  .yorum{background:#fff;border:1px solid var(--cizgi);border-radius:18px;padding:22px}
  .yildiz{color:#f2a51a;letter-spacing:2px}
  .yorum p{margin:10px 0 12px;color:var(--soluk)}
  .yorum b{font-size:14px}
  .bos-yer{display:inline-flex;align-items:center;gap:10px;background:var(--acik);color:var(--koyu);border-radius:16px;padding:16px 20px;font-weight:800;font-size:17px}
  .harita{width:100%;height:340px;border:0;border-radius:20px}
  .iletisim{background:linear-gradient(135deg,var(--koyu),var(--ana));color:#fff}
  .iletisim .yazi{color:rgba(255,255,255,.88)}
  .form{background:#fff;color:var(--metin);border-radius:22px;padding:24px;display:grid;gap:12px;box-shadow:0 20px 50px rgba(0,0,0,.18)}
  .form label{font-size:14px;font-weight:800}
  .form input,.form textarea{width:100%;font:inherit;border:1.5px solid var(--cizgi);border-radius:12px;padding:12px 14px;background:#fff;color:var(--metin)}
  .form input:focus,.form textarea:focus{outline:2px solid var(--ana);outline-offset:1px;border-color:transparent}
  .basari{background:#e8f7ee;color:#14532d;border-radius:12px;padding:12px 14px;font-weight:700}
  .hata{color:#b91c1c;font-size:14px}
  .alt{background:#10191c;color:rgba(255,255,255,.75);font-size:14px}
  .alt .kap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;padding-top:28px;padding-bottom:28px}
  .yuzen{position:fixed;right:max(16px,env(safe-area-inset-right,0px));bottom:calc(16px + env(safe-area-inset-bottom,0px));z-index:30;display:flex;flex-direction:column;gap:10px}
  .yuzen a{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;text-decoration:none;box-shadow:0 10px 24px rgba(0,0,0,.25)}
  .temsili{position:absolute;right:12px;bottom:10px;z-index:2;background:rgba(0,0,0,.55);color:#fff;font-size:11px;font-weight:700;border-radius:999px;padding:3px 10px}
  .onizleme{background:#fde047;color:#422006;text-align:center;font-weight:800;font-size:14px;padding:8px 16px}
  :focus-visible{outline:3px solid var(--ana);outline-offset:2px}
</style>
</head>
<body>

@if($isPreview)
  <div class="onizleme">Önizleme — bu site henüz yayında değil. Yalnızca siz görüyorsunuz.</div>
@endif

<header class="menu">
  <div class="kap">
    <a href="#" class="logo">{{ $f->name }}</a>
    <nav aria-label="Sayfa içi menü">
      @if($site->sectionEnabled('hakkimizda') && $hakkimizda)<a href="#hakkimizda">Hakkımızda</a>@endif
      @if($site->sectionEnabled('hizmetler') && $hizmetler->isNotEmpty())<a href="#hizmetler">Hizmetler</a>@endif
      @if($site->sectionEnabled('galeri') && $gallery->isNotEmpty())<a href="#galeri">Galeri</a>@endif
      @if($site->sectionEnabled('yorumlar') && $reviews->isNotEmpty())<a href="#yorumlar">Yorumlar</a>@endif
      <a href="#iletisim">İletişim</a>
    </nav>
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-ana" style="padding:10px 16px;">📞 Hemen Ara</a>@endif
  </div>
</header>

<section class="giris">
  @if($hero)<div class="giris-foto" style="background-image:url('{{ facility_asset($hero->path) }}');"></div>@endif
  @if($hero && $temsili)<span class="temsili">Temsili görseldir</span>@endif
  <div class="kap">
    @if($f->category)<div class="rozet">{{ $f->category->name }}@if($f->city) · {{ $f->city->name }}@endif</div>@endif
    <h1>{{ $baslik }}</h1>
    @if($altBaslik)<p>{{ $altBaslik }}</p>@endif
    <div class="btnler">
      @if($telLink)<a href="{{ $telLink }}" class="btn btn-ana">📞 {{ $telefon }}</a>@endif
      @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Merhaba, '.$f->name.' hakkında bilgi almak istiyorum.') }}" target="_blank" rel="noopener" class="btn btn-wa">WhatsApp'tan Yazın</a>@endif
      <a href="#iletisim" class="btn btn-cerceve">Bilgi ve Ziyaret Talebi</a>
    </div>
  </div>
</section>

<div class="serit">
  <div class="kap">
    @if($adres)<div><span class="ikon">📍</span><span><b>Adres</b>{{ $adres }}</span></div>@endif
    @if($telefon)<div><span class="ikon">📞</span><span><b>Telefon</b><a href="{{ $telLink }}" style="text-decoration:none;">{{ $telefon }}</a></span></div>@endif
    @if($site->sectionEnabled('bos_yer') && $bosYer)<div><span class="ikon">🛏️</span><span><b>Kontenjan</b>{{ $bosYer }}</span></div>
    @elseif($f->category)<div><span class="ikon">🏠</span><span><b>Kurum türü</b>{{ $f->category->name }}</span></div>@endif
  </div>
</div>

@if($site->sectionEnabled('hakkimizda') && $hakkimizda)
  <section id="hakkimizda" class="bolum">
    <div class="kap ikili">
      <div>
        <div class="ust-etiket">Hakkımızda</div>
        <h2>{{ $f->name }}</h2>
        <div class="yazi">{{ $hakkimizda }}</div>
      </div>
      @php $ikinci = $gallery->first(fn ($g) => ! $hero || $g->id !== $hero->id) ?? $hero; @endphp
      @if($ikinci)<div style="position:relative;"><img src="{{ facility_asset($ikinci->path) }}" alt="{{ $temsili ? 'Temsili görsel' : $f->name }}" class="kart-foto" loading="lazy">@if($temsili)<span class="temsili">Temsili görseldir</span>@endif</div>@endif
    </div>
  </section>
@endif

@if($site->sectionEnabled('hizmetler') && $hizmetler->isNotEmpty())
  <section id="hizmetler" class="bolum" style="background:var(--zemin);">
    <div class="kap">
      <div class="ust-etiket">Hizmetlerimiz</div>
      <h2>Size neler sunuyoruz?</h2>
      <div class="hizmetler">
        @foreach($hizmetler as $h)<div class="hizmet">{{ $h }}</div>@endforeach
      </div>
    </div>
  </section>
@endif

@if($site->sectionEnabled('galeri') && $gallery->isNotEmpty())
  <section id="galeri" class="bolum">
    <div class="kap">
      <div class="ust-etiket">Fotoğraflar</div>
      <h2>{{ $temsili ? 'Görseller' : 'Kurumumuzdan kareler' }}</h2>
      @if($temsili)<p style="margin:-6px 0 16px;color:var(--soluk);font-size:14px;">Görseller temsilidir; kurumun kendi fotoğraflarını göstermez.</p>@endif
      <div class="galeri">
        @foreach($gallery->take(12) as $g)
          <a href="{{ facility_asset($g->path) }}" target="_blank" rel="noopener"><img src="{{ facility_thumb($g->path) }}" alt="{{ $temsili ? 'Temsili görsel' : $f->name.' fotoğraf' }} {{ $loop->iteration }}" loading="lazy"></a>
        @endforeach
      </div>
    </div>
  </section>
@endif

@if($site->sectionEnabled('menu') && $f->menu_image_path)
  <section id="menu" class="bolum" style="background:var(--zemin);">
    <div class="kap" style="max-width:820px;">
      <div class="ust-etiket">Beslenme</div>
      <h2>Yemek menümüz</h2>
      <a href="{{ facility_asset($f->menu_image_path) }}" target="_blank" rel="noopener"><img src="{{ facility_asset($f->menu_image_path) }}" alt="{{ $f->name }} yemek menüsü" loading="lazy" style="border-radius:18px;border:1px solid var(--cizgi);"></a>
    </div>
  </section>
@endif

@if($site->sectionEnabled('yorumlar') && $reviews->isNotEmpty())
  <section id="yorumlar" class="bolum">
    <div class="kap">
      <div class="ust-etiket">Aileler ne diyor?</div>
      <h2>Ailelerin yorumları</h2>
      <div class="yorumlar">
        @foreach($reviews as $r)
          <div class="yorum">
            <div class="yildiz" aria-label="{{ $r['puan'] }} yıldız">{{ str_repeat('★', max(1, min(5, $r['puan']))) }}</div>
            <p>{{ \Illuminate\Support\Str::limit($r['metin'], 320) }}</p>
            <b>{{ $r['ad'] }}</b>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

@if($site->sectionEnabled('konum') && ($f->lat && $f->lng))
  <section id="konum" class="bolum" style="background:var(--zemin);">
    <div class="kap">
      <div class="ust-etiket">Konum</div>
      <h2>Bize nasıl ulaşırsınız?</h2>
      <iframe class="harita" title="{{ $f->name }} harita" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q={{ $f->lat }},{{ $f->lng }}&z=15&output=embed"></iframe>
      @if($haritaLink)<p style="margin-top:14px;"><a href="{{ $haritaLink }}" target="_blank" rel="noopener" class="btn btn-cerceve">Yol tarifi al</a></p>@endif
    </div>
  </section>
@endif

<section id="iletisim" class="bolum iletisim">
  <div class="kap ikili">
    <div>
      <div class="ust-etiket" style="color:rgba(255,255,255,.8);">İletişim</div>
      <h2 style="color:#fff;">Bilgi almak ya da ziyarete gelmek ister misiniz?</h2>
      <div class="yazi">Formu doldurun, sizi en kısa sürede arayalım. Dilerseniz doğrudan telefonla da ulaşabilirsiniz.</div>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:22px;">
        @if($telLink)<a href="{{ $telLink }}" class="btn btn-cerceve">📞 {{ $telefon }}</a>@endif
        @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-wa">WhatsApp</a>@endif
      </div>
      @if($adres)<p class="yazi" style="margin-top:18px;">📍 {{ $adres }}</p>@endif
    </div>
    <form method="POST" action="{{ $formAction }}" class="form">
      @csrf
      @include('themes._shared.partials.honeypot')
      @if(session('kurum_sitesi_basarili'))<div class="basari" role="status">{{ session('kurum_sitesi_basarili') }}</div>@endif
      <div>
        <label for="ks-ad">Adınız soyadınız</label>
        <input id="ks-ad" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name">
        @error('full_name')<div class="hata">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="ks-tel">Telefonunuz</label>
        <input id="ks-tel" name="phone" value="{{ old('phone') }}" required maxlength="30" inputmode="tel" autocomplete="tel">
        @error('phone')<div class="hata">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="ks-mesaj">Mesajınız (isteğe bağlı)</label>
        <textarea id="ks-mesaj" name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
      </div>
      @if($whatsapp)
        <button type="submit" class="btn btn-wa" style="width:100%;">WhatsApp ile Gönder</button>
        <p style="margin:0;font-size:12px;color:var(--soluk);">Gönder'e basınca WhatsApp açılır, mesajınız hazır yazılı gelir; göndermeniz yeterli.</p>
      @else
        <button type="submit" class="btn btn-ana" style="width:100%;">Gönder</button>
      @endif
      <p style="margin:0;font-size:12px;color:var(--soluk);">Bilgileriniz yalnızca kurumun size dönüş yapması için kullanılır.</p>
    </form>
  </div>
</section>

<footer class="alt">
  <div class="kap">
    <span>© {{ now()->year }} {{ $f->name }}</span>
    <span>Altyapı: <a href="https://bakimevleri.com/kurumlar/{{ $f->slug }}" target="_blank" rel="noopener" style="color:#fff;">bakimevleri.com</a></span>
  </div>
</footer>

<div class="yuzen">
  @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp" style="background:#1fa855;color:#fff;">💬</a>@endif
  @if($telLink)<a href="{{ $telLink }}" aria-label="Telefonla ara" style="background:var(--ana);color:#fff;">📞</a>@endif
</div>

</body>
</html>
