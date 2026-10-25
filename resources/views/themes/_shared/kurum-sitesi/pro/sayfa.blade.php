{{-- 23 Ekim 2026: kullanıcının talebi - "Profesyonel Site" şablonu. Kurumun
     logosu ve rengiyle, çok sayfalı, premium görünümlü site. Sayfalar:
     ana sayfa + içeriği olan iç sayfalar (bkz. KurumSitesiController::proPages).
     Stiller satır içi: canlıda Tailwind yeniden derlenmiyor. --}}
@php
  $f = $facility;
  $ad = $f->name;
  $ana = $renk['ana']; $koyu = $renk['koyu']; $acik = $renk['acik'];
  $zarif = ($site->font_style ?: 'zarif') !== 'modern';
  $baslik = $site->heroTitle();
  $altBaslik = $site->heroSubtitle();
  $hakkimizda = trim($site->aboutText());
  $telefon = $f->phone;
  $telLink = $telefon ? 'tel:'.preg_replace('/[^0-9+]/', '', $telefon) : null;
  $waLink = $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Merhaba, '.$ad.' hakkında bilgi almak istiyorum.') : null;
  $adres = trim(($f->address ?: '').($f->district && ! str_contains((string) $f->address, (string) $f->district) ? ', '.$f->district : '').($f->city ? ' / '.$f->city->name : ''), ' ,/');
  $haritaLink = $f->google_maps_url ?: ($f->lat && $f->lng ? 'https://www.google.com/maps?q='.$f->lat.','.$f->lng : null);
  $hizmetler = $site->sectionEnabled('hizmetler') ? collect($f->services ?? [])->filter()->values() : collect();
  $bosYer = $site->sectionEnabled('bos_yer') ? \App\Models\FacilityWebsite::vacancyText($f) : null;
  $rakamlar = $site->listOf('stats');
  $degerler = $site->listOf('values_list');
  $ekip = $site->listOf('team');
  $sss = $site->listOf('faqs');
  $duyurular = $site->listOf('announcements');
  $video = $site->videoEmbedUrl();
  $kendiVideo = (! $video && $f->video_path) ? facility_asset($f->video_path) : null;
  $logo = $site->logo_path ? facility_asset($site->logo_path) : null;
  $harf = mb_strtoupper(mb_substr($ad, 0, 1, 'UTF-8'), 'UTF-8');
  $galeriAcik = $site->sectionEnabled('galeri') && $gallery->isNotEmpty();
  $yorumlar = $site->sectionEnabled('yorumlar') ? $reviews : collect();
  $haritaVar = $site->sectionEnabled('konum') && $f->lat && $f->lng;
  $puan = (float) ($f->rating ?: 0);
  $foto = fn ($img) => facility_asset($img->path);
  $ikinci = $gallery->first(fn ($g) => ! $hero || $g->id !== $hero->id);
  $ucuncu = $gallery->first(fn ($g) => (! $hero || $g->id !== $hero->id) && (! $ikinci || $g->id !== $ikinci->id));
  $sayfaAdi = $sayfa !== '' ? ($sayfalar[$sayfa] ?? '') : '';
  $sayfaBasligi = match ($sayfa) {
      'hakkimizda' => 'Hakkımızda',
      'hizmetler' => 'Hizmetlerimiz',
      'galeri' => 'Galeri',
      'ekibimiz' => 'Ekibimiz',
      'duyurular' => 'Duyurular',
      'sss' => 'Sık Sorulan Sorular',
      'iletisim' => 'İletişim',
      default => $baslik,
  };
  $metaAciklama = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $hakkimizda ?: $altBaslik)), 155);
  $kanonik = rtrim($site->publicUrl(), '/').($sayfa !== '' ? '/'.$sayfa : '');
  $kisaHakkimizda = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $hakkimizda), 520, '…', preserveWords: true);
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $sayfa !== '' ? $sayfaBasligi.' | '.$ad : $baslik.($f->city ? ' | '.$f->city->name : '') }}</title>
<meta name="description" content="{{ $metaAciklama }}">
<meta name="robots" content="{{ $isPreview || $demoPro ? 'noindex,nofollow' : 'index,follow' }}">
<meta name="theme-color" content="{{ $koyu }}">
<link rel="canonical" href="{{ $kanonik }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $sayfa !== '' ? $sayfaBasligi.' | '.$ad : $baslik }}">
<meta property="og:description" content="{{ $metaAciklama }}">
@if($hero && ! $temsili)<meta property="og:image" content="{{ $foto($hero) }}">@endif
@if($logo)<link rel="icon" href="{{ $logo }}">@endif
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@if($zarif)
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap">
@else
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
@endif
@if($sayfa === '')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => $ad, 'url' => $site->publicUrl(),
    'telephone' => $telefon ?: null, 'image' => ($hero && ! $temsili) ? $foto($hero) : null, 'logo' => $logo, 'description' => $metaAciklama ?: null,
    'address' => $adres ? ['@type' => 'PostalAddress', 'streetAddress' => $f->address, 'addressLocality' => $f->district, 'addressRegion' => $f->city->name ?? null, 'addressCountry' => 'TR'] : null,
    'geo' => ($f->lat && $f->lng) ? ['@type' => 'GeoCoordinates', 'latitude' => $f->lat, 'longitude' => $f->lng] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@if($sayfa === 'sss' && $sss)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($sss)->map(fn ($s) => ['@type' => 'Question', 'name' => $s['soru'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $s['cevap']]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
<style>
  :root{
    --ana:{{ $ana }};--koyu:{{ $koyu }};--acik:{{ $acik }};
    --ink:#141c1b;--govde:#3b4543;--soluk:#6c7673;--cizgi:#ebe6dc;--krem:#f9f6f0;--altin:#b08d57;
    --baslik:{!! $zarif ? '"Fraunces",Georgia,"Times New Roman",serif' : '"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif' !!};
    --metin:{!! $zarif ? '"Manrope","Segoe UI",system-ui,sans-serif' : '"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif' !!};
    --baslik-agirlik:{{ $zarif ? 500 : 800 }};
    --golge:0 1px 2px rgba(16,24,22,.05),0 18px 40px -18px rgba(16,24,22,.22);
    --yuvarlak:22px;
  }
  *,*::before,*::after{box-sizing:border-box}
  html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
  body{margin:0;font-family:var(--metin);color:var(--govde);background:#fff;font-size:16.5px;line-height:1.75;-webkit-font-smoothing:antialiased}
  img{max-width:100%;display:block}
  a{color:inherit}
  h1,h2,h3,h4{font-family:var(--baslik);font-weight:var(--baslik-agirlik);color:var(--ink);line-height:1.12;margin:0;letter-spacing:{{ $zarif ? '-.015em' : '-.025em' }};text-wrap:balance}
  h2{font-size:clamp(30px,4.4vw,50px)}
  h3{font-size:clamp(20px,2vw,24px)}
  p{margin:0}
  .kap{width:100%;max-width:1200px;margin:0 auto;padding-left:max(20px,env(safe-area-inset-left,0px));padding-right:max(20px,env(safe-area-inset-right,0px))}
  @media(min-width:900px){.kap{padding-left:40px;padding-right:40px}}
  .bolum{padding:84px 0}
  @media(min-width:900px){.bolum{padding:120px 0}}
  .krem{background:var(--krem)}
  .etiket{display:inline-flex;align-items:center;gap:12px;font-family:var(--metin);font-size:12.5px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--ana);margin-bottom:18px}
  .etiket::before{content:"";width:30px;height:1.5px;background:var(--altin)}
  .giris-metni{font-size:clamp(17px,1.5vw,19px);color:var(--soluk);max-width:640px}
  .bolum-bas{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:20px 40px;margin-bottom:48px}
  .bolum-bas .sol{max-width:680px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;font-family:var(--metin);font-weight:700;font-size:15.5px;border-radius:999px;padding:15px 28px;text-decoration:none;border:0;cursor:pointer;transition:transform .25s ease,background .25s ease,box-shadow .25s ease,color .25s ease;white-space:nowrap}
  .btn:hover{transform:translateY(-2px)}
  .btn-dolu{background:var(--ana);color:#fff;box-shadow:0 10px 24px -10px var(--ana)}
  .btn-dolu:hover{background:var(--koyu)}
  .btn-beyaz{background:#fff;color:var(--ink)}
  .btn-cam{background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.45);backdrop-filter:blur(6px)}
  .btn-cam:hover{background:rgba(255,255,255,.22)}
  .btn-cizgi{background:transparent;color:var(--ink);border:1.5px solid var(--cizgi)}
  .btn-cizgi:hover{border-color:var(--ana);color:var(--ana)}
  .btn-wa{background:#1f9d55;color:#fff}
  .btn-wa:hover{background:#178246}
  .ok-link{display:inline-flex;align-items:center;gap:10px;font-weight:800;color:var(--ana);text-decoration:none;font-size:15.5px}
  .ok-link span{transition:transform .25s ease}
  .ok-link:hover span{transform:translateX(5px)}
  .ikon{width:22px;height:22px;flex:none}

  /* Üst menü */
  .ust{position:fixed;inset:0 0 auto 0;z-index:50;transition:background .35s ease,box-shadow .35s ease,color .35s ease;color:#fff}
  .ust .ic{display:flex;align-items:center;justify-content:space-between;gap:20px;height:84px}
  .ust.dolu{background:rgba(255,255,255,.97);backdrop-filter:saturate(1.6) blur(12px);color:var(--ink);box-shadow:0 1px 0 var(--cizgi),0 10px 30px -20px rgba(0,0,0,.3)}
  .marka{display:flex;align-items:center;gap:12px;text-decoration:none;min-width:0}
  .marka img{max-height:52px;max-width:180px;width:auto;object-fit:contain}
  .monogram{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--baslik);font-size:22px;font-weight:600;border:1.5px solid currentColor;flex:none}
  .marka-ad{display:flex;flex-direction:column;line-height:1.15;min-width:0}
  .marka-ad b{font-family:var(--baslik);font-weight:600;font-size:18px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:46vw}
  @media(min-width:1080px){.marka-ad b{max-width:300px}}
  .marka-ad small{font-size:11.5px;letter-spacing:.14em;text-transform:uppercase;opacity:.75;font-weight:700}
  .menu{display:none;align-items:center;gap:24px}
  .menu a{position:relative;text-decoration:none;font-weight:600;font-size:14.5px;padding:6px 0;opacity:.92;white-space:nowrap}
  .menu a::after{content:"";position:absolute;left:0;right:0;bottom:0;height:1.5px;background:currentColor;transform:scaleX(0);transform-origin:left;transition:transform .3s ease}
  .menu a:hover::after,.menu a.aktif::after{transform:scaleX(1)}
  .ust-cta{display:none}
  @media(min-width:1080px){.menu{display:flex}.ust-cta{display:inline-flex}.hamburger{display:none!important}}
  .ust .btn-dolu{padding:12px 22px;font-size:14.5px}
  .ust:not(.dolu) .btn-dolu{background:#fff;color:var(--ink);box-shadow:none}
  .hamburger{width:46px;height:46px;border-radius:50%;border:1.5px solid currentColor;background:transparent;color:inherit;display:flex;align-items:center;justify-content:center;cursor:pointer;flex:none}
  .cekmece{position:fixed;inset:0;z-index:60;background:var(--ink);color:#fff;display:flex;flex-direction:column;padding:24px max(24px,env(safe-area-inset-left,0px)) calc(24px + env(safe-area-inset-bottom,0px));transform:translateY(-100%);transition:transform .45s cubic-bezier(.7,0,.2,1);visibility:hidden}
  .cekmece.acik{transform:none;visibility:visible}
  .cekmece .kapat{align-self:flex-end;width:46px;height:46px;border-radius:50%;border:1.5px solid rgba(255,255,255,.4);background:transparent;color:#fff;font-size:22px;cursor:pointer}
  .cekmece nav{display:flex;flex-direction:column;gap:6px;margin-top:28px}
  .cekmece nav a{font-family:var(--baslik);font-size:32px;text-decoration:none;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.08)}
  .cekmece .alt-btn{margin-top:auto;display:grid;gap:10px}

  /* Giriş */
  .giris{position:relative;min-height:min(100svh,940px);display:flex;align-items:flex-end;color:#fff;overflow:hidden;background:linear-gradient(135deg,var(--koyu),var(--ana))}
  .giris.ic-sayfa{min-height:clamp(340px,52vh,520px)}
  .giris-foto{position:absolute;inset:0;background-size:cover;background-position:center;transform:scale(1.06);animation:yaklas 18s ease-out forwards}
  @keyframes yaklas{to{transform:scale(1)}}
  .giris::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(9,14,13,.55) 0%,rgba(9,14,13,.25) 35%,rgba(9,14,13,.78) 100%)}
  .giris.desensiz::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(255,255,255,.14),transparent 45%),radial-gradient(circle at 10% 90%,rgba(255,255,255,.08),transparent 40%)}
  .giris .kap{position:relative;z-index:2;padding-top:150px;padding-bottom:clamp(90px,12vh,140px)}
  .giris.ic-sayfa .kap{padding-bottom:64px}
  .giris .etiket{color:#fff}
  .giris .etiket::before{background:rgba(255,255,255,.7)}
  .giris h1{color:#fff;font-size:clamp(40px,6.6vw,84px);max-width:920px}
  .giris.ic-sayfa h1{font-size:clamp(38px,5.4vw,64px)}
  .giris p.alt{font-size:clamp(17px,1.7vw,21px);max-width:620px;margin-top:22px;color:rgba(255,255,255,.88)}
  .giris .btnler{display:flex;flex-wrap:wrap;gap:12px;margin-top:36px}
  @media(max-width:560px){.giris .btnler .btn{flex:1 1 100%}}
  .guven{display:flex;flex-wrap:wrap;gap:12px 28px;margin-top:40px;font-size:14.5px;color:rgba(255,255,255,.88)}
  .guven span{display:inline-flex;align-items:center;gap:8px}
  .yildizlar{color:#f5c26b;letter-spacing:2px}
  .yol{font-size:14px;color:rgba(255,255,255,.8);margin-bottom:16px}
  .yol a{text-decoration:none}
  .yol a:hover{text-decoration:underline}
  .temsili{position:absolute;right:16px;bottom:14px;z-index:3;background:rgba(0,0,0,.55);color:#fff;font-size:11.5px;font-weight:700;border-radius:999px;padding:4px 12px}

  /* Bilgi kartı (girişin altına biner) */
  .bilgi-kart{position:relative;z-index:5;margin-top:-64px;background:#fff;border-radius:var(--yuvarlak);box-shadow:var(--golge);display:grid;grid-template-columns:1fr;overflow:hidden}
  @media(min-width:720px){.bilgi-kart{grid-template-columns:repeat(var(--sutun,3),minmax(0,1fr))}}
  .bilgi-kart > div{padding:26px 30px;display:flex;gap:16px;align-items:flex-start;border-bottom:1px solid var(--cizgi)}
  @media(min-width:720px){.bilgi-kart > div{border-bottom:0;border-right:1px solid var(--cizgi)}.bilgi-kart > div:last-child{border-right:0}}
  .bilgi-kart .rakam{font-family:var(--baslik);white-space:nowrap;font-size:clamp(26px,7vw,34px);color:var(--ana);line-height:1;font-weight:var(--baslik-agirlik)}
  .bilgi-kart .rakam-et{font-size:14px;color:var(--soluk);margin-top:8px;font-weight:600}
  .bilgi-kart .yuvar{width:46px;height:46px;border-radius:14px;background:var(--acik);color:var(--ana);display:flex;align-items:center;justify-content:center;flex:none}
  .bilgi-kart b{display:block;color:var(--ink);font-size:15px}
  .bilgi-kart small{font-size:14.5px;color:var(--soluk);line-height:1.5;display:block}
  .bilgi-kart.rakamlar{grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;background:var(--cizgi)}
  @media(min-width:720px){.bilgi-kart.rakamlar{grid-template-columns:repeat(var(--sutun,4),minmax(0,1fr))}}
  @media(max-width:719px){.bilgi-kart.rakamlar > div:last-child:nth-child(odd){grid-column:1/-1}}
  .bilgi-kart.rakamlar > div{flex-direction:column;gap:0;align-items:flex-start;background:#fff;border:0;padding:22px 20px}
  @media(min-width:720px){.bilgi-kart.rakamlar > div{padding:28px 30px}.bilgi-kart .rakam{font-size:clamp(34px,3.4vw,46px)}}

  /* Hakkımızda */
  .ikili{display:grid;gap:56px;align-items:center}
  @media(min-width:960px){.ikili{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:88px}}
  .kolaj{position:relative;padding:0 40px 56px 0;max-width:560px}
  .kolaj::before{content:"";position:absolute;top:28px;left:-18px;width:62%;height:78%;border:1.5px solid var(--altin);border-radius:var(--yuvarlak);opacity:.55}
  .kolaj .b1{position:relative;width:100%;aspect-ratio:4/5;object-fit:cover;border-radius:var(--yuvarlak);box-shadow:var(--golge)}
  .kolaj .b2{position:absolute;right:0;bottom:0;width:48%;aspect-ratio:1/1;object-fit:cover;border-radius:18px;border:8px solid #fff;box-shadow:var(--golge)}
  .kolaj .rozet-yil{position:absolute;left:-10px;bottom:24px;background:var(--ana);color:#fff;border-radius:18px;padding:16px 20px;box-shadow:var(--golge);max-width:180px}
  .kolaj .rozet-yil b{display:block;font-family:var(--baslik);font-size:30px;line-height:1;font-weight:var(--baslik-agirlik)}
  .kolaj .rozet-yil span{font-size:13px;opacity:.9}
  .yazi-blok{white-space:pre-line;font-size:17.5px;color:var(--govde)}
  .yazi-blok + .ok-link,.giris-metni + .ok-link{margin-top:28px}

  /* Kartlar */
  .izgara{display:grid;gap:20px;grid-template-columns:1fr}
  @media(min-width:640px){.izgara{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(min-width:1000px){.izgara.uc{grid-template-columns:repeat(3,minmax(0,1fr))}.izgara.dort{grid-template-columns:repeat(4,minmax(0,1fr))}}
  .deger{background:#fff;border:1px solid var(--cizgi);border-radius:var(--yuvarlak);padding:34px 30px;transition:transform .3s ease,box-shadow .3s ease}
  .deger:hover{transform:translateY(-4px);box-shadow:var(--golge)}
  .deger .no{font-family:var(--baslik);font-size:44px;line-height:1;color:var(--altin);font-weight:400;margin-bottom:22px;display:block}
  .deger p{color:var(--soluk);margin-top:12px;font-size:16px}
  .hizmet{display:flex;gap:16px;align-items:center;background:#fff;border:1px solid var(--cizgi);border-radius:18px;padding:20px 22px;font-weight:700;color:var(--ink);transition:border-color .25s ease,transform .25s ease}
  .hizmet:hover{border-color:var(--ana);transform:translateY(-2px)}
  .hizmet .tik{width:40px;height:40px;border-radius:12px;background:var(--acik);color:var(--ana);display:flex;align-items:center;justify-content:center;flex:none}

  /* Video */
  .koyu-bolum{background:var(--ink);color:rgba(255,255,255,.82)}
  .koyu-bolum h2{color:#fff}
  .koyu-bolum .etiket{color:#e9d3ab}
  .video-kutu{position:relative;width:100%;aspect-ratio:16/9;border-radius:var(--yuvarlak);overflow:hidden;box-shadow:0 30px 70px -30px rgba(0,0,0,.7);background:#000}
  .video-kutu iframe,.video-kutu video{position:absolute;inset:0;width:100%;height:100%;border:0}

  /* Galeri */
  .galeri-izgara{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:170px}
  @media(min-width:860px){.galeri-izgara{grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:220px;gap:18px}.galeri-izgara .buyuk{grid-column:span 2;grid-row:span 2}}
  .galeri-oge{position:relative;display:block;border-radius:18px;overflow:hidden;cursor:zoom-in;background:var(--krem)}
  .galeri-oge img{width:100%;height:100%;object-fit:cover;transition:transform .8s cubic-bezier(.2,.7,.2,1)}
  .galeri-oge:hover img{transform:scale(1.05)}
  .masonry{columns:1;column-gap:18px}
  @media(min-width:640px){.masonry{columns:2}}
  @media(min-width:1000px){.masonry{columns:3}}
  .masonry .galeri-oge{margin-bottom:18px;break-inside:avoid}
  .masonry .galeri-oge img{height:auto}
  .isik{position:fixed;inset:0;z-index:80;background:rgba(10,14,13,.94);display:none;align-items:center;justify-content:center;padding:60px 16px}
  .isik.acik{display:flex}
  .isik img{max-width:min(1100px,100%);max-height:84vh;border-radius:14px;object-fit:contain}
  .isik button{position:absolute;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:#fff;width:52px;height:52px;border-radius:50%;font-size:22px;cursor:pointer}
  .isik .kapat{top:18px;right:18px}
  .isik .onceki{left:16px;top:50%;transform:translateY(-50%)}
  .isik .sonraki{right:16px;top:50%;transform:translateY(-50%)}

  /* Yorumlar */
  .yorum-serit{display:grid;grid-auto-flow:column;grid-auto-columns:min(88%,420px);gap:20px;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:12px;scrollbar-width:thin}
  @media(min-width:1000px){.yorum-serit{grid-auto-flow:row;grid-template-columns:repeat(3,minmax(0,1fr));overflow:visible}}
  .yorum{scroll-snap-align:start;background:#fff;border-radius:var(--yuvarlak);padding:34px 32px;border:1px solid var(--cizgi);display:flex;flex-direction:column}
  .yorum .tirnak{font-family:Georgia,serif;font-size:64px;line-height:.6;color:var(--altin);height:30px}
  .yorum p{margin:14px 0 24px;color:var(--govde);font-size:16.5px;flex:1}
  .yorum .kim{display:flex;align-items:center;gap:12px;font-weight:800;color:var(--ink);font-size:15px}
  .yorum .kim i{width:40px;height:40px;border-radius:50%;background:var(--acik);color:var(--ana);display:flex;align-items:center;justify-content:center;font-style:normal;font-family:var(--baslik)}

  /* Ekip */
  .izgara.ekip{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
  @media(min-width:1000px){.izgara.ekip{grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}}
  @media(max-width:639px){.uye h3{font-size:17px}.uye .unvan{font-size:13px}.uye .resim span{font-size:48px}}
  .uye{text-align:left}
  .uye .resim{position:relative;aspect-ratio:4/5;border-radius:var(--yuvarlak);overflow:hidden;background:linear-gradient(160deg,var(--acik),#fff);display:flex;align-items:center;justify-content:center;margin-bottom:18px}
  .uye .resim img{width:100%;height:100%;object-fit:cover;transition:transform .8s ease}
  .uye:hover .resim img{transform:scale(1.04)}
  .uye .resim span{font-family:var(--baslik);font-size:72px;color:var(--ana);opacity:.7}
  .uye h3{font-size:21px}
  .uye .unvan{color:var(--ana);font-weight:700;font-size:14.5px;margin-top:4px}
  .uye p.ac{color:var(--soluk);font-size:15px;margin-top:10px}

  /* Duyurular */
  .duyuru{background:#fff;border:1px solid var(--cizgi);border-radius:var(--yuvarlak);padding:30px;display:flex;flex-direction:column;gap:12px}
  .duyuru time{font-size:13px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--altin)}
  .duyuru p{color:var(--soluk);white-space:pre-line}

  /* SSS */
  .sss{border-top:1px solid var(--cizgi)}
  .sss details{border-bottom:1px solid var(--cizgi)}
  .sss summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:24px;padding:26px 0;font-family:var(--baslik);font-size:clamp(19px,1.8vw,23px);color:var(--ink);font-weight:var(--baslik-agirlik)}
  .sss summary::-webkit-details-marker{display:none}
  .sss summary .arti{width:40px;height:40px;border-radius:50%;border:1.5px solid var(--cizgi);display:flex;align-items:center;justify-content:center;flex:none;transition:transform .3s ease,background .3s ease,color .3s ease;font-family:var(--metin);font-size:20px}
  .sss details[open] summary .arti{transform:rotate(45deg);background:var(--ana);color:#fff;border-color:var(--ana)}
  .sss .cevap{padding:0 64px 28px 0;color:var(--soluk);white-space:pre-line}

  /* Çağrı bandı */
  .cagri{position:relative;overflow:hidden;border-radius:calc(var(--yuvarlak) + 8px);background:linear-gradient(120deg,var(--koyu),var(--ana));color:#fff;padding:clamp(44px,6vw,80px) clamp(24px,5vw,72px);display:grid;gap:28px;align-items:center}
  @media(min-width:900px){.cagri{grid-template-columns:1.4fr auto}}
  .cagri::after{content:"";position:absolute;right:-120px;top:-120px;width:360px;height:360px;border-radius:50%;border:1.5px solid rgba(255,255,255,.18)}
  .cagri::before{content:"";position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;border:1.5px solid rgba(255,255,255,.12)}
  .cagri h2{color:#fff;font-size:clamp(28px,3.6vw,44px)}
  .cagri p{color:rgba(255,255,255,.85);margin-top:12px;font-size:17px}
  .cagri .btnler{display:flex;flex-wrap:wrap;gap:12px;position:relative;z-index:1}

  /* İletişim */
  .iletisim-izgara{display:grid;gap:28px}
  @media(min-width:960px){.iletisim-izgara{grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:48px}}
  .kanal{display:flex;gap:16px;align-items:flex-start;padding:22px 0;border-bottom:1px solid var(--cizgi);text-decoration:none}
  .kanal .yuvar{width:50px;height:50px;border-radius:16px;background:var(--acik);color:var(--ana);display:flex;align-items:center;justify-content:center;flex:none}
  .kanal b{display:block;color:var(--ink);font-size:15px}
  .kanal span{color:var(--soluk);font-size:15.5px}
  .form-kart{background:#fff;border-radius:calc(var(--yuvarlak) + 4px);box-shadow:var(--golge);padding:clamp(26px,3.4vw,44px);border:1px solid var(--cizgi)}
  .form-kart h3{font-size:clamp(24px,2.4vw,30px)}
  .form-kart form{display:grid;gap:16px;margin-top:22px}
  .form-kart label{display:block;font-size:13.5px;font-weight:800;color:var(--ink);margin-bottom:6px}
  .form-kart input,.form-kart textarea{width:100%;font:inherit;font-size:16px;border:1.5px solid var(--cizgi);border-radius:14px;padding:14px 16px;background:var(--krem);color:var(--ink);transition:border-color .2s ease,background .2s ease}
  .form-kart input:focus,.form-kart textarea:focus{outline:none;border-color:var(--ana);background:#fff}
  .form-kart .not{font-size:12.5px;color:var(--soluk)}
  .basari{background:#e9f7ef;color:#14532d;border-radius:14px;padding:14px 16px;font-weight:700}
  .hata{color:#b91c1c;font-size:13.5px;margin-top:4px}
  .harita{width:100%;height:clamp(300px,42vw,460px);border:0;border-radius:var(--yuvarlak);display:block;filter:saturate(.9)}

  /* Alt bilgi */
  .altbilgi{background:var(--ink);color:rgba(255,255,255,.7);padding:84px 0 calc(28px + env(safe-area-inset-bottom,0px));font-size:15px}
  .alt-izgara{display:grid;gap:40px}
  @media(min-width:900px){.alt-izgara{grid-template-columns:1.5fr 1fr 1.2fr}}
  .altbilgi h4{color:#fff;font-size:18px;margin-bottom:18px}
  .altbilgi a{text-decoration:none}
  .altbilgi a:hover{color:#fff}
  .altbilgi ul{list-style:none;margin:0;padding:0;display:grid;gap:10px}
  .altbilgi .marka{color:#fff;margin-bottom:18px}
  .alt-alt{margin-top:56px;padding-top:24px;border-top:1px solid rgba(255,255,255,.1);display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;font-size:13.5px}

  /* Mobil sabit çubuk */
  .mobil-cubuk{position:fixed;left:0;right:0;bottom:0;z-index:40;display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:10px max(14px,env(safe-area-inset-left,0px)) calc(10px + env(safe-area-inset-bottom,0px));background:rgba(255,255,255,.97);backdrop-filter:blur(10px);box-shadow:0 -10px 30px -18px rgba(0,0,0,.35)}
  .mobil-cubuk .btn{padding:13px 10px;font-size:15px}
  .mobil-cubuk.tek{grid-template-columns:1fr}
  @media(min-width:900px){.mobil-cubuk{display:none}}
  @media(max-width:899px){body{padding-bottom:78px}}
  .onizleme{position:fixed;left:50%;transform:translateX(-50%);bottom:96px;z-index:70;background:#fde047;color:#422006;font-weight:800;font-size:13px;border-radius:999px;padding:9px 18px;box-shadow:0 10px 30px -10px rgba(0,0,0,.4);max-width:calc(100% - 32px);text-align:center}
  @media(min-width:900px){.onizleme{bottom:24px}}

  /* Belirme */
  .js .gorun{opacity:0;transform:translateY(28px);transition:opacity .9s cubic-bezier(.2,.7,.2,1),transform .9s cubic-bezier(.2,.7,.2,1)}
  .js .gorun.acik{opacity:1;transform:none}
  @media(prefers-reduced-motion:reduce){.js .gorun{opacity:1;transform:none;transition:none}.giris-foto{animation:none;transform:none}html{scroll-behavior:auto}}
  :focus-visible{outline:3px solid var(--ana);outline-offset:3px}
</style>
<script>document.documentElement.classList.add('js')</script>
</head>
<body>

@php
  $ikonTel = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.8 2z"/></svg>';
  $ikonWa = '<svg class="ikon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2l-.5-.3z"/></svg>';
  $ikonKonum = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg>';
  $ikonYatak = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v12M21 19v-6a3 3 0 0 0-3-3H3M7 10V8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>';
  $ikonTik = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  $ikonOk = '<span aria-hidden="true">→</span>';
@endphp

<header class="ust" id="ust">
  <div class="kap ic">
    <a href="{{ $link() }}" class="marka" aria-label="{{ $ad }} ana sayfa">
      @if($logo)
        <img src="{{ $logo }}" alt="{{ $ad }}">
      @else
        <span class="monogram" aria-hidden="true">{{ $harf }}</span>
        <span class="marka-ad"><b>{{ $ad }}</b>@if($f->category)<small>{{ $f->category->name }}</small>@endif</span>
      @endif
    </a>
    <nav class="menu" aria-label="Ana menü">
      <a href="{{ $link() }}" class="{{ $sayfa === '' ? 'aktif' : '' }}">Ana Sayfa</a>
      @foreach($sayfalar as $k => $etiket)
        <a href="{{ $link($k) }}" class="{{ $sayfa === $k ? 'aktif' : '' }}">{{ $etiket }}</a>
      @endforeach
    </nav>
    <a href="{{ $link('iletisim') }}" class="btn btn-dolu ust-cta">Bilgi Alın</a>
    <button type="button" class="hamburger" id="menuAc" aria-label="Menüyü aç" aria-controls="cekmece" aria-expanded="false">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
    </button>
  </div>
</header>

<div class="cekmece" id="cekmece" aria-hidden="true">
  <button type="button" class="kapat" id="menuKapat" aria-label="Menüyü kapat">✕</button>
  <nav aria-label="Mobil menü">
    <a href="{{ $link() }}">Ana Sayfa</a>
    @foreach($sayfalar as $k => $etiket)<a href="{{ $link($k) }}">{{ $sayfalar[$k] }}</a>@endforeach
  </nav>
  <div class="alt-btn">
    @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" data-olay="whatsapp" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp'tan Yazın</a>@endif
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-beyaz" data-olay="arama">{!! $ikonTel !!} {{ $telefon }}</a>@endif
  </div>
</div>

{{-- Giriş --}}
<section class="giris {{ $sayfa !== '' ? 'ic-sayfa' : '' }} {{ $hero ? '' : 'desensiz' }}">
  @if($hero)<div class="giris-foto" style="background-image:url('{{ $foto($hero) }}');" role="img" aria-label="{{ $temsili ? 'Temsili görsel' : $ad }}"></div>@endif
  @if($hero && $temsili)<span class="temsili">Temsili görseldir</span>@endif
  <div class="kap">
    @if($sayfa === '')
      <div class="etiket gorun">{{ $f->category->name ?? 'Hoş geldiniz' }}@if($f->city) · {{ $f->city->name }}@endif</div>
      <h1 class="gorun">{{ $baslik }}</h1>
      @if($altBaslik)<p class="alt gorun">{{ $altBaslik }}</p>@endif
      <div class="btnler gorun">
        @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" data-olay="whatsapp" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp'tan Yazın</a>@endif
        @if($telLink)<a href="{{ $telLink }}" class="btn btn-cam" data-olay="arama">{!! $ikonTel !!} {{ $telefon }}</a>@endif
        @if(! $waLink && ! $telLink)<a href="{{ $link('iletisim') }}" class="btn btn-beyaz">Bilgi Alın</a>@endif
      </div>
      <div class="guven gorun">
        @if($puan >= 4)<span><span class="yildizlar" aria-hidden="true">★★★★★</span> Google'da {{ number_format($puan, 1, ',', '') }} puan</span>@endif
        @if($bosYer)<span>{!! $ikonYatak !!} {{ $bosYer }}</span>@endif
        @if($f->district || $f->city)<span>{!! $ikonKonum !!} {{ trim(($f->district ? $f->district.', ' : '').($f->city->name ?? ''), ', ') }}</span>@endif
      </div>
    @else
      <div class="yol gorun"><a href="{{ $link() }}">Ana Sayfa</a> &nbsp;/&nbsp; {{ $sayfaAdi }}</div>
      <h1 class="gorun">{{ $sayfaBasligi }}</h1>
    @endif
  </div>
</section>

@if($sayfa === '')
  {{-- Rakamlar ya da temel bilgiler: girişin altına binen kart --}}
  <div class="kap">
    @if($rakamlar)
      <div class="bilgi-kart rakamlar gorun" style="--sutun:{{ count($rakamlar) }};">
        @foreach($rakamlar as $r)<div><span class="rakam">{{ $r['deger'] }}</span><span class="rakam-et">{{ $r['etiket'] }}</span></div>@endforeach
      </div>
    @else
      @php $bilgiler = array_values(array_filter([
          $telefon ? ['ikon' => $ikonTel, 'b' => 'Telefon', 's' => $telefon, 'href' => $telLink, 'olay' => 'arama'] : null,
          $adres ? ['ikon' => $ikonKonum, 'b' => 'Adres', 's' => $adres, 'href' => $haritaLink, 'olay' => null] : null,
          $bosYer ? ['ikon' => $ikonYatak, 'b' => 'Kontenjan', 's' => $bosYer, 'href' => null, 'olay' => null] : null,
      ])); @endphp
      @if($bilgiler)
        <div class="bilgi-kart gorun" style="--sutun:{{ count($bilgiler) }};">
          @foreach($bilgiler as $bi)
            <div><span class="yuvar">{!! $bi['ikon'] !!}</span><span><b>{{ $bi['b'] }}</b><small>@if($bi['href'])<a href="{{ $bi['href'] }}" style="text-decoration:none;" @if($bi['olay']) data-olay="{{ $bi['olay'] }}" @else target="_blank" rel="noopener" @endif>{{ $bi['s'] }}</a>@else{{ $bi['s'] }}@endif</small></span></div>
          @endforeach
        </div>
      @endif
    @endif
  </div>

  {{-- Hakkımızda --}}
  @if(isset($sayfalar['hakkimizda']))
    <section class="bolum">
      <div class="kap ikili">
        @if($ikinci || $hero)
          <div class="kolaj gorun">
            <img class="b1" src="{{ $foto($ikinci ?? $hero) }}" alt="{{ $temsili ? 'Temsili görsel' : $ad }}" loading="lazy">
            @if($ucuncu)<img class="b2" src="{{ $foto($ucuncu) }}" alt="" loading="lazy">@endif
            @if($rakamlar)<div class="rozet-yil"><b>{{ $rakamlar[0]['deger'] }}</b><span>{{ $rakamlar[0]['etiket'] }}</span></div>@endif
            @if($temsili)<span class="temsili">Temsili görseldir</span>@endif
          </div>
        @endif
        <div class="gorun">
          <div class="etiket">Hakkımızda</div>
          <h2>{{ $ad }}</h2>
          <p class="giris-metni" style="margin-top:22px;">{{ $kisaHakkimizda }}</p>
          <a href="{{ $link('hakkimizda') }}" class="ok-link">Hikâyemizin devamı {!! $ikonOk !!}</a>
        </div>
      </div>
    </section>
  @endif

  {{-- Neden biz --}}
  @if($degerler)
    <section class="bolum krem">
      <div class="kap">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Neden biz?</div><h2>Bizi farklı kılan değerler</h2></div></div>
        <div class="izgara uc">
          @foreach($degerler as $d)
            <div class="deger gorun"><span class="no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $d['baslik'] }}</h3>@if($d['metin'])<p>{{ $d['metin'] }}</p>@endif</div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Hizmetler --}}
  @if(isset($sayfalar['hizmetler']))
    <section class="bolum {{ $degerler ? '' : 'krem' }}">
      <div class="kap">
        <div class="bolum-bas gorun">
          <div class="sol"><div class="etiket">Hizmetlerimiz</div><h2>Size neler sunuyoruz?</h2></div>
          @if($hizmetler->count() > 8)<a href="{{ $link('hizmetler') }}" class="ok-link">Tüm hizmetler {!! $ikonOk !!}</a>@endif
        </div>
        <div class="izgara uc">
          @foreach($hizmetler->take(9) as $h)<div class="hizmet gorun"><span class="tik">{!! $ikonTik !!}</span>{{ $h }}</div>@endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Video --}}
  @if($video || $kendiVideo)
    <section class="bolum koyu-bolum">
      <div class="kap">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Tanıtım filmi</div><h2>Kurumumuzu yakından görün</h2></div></div>
        <div class="video-kutu gorun">
          @if($video)
            <iframe src="{{ $video }}" title="{{ $ad }} tanıtım videosu" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
          @else
            <video src="{{ $kendiVideo }}" controls preload="metadata" playsinline @if($hero) poster="{{ $foto($hero) }}" @endif></video>
          @endif
        </div>
      </div>
    </section>
  @endif

  {{-- Galeri --}}
  @if($galeriAcik)
    <section class="bolum">
      <div class="kap">
        <div class="bolum-bas gorun">
          <div class="sol"><div class="etiket">Galeri</div><h2>{{ $temsili ? 'Görseller' : 'Kurumumuzdan kareler' }}</h2>@if($temsili)<p class="giris-metni" style="margin-top:12px;font-size:15px;">Görseller temsilidir; kurumun kendi fotoğraflarını göstermez.</p>@endif</div>
          @if($gallery->count() > 5)<a href="{{ $link('galeri') }}" class="ok-link">Tüm fotoğraflar {!! $ikonOk !!}</a>@endif
        </div>
        <div class="galeri-izgara" data-galeri>
          @foreach($gallery->take(5) as $g)
            <a href="{{ $foto($g) }}" class="galeri-oge gorun {{ $loop->first ? 'buyuk' : '' }}" data-isik><img src="{{ $foto($g) }}" alt="{{ $temsili ? 'Temsili görsel' : $ad.' fotoğraf '.$loop->iteration }}" loading="lazy"></a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Yorumlar --}}
  @if($yorumlar->isNotEmpty())
    <section class="bolum krem">
      <div class="kap">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Aileler ne diyor?</div><h2>Bize güvenen ailelerden</h2></div></div>
        <div class="yorum-serit gorun">
          @foreach($yorumlar as $r)
            <figure class="yorum" style="margin:0;">
              <div class="tirnak" aria-hidden="true">“</div>
              <span class="yildizlar" aria-label="{{ $r['puan'] }} yıldız">{{ str_repeat('★', max(1, min(5, $r['puan']))) }}</span>
              <p>{{ \Illuminate\Support\Str::limit($r['metin'], 300) }}</p>
              <figcaption class="kim"><i>{{ mb_substr((string) $r['ad'], 0, 1, 'UTF-8') }}</i>{{ $r['ad'] }}</figcaption>
            </figure>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Ekibimiz --}}
  @if($ekip)
    <section class="bolum">
      <div class="kap">
        <div class="bolum-bas gorun">
          <div class="sol"><div class="etiket">Ekibimiz</div><h2>Sevdiklerinize emanet ettiğiniz ekip</h2></div>
          @if(count($ekip) > 4)<a href="{{ $link('ekibimiz') }}" class="ok-link">Tüm ekip {!! $ikonOk !!}</a>@endif
        </div>
        <div class="izgara ekip">
          @foreach(array_slice($ekip, 0, 4) as $u)
            <div class="uye gorun"><div class="resim">@if(! empty($u['foto']))<img src="{{ facility_asset($u['foto']) }}" alt="{{ $u['ad'] }}" loading="lazy">@else<span>{{ mb_substr($u['ad'], 0, 1, 'UTF-8') }}</span>@endif</div><h3>{{ $u['ad'] }}</h3>@if(! empty($u['unvan']))<div class="unvan">{{ $u['unvan'] }}</div>@endif</div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Duyurular --}}
  @if($duyurular)
    <section class="bolum krem">
      <div class="kap">
        <div class="bolum-bas gorun">
          <div class="sol"><div class="etiket">Duyurular</div><h2>Kurumumuzdan haberler</h2></div>
          @if(count($duyurular) > 3)<a href="{{ $link('duyurular') }}" class="ok-link">Tüm duyurular {!! $ikonOk !!}</a>@endif
        </div>
        <div class="izgara uc">
          @foreach(array_slice($duyurular, 0, 3) as $d)
            <article class="duyuru gorun">@if(! empty($d['tarih']))<time datetime="{{ $d['tarih'] }}">{{ \Illuminate\Support\Carbon::parse($d['tarih'])->locale('tr')->translatedFormat('j F Y') }}</time>@endif<h3>{{ $d['baslik'] }}</h3>@if(! empty($d['metin']))<p>{{ \Illuminate\Support\Str::limit($d['metin'], 220) }}</p>@endif</article>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- SSS --}}
  @if($sss)
    <section class="bolum">
      <div class="kap ikili" style="align-items:start;">
        <div class="gorun">
          <div class="etiket">Sık sorulan sorular</div>
          <h2>Merak ettikleriniz</h2>
          <p class="giris-metni" style="margin-top:18px;">Aklınıza takılan başka bir soru varsa bize doğrudan ulaşabilirsiniz.</p>
          <div style="margin-top:28px;"><a href="{{ $link('iletisim') }}" class="btn btn-dolu">Soru sorun</a></div>
        </div>
        <div class="sss gorun">
          @foreach(array_slice($sss, 0, 5) as $s)
            <details @if($loop->first) open @endif><summary>{{ $s['soru'] }}<span class="arti" aria-hidden="true">+</span></summary><div class="cevap">{{ $s['cevap'] }}</div></details>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Çağrı + iletişim --}}
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false])

@elseif($sayfa === 'hakkimizda')
  <section class="bolum">
    <div class="kap ikili" style="align-items:start;">
      <div class="gorun">
        <div class="etiket">{{ $ad }}</div>
        <h2>{{ $altBaslik ?: 'Hikâyemiz' }}</h2>
        <div class="yazi-blok" style="margin-top:24px;">{{ $hakkimizda }}</div>
      </div>
      @if($ikinci || $hero)
        <div class="kolaj gorun" style="margin-left:auto;">
          <img class="b1" src="{{ $foto($ikinci ?? $hero) }}" alt="{{ $temsili ? 'Temsili görsel' : $ad }}" loading="lazy">
          @if($ucuncu)<img class="b2" src="{{ $foto($ucuncu) }}" alt="" loading="lazy">@endif
          @if($temsili)<span class="temsili">Temsili görseldir</span>@endif
        </div>
      @endif
    </div>
  </section>
  @if($rakamlar)
    <div class="kap" style="margin-bottom:24px;"><div class="bilgi-kart rakamlar gorun" style="--sutun:{{ count($rakamlar) }};margin-top:0;">@foreach($rakamlar as $r)<div><span class="rakam">{{ $r['deger'] }}</span><span class="rakam-et">{{ $r['etiket'] }}</span></div>@endforeach</div></div>
  @endif
  @if($degerler)
    <section class="bolum krem">
      <div class="kap">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Değerlerimiz</div><h2>Bizi farklı kılan değerler</h2></div></div>
        <div class="izgara uc">@foreach($degerler as $d)<div class="deger gorun"><span class="no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $d['baslik'] }}</h3>@if($d['metin'])<p>{{ $d['metin'] }}</p>@endif</div>@endforeach</div>
      </div>
    </section>
  @endif
  @if($video || $kendiVideo)
    <section class="bolum koyu-bolum">
      <div class="kap">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Tanıtım filmi</div><h2>Kurumumuzu yakından görün</h2></div></div>
        <div class="video-kutu gorun">@if($video)<iframe src="{{ $video }}" title="{{ $ad }} tanıtım videosu" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>@else<video src="{{ $kendiVideo }}" controls preload="metadata" playsinline></video>@endif</div>
      </div>
    </section>
  @endif
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'hizmetler')
  <section class="bolum">
    <div class="kap">
      <div class="bolum-bas gorun"><div class="sol"><div class="etiket">{{ $f->category->name ?? 'Hizmetlerimiz' }}</div><h2>Size sunduğumuz hizmetler</h2><p class="giris-metni" style="margin-top:16px;">Sevdiklerinizin ihtiyaçlarına göre sunduğumuz hizmetlerin tamamı.</p></div></div>
      <div class="izgara uc">@foreach($hizmetler as $h)<div class="hizmet gorun"><span class="tik">{!! $ikonTik !!}</span>{{ $h }}</div>@endforeach</div>
      @if($bosYer)<div class="gorun" style="margin-top:36px;display:inline-flex;align-items:center;gap:12px;background:var(--acik);color:var(--koyu);border-radius:18px;padding:18px 24px;font-weight:800;">{!! $ikonYatak !!} {{ $bosYer }}</div>@endif
    </div>
  </section>
  @if($site->sectionEnabled('menu') && $f->menu_image_path)
    <section class="bolum krem">
      <div class="kap" style="max-width:900px;">
        <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Beslenme</div><h2>Yemek menümüz</h2></div></div>
        <a href="{{ facility_asset($f->menu_image_path) }}" target="_blank" rel="noopener" class="gorun"><img src="{{ facility_asset($f->menu_image_path) }}" alt="{{ $ad }} yemek menüsü" loading="lazy" style="border-radius:var(--yuvarlak);border:1px solid var(--cizgi);box-shadow:var(--golge);"></a>
      </div>
    </section>
  @endif
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'galeri')
  <section class="bolum">
    <div class="kap">
      @if($temsili)<p class="giris-metni gorun" style="margin-bottom:28px;">Görseller temsilidir; kurumun kendi fotoğraflarını göstermez.</p>@endif
      <div class="masonry" data-galeri>
        @foreach($gallery as $g)
          <a href="{{ $foto($g) }}" class="galeri-oge gorun" data-isik><img src="{{ $foto($g) }}" alt="{{ $temsili ? 'Temsili görsel' : $ad.' fotoğraf '.$loop->iteration }}" loading="lazy"></a>
        @endforeach
      </div>
    </div>
  </section>
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'ekibimiz')
  <section class="bolum">
    <div class="kap">
      <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Ekibimiz</div><h2>Sevdiklerinize emanet ettiğiniz ekip</h2></div></div>
      <div class="izgara ekip">
        @foreach($ekip as $u)
          <div class="uye gorun"><div class="resim">@if(! empty($u['foto']))<img src="{{ facility_asset($u['foto']) }}" alt="{{ $u['ad'] }}" loading="lazy">@else<span>{{ mb_substr($u['ad'], 0, 1, 'UTF-8') }}</span>@endif</div><h3>{{ $u['ad'] }}</h3>@if(! empty($u['unvan']))<div class="unvan">{{ $u['unvan'] }}</div>@endif @if(! empty($u['aciklama']))<p class="ac">{{ $u['aciklama'] }}</p>@endif</div>
        @endforeach
      </div>
    </div>
  </section>
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'duyurular')
  <section class="bolum">
    <div class="kap" style="max-width:920px;">
      <div style="display:grid;gap:20px;">
        @foreach($duyurular as $d)
          <article class="duyuru gorun">@if(! empty($d['tarih']))<time datetime="{{ $d['tarih'] }}">{{ \Illuminate\Support\Carbon::parse($d['tarih'])->locale('tr')->translatedFormat('j F Y') }}</time>@endif<h3 style="font-size:clamp(22px,2.4vw,28px);">{{ $d['baslik'] }}</h3>@if(! empty($d['metin']))<p>{{ $d['metin'] }}</p>@endif</article>
        @endforeach
      </div>
    </div>
  </section>
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'sss')
  <section class="bolum">
    <div class="kap" style="max-width:920px;">
      <div class="sss gorun">
        @foreach($sss as $s)
          <details @if($loop->first) open @endif><summary>{{ $s['soru'] }}<span class="arti" aria-hidden="true">+</span></summary><div class="cevap">{{ $s['cevap'] }}</div></details>
        @endforeach
      </div>
    </div>
  </section>
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => false, 'sadeceCagri' => true])

@elseif($sayfa === 'iletisim')
  @include('themes._shared.kurum-sitesi.pro._iletisim', ['tamSayfa' => true])
@endif

<footer class="altbilgi">
  <div class="kap">
    <div class="alt-izgara">
      <div>
        <a href="{{ $link() }}" class="marka">
          @if($logo)<img src="{{ $logo }}" alt="{{ $ad }}" style="max-height:56px;background:#fff;border-radius:12px;padding:6px 10px;">@else<span class="monogram" aria-hidden="true">{{ $harf }}</span><span class="marka-ad"><b>{{ $ad }}</b>@if($f->category)<small>{{ $f->category->name }}</small>@endif</span>@endif
        </a>
        @if($kisaHakkimizda)<p style="max-width:420px;">{{ \Illuminate\Support\Str::limit($kisaHakkimizda, 180, '…', preserveWords: true) }}</p>@endif
      </div>
      <div>
        <h4>Sayfalar</h4>
        <ul>
          <li><a href="{{ $link() }}">Ana Sayfa</a></li>
          @foreach($sayfalar as $k => $etiket)<li><a href="{{ $link($k) }}">{{ $etiket }}</a></li>@endforeach
        </ul>
      </div>
      <div>
        <h4>İletişim</h4>
        <ul>
          @if($telLink)<li><a href="{{ $telLink }}" data-olay="arama">{{ $telefon }}</a></li>@endif
          @if($waLink)<li><a href="{{ $waLink }}" data-olay="whatsapp" target="_blank" rel="noopener">WhatsApp'tan yazın</a></li>@endif
          @if($adres)<li>{{ $adres }}</li>@endif
          @if($haritaLink)<li><a href="{{ $haritaLink }}" target="_blank" rel="noopener" style="color:#e9d3ab;font-weight:700;">Yol tarifi al →</a></li>@endif
        </ul>
      </div>
    </div>
    <div class="alt-alt">
      <span>© {{ now()->year }} {{ $ad }}. Tüm hakları saklıdır.</span>
      <span>Altyapı: <a href="https://bakimevleri.com" target="_blank" rel="noopener" style="color:#fff;">bakimevleri.com</a></span>
    </div>
  </div>
</footer>

@if($waLink || $telLink)
  <div class="mobil-cubuk {{ $waLink && $telLink ? '' : 'tek' }}">
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-cizgi" data-olay="arama">{!! $ikonTel !!} Ara</a>@endif
    @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" data-olay="whatsapp" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp</a>@endif
  </div>
@endif

@if($demoPro)
  <div class="onizleme">Profesyonel Site tasarım önizlemesi — paket etkinleşince siteniz bu görünümde yayınlanır.</div>
@elseif($isPreview)
  <div class="onizleme">Önizleme — bu site henüz yayında değil. Yalnızca siz görüyorsunuz.</div>
@endif

<div class="isik" id="isik" role="dialog" aria-modal="true" aria-label="Fotoğraf">
  <button type="button" class="kapat" aria-label="Kapat">✕</button>
  <button type="button" class="onceki" aria-label="Önceki">‹</button>
  <img alt="">
  <button type="button" class="sonraki" aria-label="Sonraki">›</button>
</div>

<script>
(function () {
  var ust = document.getElementById('ust');
  function kaydir() { ust.classList.toggle('dolu', window.scrollY > 40); }
  kaydir(); window.addEventListener('scroll', kaydir, { passive: true });

  var cekmece = document.getElementById('cekmece'), ac = document.getElementById('menuAc'), kapat = document.getElementById('menuKapat');
  function menu(acik) { cekmece.classList.toggle('acik', acik); cekmece.setAttribute('aria-hidden', acik ? 'false' : 'true'); ac.setAttribute('aria-expanded', acik ? 'true' : 'false'); document.body.style.overflow = acik ? 'hidden' : ''; }
  ac.addEventListener('click', function () { menu(true); });
  kapat.addEventListener('click', function () { menu(false); });
  cekmece.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { menu(false); }); });

  var ogeler = document.querySelectorAll('.gorun');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (girdiler) {
      girdiler.forEach(function (g) { if (g.isIntersecting) { g.target.classList.add('acik'); io.unobserve(g.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    ogeler.forEach(function (o) { io.observe(o); });
  } else { ogeler.forEach(function (o) { o.classList.add('acik'); }); }

  var isik = document.getElementById('isik'), resim = isik.querySelector('img'), liste = [], sira = 0;
  function goster(i) { sira = (i + liste.length) % liste.length; resim.src = liste[sira].href; resim.alt = liste[sira].querySelector('img').alt; }
  document.querySelectorAll('[data-galeri]').forEach(function (g) {
    var bag = Array.prototype.slice.call(g.querySelectorAll('[data-isik]'));
    bag.forEach(function (a, i) { a.addEventListener('click', function (e) { e.preventDefault(); liste = bag; goster(i); isik.classList.add('acik'); document.body.style.overflow = 'hidden'; }); });
  });
  function isikKapat() { isik.classList.remove('acik'); document.body.style.overflow = ''; }
  isik.querySelector('.kapat').addEventListener('click', isikKapat);
  isik.querySelector('.onceki').addEventListener('click', function () { goster(sira - 1); });
  isik.querySelector('.sonraki').addEventListener('click', function () { goster(sira + 1); });
  isik.addEventListener('click', function (e) { if (e.target === isik) isikKapat(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { isikKapat(); menu(false); }
    if (isik.classList.contains('acik') && e.key === 'ArrowLeft') goster(sira - 1);
    if (isik.classList.contains('acik') && e.key === 'ArrowRight') goster(sira + 1);
  });

  var token = document.querySelector('meta[name=csrf-token]').content;
  document.querySelectorAll('[data-olay]').forEach(function (a) {
    a.addEventListener('click', function () {
      try { var v = new FormData(); v.append('_token', token); v.append('tur', a.getAttribute('data-olay')); navigator.sendBeacon(@json($olayAdresi), v); } catch (e) {}
    });
  });
})();
</script>
</body>
</html>
