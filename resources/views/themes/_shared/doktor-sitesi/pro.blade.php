{{-- 25 Ekim 2026: kullanıcının talebi - doktorun "Profesyonel Site" tasarımı.
     Kurum profesyonel şablonuyla aynı görsel dil (stiller oradan kopyalanır),
     tek sayfa. Hukuki nedenle hasta yorumu ve puan GÖSTERİLMEZ. --}}
@php
  $e = $expert;
  $ana = $renk['ana']; $koyu = $renk['koyu']; $acik = $renk['acik'];
  $zarif = ($site->font_style ?: 'zarif') !== 'modern';
  $ad = $site->fullName();
  $baslik = $site->heroTitle();
  $altBaslik = $site->heroSubtitle();
  $hakkimda = trim($site->aboutText());
  $ilgi = $site->ilgiAlanlariListesi();
  $saatler = trim((string) $site->calisma_saatleri);
  $brans = $e->branch_label ?: $e->branch;
  $telefon = $e->phone;
  $telLink = $telefon ? 'tel:'.preg_replace('/[^0-9+]/', '', $telefon) : null;
  $waLink = $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Merhaba, randevu almak istiyorum.') : null;
  $adres = trim(($e->address ?: '').($e->district && ! str_contains((string) $e->address, (string) $e->district) ? ', '.$e->district : '').($e->city ? ' / '.$e->city->name : ''), ' ,/');
  $haritaLink = $e->google_maps_url ?: ($e->lat && $e->lng ? 'https://www.google.com/maps?q='.$e->lat.','.$e->lng : null);
  $foto = $site->photo_path ? facility_asset($site->photo_path) : null;
  $logo = $site->logo_path ? facility_asset($site->logo_path) : null;
  $harf = mb_strtoupper(mb_substr((string) $e->name, 0, 1, 'UTF-8'), 'UTF-8');
  $rakamlar = $site->listOf('stats');
  $sss = $site->listOf('faqs');
  // 25 Ekim 2026: branşa göre otomatik bilgilendirme içerikleri (bkz. App\Support\DoktorIcerik).
  $icerikTur = \App\Support\DoktorIcerik::tur($e->branch, $e->branch_label);
  $durumlar = \App\Support\DoktorIcerik::durumlar($icerikTur);
  $hazirlik = \App\Support\DoktorIcerik::hazirlik($icerikTur);
  $surec = \App\Support\DoktorIcerik::surec((bool) $whatsapp);
  $sssVarsayilan = ! $sss;
  if ($sssVarsayilan) {
      $sss = \App\Support\DoktorIcerik::varsayilanSss((bool) $whatsapp);
  }
  $duyurular = $site->listOf('announcements');
  $video = $site->videoEmbedUrl();
  $metaAciklama = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $hakkimda ?: $altBaslik)), 155);
  $bolum = fn ($k) => $site->sectionEnabled($k);
  $menu = array_filter([
      'hakkimda' => $bolum('hakkimda') && $hakkimda !== '' ? 'Hakkımda' : null,
      'ilgi' => $bolum('ilgi_alanlari') && $ilgi ? 'Uzmanlık Alanları' : null,
      'surec' => 'Muayene Süreci',
      'duyurular' => $duyurular ? 'Duyurular' : null,
      'sss' => $sss ? 'S.S.S.' : null,
      'iletisim' => 'Randevu',
  ]);
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $site->seoBaslik() }}</title>
<meta name="description" content="{{ $site->seoAciklama() }}">
<meta name="robots" content="{{ $isPreview || $demoPro ? 'noindex,nofollow' : 'index,follow' }}">
<meta name="theme-color" content="{{ $koyu }}">
<link rel="canonical" href="{{ $site->publicUrl() }}">
<meta property="og:title" content="{{ $baslik }}">
<meta property="og:description" content="{{ $metaAciklama }}">
@if($foto)<meta property="og:image" content="{{ $foto }}">@endif
@if($logo)<link rel="icon" href="{{ $logo }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@if($zarif)
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap">
@else
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
@endif
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Physician', 'name' => $ad, 'url' => $site->publicUrl(),
    'areaServed' => trim(($e->district ? $e->district.', ' : '').($e->city->name ?? ''), ', ') ?: null,
    'medicalSpecialty' => $brans ?: null, 'telephone' => $telefon ?: null, 'image' => $foto, 'description' => $metaAciklama ?: null,
    'address' => $adres ? ['@type' => 'PostalAddress', 'streetAddress' => $e->address, 'addressLocality' => $e->district, 'addressRegion' => $e->city->name ?? null, 'addressCountry' => 'TR'] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@if($sss && ! $sssVarsayilan)
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
  /* 25 Ekim 2026: kullanıcının talebi - mobil alttaki "Ara" düğmesi beyazdı, fark edilmiyordu: yeşil. */
  .btn-ara{background:#16a34a;color:#fff;border:0;box-shadow:0 8px 20px -10px rgba(22,163,74,.9)}
  .btn-ara:hover{background:#15803d;color:#fff}
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
  /* Doktor şablonuna özel */
  .dr-giris .kap{display:grid;gap:36px;align-items:end}
  @media(min-width:960px){.dr-giris .kap{grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);align-items:center}}
  .portre-kart{position:relative;justify-self:center;width:min(100%,380px)}
  .portre-kart img,.portre-kart .bos{width:100%;aspect-ratio:4/5;object-fit:cover;border-radius:28px;box-shadow:0 40px 80px -30px rgba(0,0,0,.6);border:6px solid rgba(255,255,255,.18)}
  .portre-kart .bos{display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.1);font-family:var(--baslik);font-size:120px;color:#fff}
  .portre-kart .unvan-rozet{position:absolute;left:-14px;bottom:28px;background:#fff;color:var(--ink);border-radius:16px;padding:12px 16px;box-shadow:var(--golge);max-width:230px}
  .portre-kart .unvan-rozet b{display:block;font-family:var(--baslik);font-size:18px;line-height:1.2}
  .portre-kart .unvan-rozet span{font-size:13px;color:var(--soluk)}
  @media(max-width:959px){.dr-giris .portre-kart{order:-1;width:min(70%,280px)}.portre-kart .unvan-rozet{left:50%;transform:translateX(-50%);bottom:-18px;text-align:center;width:max-content;max-width:90%}}
  .etiket-bulut{display:flex;flex-wrap:wrap;gap:12px}
  .etiket-bulut span{display:inline-flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--cizgi);border-radius:999px;padding:12px 18px;font-weight:700;color:var(--ink);font-size:15.5px}
  .etiket-bulut span::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--ana)}
  /* 25 Ekim 2026: muayene süreci adımları + hazırlık listesi + bilgilendirme notu */
  .adimlar{display:grid;gap:18px;grid-template-columns:1fr;position:relative}
  @media(min-width:900px){.adimlar{grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}.adimlar::before{content:"";position:absolute;left:12%;right:12%;top:30px;height:2px;background:linear-gradient(90deg,var(--acik),var(--ana),var(--acik));opacity:.6}}
  .adim{position:relative;background:#fff;border:1px solid var(--cizgi);border-radius:var(--yuvarlak);padding:26px 24px;text-align:left}
  .adim-no{position:relative;z-index:1;width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--ana),var(--koyu));color:#fff;font-family:var(--baslik);font-size:24px;margin-bottom:16px;box-shadow:0 10px 24px -12px var(--ana)}
  .adim h3{font-size:20px}
  .adim p{color:var(--soluk);margin-top:8px;font-size:15.5px}
  .hazirlik{margin-top:44px;background:#fff;border:1px solid var(--cizgi);border-radius:var(--yuvarlak);padding:clamp(22px,3vw,36px)}
  .hazirlik h3{font-size:clamp(20px,2.2vw,26px)}
  .hazirlik .izgara{margin-top:18px}
  .hazirlik .hizmet{padding:16px 18px;font-size:15.5px}
  .tibbi-not{font-size:13.5px;color:var(--soluk);background:var(--krem);border:1px solid var(--cizgi);border-radius:14px;padding:14px 18px;margin:0}
  .saat-kart{background:#fff;border:1px solid var(--cizgi);border-radius:var(--yuvarlak);padding:28px 30px;white-space:pre-line;font-weight:600;color:var(--ink);box-shadow:var(--golge)}
  .ust.dolu .marka-ad b,.ust.dolu .marka-ad small{color:var(--ink)}
</style>
@if(! empty($hareketli))@include('themes._shared.kurum-sitesi.pro._hareketli', ['parca' => 'css'])@endif
<script>document.documentElement.classList.add('js')</script>
</head>
<body class="{{ ! empty($hareketli) ? 'hrk hrk-dr' : '' }}">
@if(! empty($hareketli))<div class="ilerleme" id="ilerleme" aria-hidden="true"></div>@endif
@php
  $ikonTel = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.8 2z"/></svg>';
  $ikonWa = '<svg class="ikon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2l-.5-.3z"/></svg>';
  $ikonKonum = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg>';
  $ikonSaat = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
@endphp

<header class="ust" id="ust">
  <div class="kap ic">
    <a href="#" class="marka" aria-label="{{ $ad }}">
      @if($logo)<img src="{{ $logo }}" alt="{{ $ad }}">@else<span class="monogram" aria-hidden="true">{{ $harf }}</span><span class="marka-ad"><b>{{ $ad }}</b>@if($brans)<small>{{ $brans }}</small>@endif</span>@endif
    </a>
    <nav class="menu" aria-label="Ana menü">
      @foreach($menu as $k => $etiket)<a href="#{{ $k }}">{{ $etiket }}</a>@endforeach
    </nav>
    <a href="#iletisim" class="btn btn-dolu ust-cta">Randevu Alın</a>
    <button type="button" class="hamburger" id="menuAc" aria-label="Menüyü aç" aria-controls="cekmece" aria-expanded="false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg></button>
  </div>
</header>
<div class="cekmece" id="cekmece" aria-hidden="true">
  <button type="button" class="kapat" id="menuKapat" aria-label="Menüyü kapat">✕</button>
  <nav aria-label="Mobil menü">@foreach($menu as $k => $etiket)<a href="#{{ $k }}">{{ $etiket }}</a>@endforeach</nav>
  <div class="alt-btn">
    @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp'tan Yazın</a>@endif
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-beyaz">{!! $ikonTel !!} {{ $telefon }}</a>@endif
  </div>
</div>

<section class="giris desensiz dr-giris">
  @if(! empty($hareketli))
    <div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="tibbi" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:8%;top:18%;--b:54px;--o:.14;--s:17s;--g:0s;"><path d="M6 3v6a5 5 0 0 0 10 0V3"/><path d="M11 14v2a5 5 0 0 0 10 0v-1"/><circle cx="21" cy="13" r="2"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:44%;top:12%;--b:40px;--o:.12;--s:21s;--g:-5s;"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/><path d="M3.5 12h4l2-3 3 6 2-3h6"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:88%;top:22%;--b:34px;--o:.16;--s:15s;--g:-3s;"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:70%;top:70%;--b:44px;--o:.1;--s:19s;--g:-8s;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:20%;top:76%;--b:28px;--o:.14;--s:13s;--g:-6s;"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:92%;top:62%;--b:30px;--o:.1;--s:18s;--g:-11s;"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/><path d="M3.5 12h4l2-3 3 6 2-3h6"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="left:56%;top:44%;--b:36px;--o:.08;--s:23s;--g:-2s;"><path d="M6 3v6a5 5 0 0 0 10 0V3"/><path d="M11 14v2a5 5 0 0 0 10 0v-1"/><circle cx="21" cy="13" r="2"/></svg></div>
    <svg class="ekg" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path class="zemin" d="M0 60 H430 l16 -4 l10 8 l14 -46 l16 74 l12 -44 l10 12 H860 l12 -3 l9 6 l12 -30 l14 48 l10 -28 l9 7 H1440"/><path class="isik" pathLength="1" d="M0 60 H430 l16 -4 l10 8 l14 -46 l16 74 l12 -44 l10 12 H860 l12 -3 l9 6 l12 -30 l14 48 l10 -28 l9 7 H1440"/></svg>
  @endif
  <div class="kap">
    <div>
      <div class="etiket gorun">{{ $brans ?: 'Muayenehane' }}@if($e->district || $e->city) · {{ trim(($e->district ? $e->district.' / ' : '').($e->city->name ?? ''), ' /') }}@endif</div>
      <h1 class="gorun">{{ $baslik }}</h1>
      @if($altBaslik)<p class="alt gorun">{{ $altBaslik }}</p>@endif
      <div class="btnler gorun">
        @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp'tan Randevu</a>@endif
        @if($telLink)<a href="{{ $telLink }}" class="btn btn-cam">{!! $ikonTel !!} {{ $telefon }}</a>@endif
      </div>
      <div class="guven gorun">
        @if($e->clinic_name)<span>🏥 {{ $e->clinic_name }}</span>@endif
        @if($e->district || $e->city)<span>{!! $ikonKonum !!} {{ trim(($e->district ? $e->district.', ' : '').($e->city->name ?? ''), ', ') }}</span>@endif
      </div>
    </div>
    <div class="portre-kart gorun">
      @if($foto)<img src="{{ $foto }}" alt="{{ $ad }}">@else<div class="bos" aria-hidden="true">{{ $harf }}</div>@endif
      <div class="unvan-rozet"><b>{{ $ad }}</b>@if($brans)<span>{{ $brans }}</span>@endif</div>
    </div>
  </div>
</section>

@if($rakamlar)
  <div class="kap"><div class="bilgi-kart rakamlar gorun" style="--sutun:{{ count($rakamlar) }};">@foreach($rakamlar as $r)<div><span class="rakam">{{ $r['deger'] }}</span><span class="rakam-et">{{ $r['etiket'] }}</span></div>@endforeach</div></div>
@endif

@if(! empty($hareketli) && isset($menu['ilgi']) && count($ilgi) >= 3)
  {{-- 25 Ekim 2026: hareketli tasarımda uzmanlık alanları kayan şerit. --}}
  <div class="ilgi-serit" aria-hidden="true">
    <div class="serit"><div class="serit-ic" style="--sure:{{ max(28, count($ilgi) * 6) }}s;">@foreach([0, 1] as $kopya)@foreach($ilgi as $alan)<span>{{ $alan }}</span>@endforeach @endforeach</div></div>
  </div>
@endif

@if(isset($menu['hakkimda']))
  <section class="bolum" id="hakkimda">
    <div class="kap ikili" style="align-items:start;">
      <div class="gorun">
        <div class="etiket">Hakkımda</div>
        <h2>{{ $ad }}</h2>
        <div class="yazi-blok" style="margin-top:22px;">{{ $hakkimda }}</div>
      </div>
      @if($bolum('calisma_saatleri') && $saatler !== '')
        <div class="gorun">
          <div class="etiket">Çalışma saatleri</div>
          <h2 style="font-size:clamp(26px,3vw,36px);">Muayene saatleri</h2>
          <div class="saat-kart" style="margin-top:20px;">{{ $saatler }}</div>
        </div>
      @endif
    </div>
  </section>
@elseif($bolum('calisma_saatleri') && $saatler !== '')
  <section class="bolum"><div class="kap" style="max-width:760px;"><div class="etiket">Çalışma saatleri</div><h2>Muayene saatleri</h2><div class="saat-kart" style="margin-top:20px;">{{ $saatler }}</div></div></section>
@endif

@if(isset($menu['ilgi']))
  <section class="bolum krem" id="ilgi">
    <div class="kap">
      <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Uzmanlık alanları</div><h2>Hangi konularda yardımcı oluyorum?</h2></div></div>
      <div class="etiket-bulut gorun">@foreach($ilgi as $alan)<span style="--n:{{ $loop->index }};">{{ $alan }}</span>@endforeach</div>
    </div>
  </section>
@endif

{{-- 25 Ekim 2026: branşa göre bilgilendirme - başvuru durumları --}}
<section class="bolum" id="durumlar">
  <div class="kap">
    <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Bilgilendirme</div><h2>Hangi durumlarda başvurabilirsiniz?</h2><p class="giris-metni" style="margin-top:14px;">{{ $brans ? $brans.' alanında' : 'Muayenehanede' }} sık karşılaşılan başvuru nedenlerinden bazıları.</p></div></div>
    <div class="izgara uc">
      @foreach($durumlar as [$durumBaslik, $durumMetin])
        <div class="deger gorun"><span class="no">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $durumBaslik }}</h3><p>{{ $durumMetin }}</p></div>
      @endforeach
    </div>
  </div>
</section>

{{-- 25 Ekim 2026: muayene süreci + muayeneye hazırlık listesi --}}
<section class="bolum krem" id="surec">
  <div class="kap">
    <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Muayene süreci</div><h2>Randevudan takibe adım adım</h2></div></div>
    <div class="adimlar">
      @foreach($surec as [$adimBaslik, $adimMetin])
        <div class="adim gorun"><span class="adim-no">{{ $loop->iteration }}</span><h3>{{ $adimBaslik }}</h3><p>{{ $adimMetin }}</p></div>
      @endforeach
    </div>
    <div class="hazirlik gorun">
      <h3>Muayeneye gelirken yanınızda bulunsun</h3>
      <div class="izgara uc">
        @foreach($hazirlik as $hazir)<div class="hizmet"><span class="tik"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span>{{ $hazir }}</div>@endforeach
      </div>
    </div>
  </div>
</section>

@if($video)
  <section class="bolum koyu-bolum">
    <div class="kap">
      <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Tanıtım</div><h2>Sizi muayenehanemize davet ediyoruz</h2></div></div>
      <div class="video-kutu gorun"><iframe src="{{ $video }}" title="{{ $ad }} tanıtım videosu" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe></div>
    </div>
  </section>
@endif

@if($duyurular)
  <section class="bolum" id="duyurular">
    <div class="kap">
      <div class="bolum-bas gorun"><div class="sol"><div class="etiket">Duyurular</div><h2>Muayenehaneden haberler</h2></div></div>
      <div class="izgara uc">
        @foreach($duyurular as $d)
          <article class="duyuru gorun">@if(! empty($d['tarih']))<time datetime="{{ $d['tarih'] }}">{{ \Illuminate\Support\Carbon::parse($d['tarih'])->locale('tr')->translatedFormat('j F Y') }}</time>@endif<h3>{{ $d['baslik'] }}</h3>@if(! empty($d['metin']))<p>{{ $d['metin'] }}</p>@endif</article>
        @endforeach
      </div>
    </div>
  </section>
@endif

@if($sss)
  <section class="bolum krem" id="sss">
    <div class="kap ikili" style="align-items:start;">
      <div class="gorun"><div class="etiket">Sık sorulan sorular</div><h2>Merak ettikleriniz</h2><p class="giris-metni" style="margin-top:18px;">Aklınıza takılan başka bir soru varsa bize doğrudan ulaşabilirsiniz.</p></div>
      <div class="sss gorun">@foreach($sss as $s)<details @if($loop->first) open @endif><summary>{{ $s['soru'] }}<span class="arti" aria-hidden="true">+</span></summary><div class="cevap">{{ $s['cevap'] }}</div></details>@endforeach</div>
    </div>
  </section>
@endif

<section class="bolum" id="iletisim">
  <div class="kap iletisim-izgara">
    <div class="gorun">
      <div class="etiket">Randevu</div>
      <h2>Randevu almak ister misiniz?</h2>
      <p class="giris-metni" style="margin-top:16px;">Formu doldurun ya da doğrudan ulaşın, en kısa sürede dönüş yapalım.</p>
      <div style="margin-top:22px;border-top:1px solid var(--cizgi);">
        @if($telLink)<a href="{{ $telLink }}" class="kanal"><span class="yuvar">{!! $ikonTel !!}</span><span><b>Telefon</b><span>{{ $telefon }}</span></span></a>@endif
        @if($waLink)<a href="{{ $waLink }}" class="kanal" target="_blank" rel="noopener"><span class="yuvar" style="background:#e8f6ee;color:#1f9d55;">{!! $ikonWa !!}</span><span><b>WhatsApp</b><span>Mesaj gönderin, hızlıca dönelim</span></span></a>@endif
        @if($adres)<a href="{{ $haritaLink ?: '#' }}" class="kanal" target="_blank" rel="noopener"><span class="yuvar">{!! $ikonKonum !!}</span><span><b>{{ $e->clinic_name ?: 'Adres' }}</b><span>{{ $adres }}</span></span></a>@endif
        @if($bolum('calisma_saatleri') && $saatler !== '')<div class="kanal"><span class="yuvar">{!! $ikonSaat !!}</span><span><b>Çalışma saatleri</b><span style="white-space:pre-line;">{{ $saatler }}</span></span></div>@endif
      </div>
    </div>
    <div class="form-kart gorun">
      <h3>Randevu talebi</h3>
      <form method="POST" action="{{ $formAction }}">
        @csrf
        @include('themes._shared.partials.honeypot')
        @if(session('doktor_sitesi_basarili'))<div class="basari" role="status">{{ session('doktor_sitesi_basarili') }}</div>@endif
        <div><label for="pd-ad">Adınız soyadınız</label><input id="pd-ad" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name">@error('full_name')<div class="hata">{{ $message }}</div>@enderror</div>
        <div><label for="pd-tel">Telefonunuz</label><input id="pd-tel" name="phone" value="{{ old('phone') }}" required maxlength="30" inputmode="tel" autocomplete="tel">@error('phone')<div class="hata">{{ $message }}</div>@enderror</div>
        <div><label for="pd-mesaj">Mesajınız (isteğe bağlı)</label><textarea id="pd-mesaj" name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea></div>
        @if($whatsapp)
          <button type="submit" class="btn btn-wa" style="width:100%;">{!! $ikonWa !!} WhatsApp ile Gönder</button>
          <p class="not">Gönder'e basınca WhatsApp açılır, mesajınız hazır yazılı gelir. Lütfen sağlık bilgilerinizi forma yazmayın.</p>
        @else
          <button type="submit" class="btn btn-dolu" style="width:100%;">Gönder</button>
          <p class="not">Bilgileriniz yalnızca size dönüş yapılması için kullanılır. Lütfen sağlık bilgilerinizi forma yazmayın.</p>
        @endif
      </form>
    </div>
  </div>
</section>
@if($bolum('konum') && $e->lat && $e->lng)
  <section class="bolum" style="padding-top:0;"><div class="kap gorun"><iframe class="harita" title="{{ $ad }} harita" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q={{ $e->lat }},{{ $e->lng }}&z=15&output=embed"></iframe></div></section>
@endif

<div class="kap" style="padding-bottom:32px;"><p class="tibbi-not">ℹ️ Bu sitedeki bilgiler genel bilgilendirme amaçlıdır; muayene, tanı ve tedavinin yerine geçmez. Acil durumlarda 112’yi arayın.</p></div>

{{-- 25 Ekim 2026: kullanıcının talebi - sitenin en altına tam alt bilgi bölümü. --}}
<footer class="altbilgi">
  <div class="kap">
    <div class="alt-izgara">
      <div>
        <a href="#" class="marka" aria-label="{{ $ad }}">
          @if($logo)<img src="{{ $logo }}" alt="{{ $ad }}" style="max-height:56px;background:#fff;border-radius:12px;padding:6px 10px;">@else<span class="monogram" aria-hidden="true">{{ $harf }}</span><span class="marka-ad"><b style="color:#fff;">{{ $ad }}</b>@if($brans)<small>{{ $brans }}</small>@endif</span>@endif
        </a>
        @if($hakkimda !== '')<p style="max-width:420px;">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $hakkimda), 180, '…', preserveWords: true) }}</p>@endif
        @if($e->clinic_name)<p style="margin-top:12px;">🏥 {{ $e->clinic_name }}</p>@endif
      </div>
      <div>
        <h4>Sayfa</h4>
        <ul>
          @foreach($menu as $k => $etiket)<li><a href="#{{ $k }}">{{ $etiket }}</a></li>@endforeach
          <li><a href="#durumlar">Başvuru konuları</a></li>
        </ul>
      </div>
      <div>
        <h4>İletişim</h4>
        <ul>
          @if($telLink)<li><a href="{{ $telLink }}">📞 {{ $telefon }}</a></li>@endif
          @if($waLink)<li><a href="{{ $waLink }}" target="_blank" rel="noopener">💬 WhatsApp'tan yazın</a></li>@endif
          @if($adres)<li>📍 {{ $adres }}</li>@endif
          @if($haritaLink)<li><a href="{{ $haritaLink }}" target="_blank" rel="noopener" style="color:#e9d3ab;font-weight:700;">Yol tarifi al →</a></li>@endif
          @if($bolum('calisma_saatleri') && $saatler !== '')<li style="white-space:pre-line;">🕘 {{ $saatler }}</li>@endif
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
    @if($telLink)<a href="{{ $telLink }}" class="btn btn-ara">{!! $ikonTel !!} Ara</a>@endif
    @if($waLink)<a href="{{ $waLink }}" class="btn btn-wa" target="_blank" rel="noopener">{!! $ikonWa !!} WhatsApp</a>@endif
  </div>
@endif
@if(! empty($hareketliDemo))
  <div class="onizleme">✨ Hareketli Profesyonel tasarım önizlemesi</div>
@elseif($demoPro)
  <div class="onizleme">Profesyonel Site tasarım önizlemesi — paket etkinleşince site bu görünümde yayınlanır.</div>
@elseif($isPreview)
  <div class="onizleme">Önizleme — bu site henüz yayında değil.</div>
@endif

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
    var io = new IntersectionObserver(function (g) { g.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('acik'); io.unobserve(x.target); } }); }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    ogeler.forEach(function (o) { io.observe(o); });
  } else { ogeler.forEach(function (o) { o.classList.add('acik'); }); }
})();
</script>
<script>
// 25 Ekim 2026: WhatsApp / telefon tıklama sayacı (raporda görünür).
document.addEventListener('click', function (e) {
  var a = e.target.closest ? e.target.closest('a') : null;
  if (!a) return;
  var h = a.getAttribute('href') || '';
  var tur = h.indexOf('tel:') === 0 ? 'arama' : (h.indexOf('wa.me') !== -1 ? 'whatsapp' : '');
  if (!tur || !navigator.sendBeacon) return;
  var v = new FormData(); v.append('_token', @json(csrf_token())); v.append('tur', tur);
  navigator.sendBeacon(@json($olayAdresi ?? ''), v);
});
</script>
@if(! empty($hareketli))@include('themes._shared.kurum-sitesi.pro._hareketli', ['parca' => 'js'])@endif
</body>
</html>
