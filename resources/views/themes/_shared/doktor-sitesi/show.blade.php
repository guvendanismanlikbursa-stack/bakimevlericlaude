{{-- 23 Ekim 2026: kullanıcının talebi - doktorun internet sitesi. Platform
     arayüzünden bağımsız, kendi başına bir site gibi görünür. Telefon, adres,
     branş, klinik doktor kaydından; başlık, hakkımda, ilgi alanları, çalışma
     saatleri, fotoğraf, renk siteye özel alanlardan gelir. Hukuki nedenle
     hasta yorumu ve puan gösterilmez. Stiller satır içi: canlıda Tailwind
     yeniden derlenmiyor. --}}
@php
  $e = $expert;
  $ana = $renk['ana']; $koyu = $renk['koyu']; $acik = $renk['acik'];
  $adSoyad = $site->fullName();
  $baslik = $site->heroTitle();
  $altBaslik = $site->heroSubtitle();
  $hakkimda = $site->aboutText();
  $ilgi = $site->ilgiAlanlariListesi();
  $saatler = trim((string) $site->calisma_saatleri);
  $brans = $e->branch_label ?: $e->branch;
  $telefon = $e->phone;
  $telLink = $telefon ? 'tel:'.preg_replace('/[^0-9+]/', '', $telefon) : null;
  $adres = trim(($e->address ?: '').($e->district && ! str_contains((string) $e->address, (string) $e->district) ? ', '.$e->district : '').($e->city ? ' / '.$e->city->name : ''), ' ,/');
  $haritaLink = $e->google_maps_url ?: ($e->lat && $e->lng ? 'https://www.google.com/maps?q='.$e->lat.','.$e->lng : null);
  $foto = $site->photo_path ? facility_asset($site->photo_path) : null;
  $basHarf = mb_strtoupper(mb_substr((string) $e->name, 0, 1, 'UTF-8'), 'UTF-8');
  $metaAciklama = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $hakkimda ?: $altBaslik)), 155);
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $baslik }}{{ $brans ? ' | '.$brans : '' }}{{ $e->city ? ' | '.$e->city->name : '' }}</title>
<meta name="description" content="{{ $metaAciklama }}">
<meta name="robots" content="{{ $isPreview ? 'noindex,nofollow' : 'index,follow' }}">
<link rel="canonical" href="{{ $site->publicUrl() }}">
<meta property="og:title" content="{{ $baslik }}">
<meta property="og:description" content="{{ $metaAciklama }}">
@if($foto)<meta property="og:image" content="{{ $foto }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap">
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Physician', 'name' => $adSoyad, 'url' => $site->publicUrl(),
    'medicalSpecialty' => $brans ?: null, 'telephone' => $telefon ?: null, 'image' => $foto, 'description' => $metaAciklama ?: null,
    'address' => $adres ? ['@type' => 'PostalAddress', 'streetAddress' => $e->address, 'addressLocality' => $e->district, 'addressRegion' => $e->city->name ?? null, 'addressCountry' => 'TR'] : null,
    'geo' => ($e->lat && $e->lng) ? ['@type' => 'GeoCoordinates', 'latitude' => $e->lat, 'longitude' => $e->lng] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<style>
  :root{--ana:{{ $ana }};--koyu:{{ $koyu }};--acik:{{ $acik }};--metin:#1f2a2e;--soluk:#5b6b70;--cizgi:#e6ecea;--zemin:#f7f9f8}
  *{box-sizing:border-box}
  html{scroll-behavior:smooth}
  body{margin:0;font-family:"Nunito Sans","Segoe UI",system-ui,sans-serif;color:var(--metin);background:#fff;font-size:16px;line-height:1.65}
  a{color:inherit}
  img{max-width:100%;display:block}
  .kap{max-width:1080px;margin:0 auto;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
  .bolum{padding:56px 0}
  @media(min-width:768px){.bolum{padding:80px 0}}
  .ust-etiket{font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:var(--ana)}
  h1,h2,h3{line-height:1.2;margin:0;text-wrap:balance}
  h2{font-size:clamp(26px,4vw,34px);font-weight:900;margin:8px 0 16px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-weight:800;border-radius:14px;padding:14px 20px;text-decoration:none;border:0;cursor:pointer;font-size:15px}
  .btn-ana{background:var(--ana);color:#fff}
  .btn-ana:hover{background:var(--koyu)}
  .btn-wa{background:#1fa855;color:#fff}
  .btn-cerceve{background:#fff;color:var(--koyu);border:2px solid var(--cizgi)}
  .menu{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.96);backdrop-filter:saturate(1.4) blur(8px);border-bottom:1px solid var(--cizgi)}
  .menu .kap{display:flex;align-items:center;justify-content:space-between;gap:12px;height:64px}
  .logo{font-weight:900;font-size:18px;color:var(--koyu);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:60vw}
  .menu nav{display:none;gap:22px;font-weight:700;font-size:15px}
  .menu nav a{text-decoration:none;color:var(--soluk)}
  .menu nav a:hover{color:var(--ana)}
  @media(min-width:900px){.menu nav{display:flex}}
  .giris{background:linear-gradient(135deg,var(--koyu),var(--ana));color:#fff}
  .giris .kap{display:grid;gap:28px;align-items:center;padding-top:clamp(48px,9vw,96px);padding-bottom:clamp(48px,9vw,96px)}
  @media(min-width:820px){.giris .kap{grid-template-columns:minmax(0,1fr) 300px;gap:48px}}
  .giris h1{font-size:clamp(30px,5.5vw,52px);font-weight:900}
  .giris p{font-size:clamp(17px,2.2vw,20px);margin:16px 0 26px;color:rgba(255,255,255,.92)}
  .giris .btnler{display:flex;flex-wrap:wrap;gap:12px}
  .rozet{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:7px 14px;font-size:13px;font-weight:800;margin-bottom:16px}
  .portre{width:100%;max-width:300px;aspect-ratio:4/5;border-radius:26px;object-fit:cover;box-shadow:0 24px 60px rgba(0,0,0,.28);justify-self:center;border:4px solid rgba(255,255,255,.35)}
  .portre-bos{width:100%;max-width:300px;aspect-ratio:4/5;border-radius:26px;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:96px;font-weight:900;justify-self:center}
  @media(max-width:819px){.portre,.portre-bos{max-width:220px;order:-1}}
  .serit{background:var(--zemin);border-bottom:1px solid var(--cizgi)}
  .serit .kap{display:grid;grid-template-columns:1fr;gap:12px;padding-top:22px;padding-bottom:22px}
  @media(min-width:760px){.serit .kap{grid-template-columns:repeat(3,minmax(0,1fr))}}
  .serit div{display:flex;gap:12px;align-items:flex-start;font-size:15px}
  .serit b{display:block;color:var(--koyu)}
  .ikon{flex:none;width:40px;height:40px;border-radius:12px;background:var(--acik);display:flex;align-items:center;justify-content:center;font-size:19px}
  .ikili{display:grid;gap:36px;align-items:start}
  @media(min-width:880px){.ikili{grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);gap:56px}}
  .yazi{white-space:pre-line;color:var(--soluk);font-size:17px}
  .etiketler{display:flex;flex-wrap:wrap;gap:10px}
  .etiketler span{background:#fff;border:1px solid var(--cizgi);border-radius:999px;padding:9px 16px;font-weight:700;font-size:15px}
  .saat-kutu{background:#fff;border:1px solid var(--cizgi);border-radius:18px;padding:20px 22px;white-space:pre-line;font-weight:600}
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
    <a href="#" class="logo">{{ $adSoyad }}</a>
    <nav aria-label="Sayfa içi menü">
      @if($site->sectionEnabled('hakkimda') && $hakkimda)<a href="#hakkimda">Hakkımda</a>@endif
      @if($site->sectionEnabled('ilgi_alanlari') && $ilgi)<a href="#ilgi-alanlari">İlgi Alanları</a>@endif
      @if($site->sectionEnabled('konum') && $e->lat && $e->lng)<a href="#konum">Konum</a>@endif
      <a href="#iletisim">Randevu</a>
    </nav>
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-ana" style="padding:10px 16px;">📞 Randevu</a>@endif
  </div>
</header>

<section class="giris">
  <div class="kap">
    <div>
      @if($brans)<div class="rozet">{{ $brans }}@if($e->city) · {{ $e->city->name }}@endif</div>@endif
      <h1>{{ $baslik }}</h1>
      @if($altBaslik)<p>{{ $altBaslik }}</p>@endif
      <div class="btnler">
        @if($telLink)<a href="{{ $telLink }}" class="btn btn-ana" style="background:#fff;color:var(--koyu);">📞 {{ $telefon }}</a>@endif
        @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Merhaba, randevu almak istiyorum.') }}" target="_blank" rel="noopener" class="btn btn-wa">WhatsApp'tan Yazın</a>@endif
        <a href="#iletisim" class="btn btn-cerceve">Randevu Talebi</a>
      </div>
    </div>
    @if($foto)
      <img src="{{ $foto }}" alt="{{ $adSoyad }}" class="portre">
    @else
      <div class="portre-bos" aria-hidden="true">{{ $basHarf }}</div>
    @endif
  </div>
</section>

<div class="serit">
  <div class="kap">
    @if($brans)<div><span class="ikon">🩺</span><span><b>Uzmanlık</b>{{ $brans }}</span></div>@endif
    @if($e->clinic_name || $adres)<div><span class="ikon">📍</span><span><b>{{ $e->clinic_name ?: 'Adres' }}</b>{{ $adres }}</span></div>@endif
    @if($telefon)<div><span class="ikon">📞</span><span><b>Telefon</b><a href="{{ $telLink }}" style="text-decoration:none;">{{ $telefon }}</a></span></div>@endif
  </div>
</div>

@if($site->sectionEnabled('hakkimda') && $hakkimda)
  <section id="hakkimda" class="bolum">
    <div class="kap ikili">
      <div>
        <div class="ust-etiket">Hakkımda</div>
        <h2>{{ $adSoyad }}</h2>
        <div class="yazi">{{ $hakkimda }}</div>
      </div>
      @if($site->sectionEnabled('calisma_saatleri') && $saatler !== '')
        <div>
          <div class="ust-etiket">Çalışma saatleri</div>
          <h2 style="font-size:24px;">Muayene saatleri</h2>
          <div class="saat-kutu">{{ $saatler }}</div>
        </div>
      @endif
    </div>
  </section>
@elseif($site->sectionEnabled('calisma_saatleri') && $saatler !== '')
  <section class="bolum">
    <div class="kap" style="max-width:720px;">
      <div class="ust-etiket">Çalışma saatleri</div>
      <h2>Muayene saatleri</h2>
      <div class="saat-kutu">{{ $saatler }}</div>
    </div>
  </section>
@endif

@if($site->sectionEnabled('ilgi_alanlari') && $ilgi)
  <section id="ilgi-alanlari" class="bolum" style="background:var(--zemin);">
    <div class="kap">
      <div class="ust-etiket">İlgi alanları</div>
      <h2>Hangi konularda hizmet veriyorum?</h2>
      <div class="etiketler">
        @foreach($ilgi as $alan)<span>{{ $alan }}</span>@endforeach
      </div>
    </div>
  </section>
@endif

@if($site->sectionEnabled('konum') && ($e->lat && $e->lng))
  <section id="konum" class="bolum">
    <div class="kap">
      <div class="ust-etiket">Konum</div>
      <h2>Muayenehaneye nasıl ulaşırsınız?</h2>
      <iframe class="harita" title="{{ $adSoyad }} harita" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q={{ $e->lat }},{{ $e->lng }}&z=15&output=embed"></iframe>
      @if($haritaLink)<p style="margin-top:14px;"><a href="{{ $haritaLink }}" target="_blank" rel="noopener" class="btn btn-cerceve">Yol tarifi al</a></p>@endif
    </div>
  </section>
@endif

<section id="iletisim" class="bolum iletisim">
  <div class="kap ikili">
    <div>
      <div class="ust-etiket" style="color:rgba(255,255,255,.8);">Randevu</div>
      <h2 style="color:#fff;">Randevu almak ya da bilgi almak ister misiniz?</h2>
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
      @if(session('doktor_sitesi_basarili'))<div class="basari" role="status">{{ session('doktor_sitesi_basarili') }}</div>@endif
      <div>
        <label for="ds-ad">Adınız soyadınız</label>
        <input id="ds-ad" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name">
        @error('full_name')<div class="hata">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="ds-tel">Telefonunuz</label>
        <input id="ds-tel" name="phone" value="{{ old('phone') }}" required maxlength="30" inputmode="tel" autocomplete="tel">
        @error('phone')<div class="hata">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="ds-mesaj">Mesajınız (isteğe bağlı)</label>
        <textarea id="ds-mesaj" name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
      </div>
      @if($whatsapp)
        <button type="submit" class="btn btn-wa" style="width:100%;">WhatsApp ile Gönder</button>
        <p style="margin:0;font-size:12px;color:var(--soluk);">Gönder'e basınca WhatsApp açılır, mesajınız hazır yazılı gelir; göndermeniz yeterli.</p>
      @else
        <button type="submit" class="btn btn-ana" style="width:100%;">Gönder</button>
      @endif
      <p style="margin:0;font-size:12px;color:var(--soluk);">Bilgileriniz yalnızca size dönüş yapılması için kullanılır. Lütfen sağlık bilgilerinizi bu forma yazmayın.</p>
    </form>
  </div>
</section>

<footer class="alt">
  <div class="kap">
    <span>© {{ now()->year }} {{ $adSoyad }}</span>
    <span>Altyapı: <a href="https://bakimevleri.com" target="_blank" rel="noopener" style="color:#fff;">bakimevleri.com</a></span>
  </div>
</footer>

<div class="yuzen">
  @if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp" style="background:#1fa855;color:#fff;">💬</a>@endif
  @if($telLink)<a href="{{ $telLink }}" aria-label="Telefonla ara" style="background:var(--ana);color:#fff;">📞</a>@endif
</div>

</body>
</html>
