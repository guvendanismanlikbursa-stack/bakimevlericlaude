@extends('layouts.brand')
@section('title', $facility->name.' - '.($facility->city->name ?? '').' '.($facility->district ?? ''))
@if(($serviceSection['slug'] ?? null) !== (current_brand()['default_section'] ?? null))
  {{-- bkz. layouts/brand.blade.php - envanter 3 markada da paylasildigi
       icin bu kurum kendi bolumune ait OLMAYAN markada da erisilebiliyor;
       kopya icerik olarak indekslenmesin, sadece kendi markasinda indexlensin. --}}
  @section('robots_meta', 'noindex,follow')
@endif
@section('og_title', $facility->name.' - '.($facility->city->name ?? '').' '.($facility->district ?? ''))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($facility->description), 100).' '.facility_brand_framing($facility, current_brand())['meta_suffix'])
@section('og_image', facility_card_image($facility))
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => [
      ['name' => current_brand()['name'], 'url' => brand_route('home')],
      ['name' => $facility->city->name ?? '', 'url' => brand_route('facilities.index', ['city' => $facility->city->slug ?? null])],
      ['name' => $facility->name, 'url' => brand_route('facilities.show', ['slug' => $facility->slug])],
  ]])
@endsection

@section('content')
@php
  $brand = current_brand();
  $section = $serviceSection;
  $colors = $section['theme'] ?? ['primary' => $brand['primary_color'], 'soft' => '#f8fafc'];
  $sectionSlug = $section['slug'] ?? null;
  $sectionActions = [
    'yasli-bakim' => ['Ziyaret planı çıkar', 'Fiyat ve bakım kapsamını karşılaştır', 'Aile için soru listesi oluştur'],
    'cocuk' => ['Eğitim programını incele', 'Servis ve yemek düzenini sor', 'Gelişim takibi detayını öğren'],
    'rehabilitasyon' => ['Terapi planını sor', 'Uzman kadro ve ekipmanı karşılaştır', 'Ev programı takibini öğren'],
  ][$sectionSlug] ?? ['Detayları incele', 'Teklif iste', 'Karşılaştır'];
  $sectionChecklist = [
    'yasli-bakim' => ['Günlük sağlık takibi yazılı mı?', 'Acil durumda aileye haber süreci net mi?', 'Ziyaret saatleri ve görüntülü görüşme imkanı var mı?', 'Ek bakım ücretleri açıkça belirtilmiş mi?', 'Beslenme ve ilaç takibi kim tarafından yapılıyor?'],
    'cocuk' => ['Yaş grubu ve sınıf mevcudu uygun mu?', 'Servis, yemek ve güvenlik süreçleri yazılı mı?', 'Gelişim takibi aileyle düzenli paylaşılıyor mu?', 'Rehberlik/psikolog desteği var mı?', 'Özel ihtiyaçlarda bireysel plan hazırlanıyor mu?'],
    'rehabilitasyon' => ['İlk değerlendirme uzman tarafından mı yapılıyor?', 'Seans hedefleri ve süreleri yazılı mı?', 'Cihaz ve terapi alanları ihtiyaca uygun mu?', 'Ev programı ve ara takip veriliyor mu?', 'İlerleme raporu aile/kullanıcı ile paylaşılıyor mu?'],
  ][$sectionSlug] ?? [];
  $primaryImage = $facility->primaryImage();
  $heroImage = $primaryImage
    ? facility_asset($primaryImage->path)
    : ($section['hero_image'] ?? null);
  // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - anlasmali
  // (is_broker_managed) ama henuz sahiplenilmemis bir kurumda "Ücret
  // Bilgisi İste"/"Ziyaret Talebi"/"Kontenjan Sor"/"Soru Sor" formlari
  // sadece is_claimed kontrol edildigi icin HIC gorunmuyordu, sayfa hala
  // "Bu kurum henüz sahiplenilmedi" diyordu. Facility::scopeAcceptsFamilyRequests()
  // ile AYNI kural (bkz. o scope'un yorumu) burada da uygulanir.
  $acceptsFamilyRequests = $facility->is_claimed || $facility->is_broker_managed;
@endphp

{{-- 12 Agustos 2026: kullanicinin talebi - kurum detay sayfasi "yarim
     kalmis profil" gibi hissettiriyordu, ozellikle kendi gorseli olmayan
     on kayitli kurumlarda. Sayfa artik duz bir baslikla degil, gercek
     bir fotografla (kurumun kendi gorseli varsa o, yoksa bolumun temsili
     fotografi) acilan bir hero ile basliyor - anasayfa/Hakkimizda ile
     ayni gorsel dil. --}}
@if($heroImage)
<section class="relative bg-gray-950 text-white overflow-hidden">
  <img src="{{ $heroImage }}" alt="{{ $facility->name }}" class="absolute inset-0 w-full h-full object-cover opacity-75">
  <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/55 to-gray-950/15"></div>
  <div class="relative max-w-6xl mx-auto px-4 py-10 md:py-14">
    <div class="flex items-center gap-2 mb-3 flex-wrap">
      @if($facility->is_claimed)
        <span class="bg-white/15 border border-white/20 text-white text-xs font-semibold px-2 py-1 rounded-full">✓ Onaylı / sahiplenilmiş kurum</span>
      @elseif($facility->is_broker_managed)
        <span class="bg-white/15 border border-white/20 text-white text-xs font-semibold px-2 py-1 rounded-full">✓ Anlaşmalı kurum</span>
      @else
        <span class="bg-white/10 border border-white/15 text-white/80 text-xs font-semibold px-2 py-1 rounded-full">Ön kayıtlı profil</span>
      @endif
      @if($facility->is_featured)
        <span class="bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-amber-950 text-xs font-black px-2 py-1 rounded-full shadow-sm">⭐ Öne Çıkan Kurum</span>
      @endif
      {{-- 29 Agustos 2026: kullanicinin talebi - anlasmali kurumlarin
           ekip tarafindan YERINDE ziyaret edildigini durust bir sekilde
           gostermek. SADECE gercekten isaretlenmisse (bkz. Facility::
           site_visited_at, Admin\FacilityController) - bos bir vaat degil. --}}
      @if($facility->site_visited_at)
        <span class="bg-emerald-500/90 text-white text-xs font-semibold px-2 py-1 rounded-full">🤝 Yerinde Ziyaret Edildi</span>
      @endif
      @if($facility->hasFastResponseBadge())
        <span class="bg-blue-500/90 text-white text-xs font-semibold px-2 py-1 rounded-full">⚡ Hızlı Yanıt</span>
      @endif
      @if($section)<span class="text-xs font-semibold px-2 py-1 rounded-full bg-white text-gray-950">{{ $section['title'] }}</span>@endif
      @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
      @if($ministryBadge = $facility->ministryVerificationBadge())<span class="{{ $ministryBadge['classes'] }} text-xs font-semibold px-2 py-1 rounded-full">{{ $ministryBadge['label'] }}</span>@endif
    </div>
    <h1 class="text-3xl md:text-4xl font-black text-balance">{{ $facility->name }}</h1>
    <p class="text-white/80 mt-2">{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</p>
  </div>
</section>
@endif

<div class="max-w-6xl mx-auto px-4 py-10 grid md:grid-cols-3 gap-8">
  <div class="md:col-span-2">
    @unless($heroImage)
    <div class="flex items-center gap-2 mb-2 flex-wrap">
      @if($facility->is_claimed)
        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Onaylı / sahiplenilmiş kurum</span>
      @elseif($facility->is_broker_managed)
        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Anlaşmalı kurum</span>
      @else
        <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-1 rounded-full">Ön kayıtlı profil</span>
      @endif
      @if($facility->is_featured)
        <span class="bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-amber-950 text-xs font-black px-2 py-1 rounded-full shadow-sm">⭐ Öne Çıkan Kurum</span>
      @endif
      @if($facility->site_visited_at)
        <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-1 rounded-full">🤝 Yerinde Ziyaret Edildi</span>
      @endif
      @if($facility->hasFastResponseBadge())
        <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-1 rounded-full">⚡ Hızlı Yanıt</span>
      @endif
      @if($section)<span class="text-xs font-semibold px-2 py-1 rounded-full" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">{{ $section['title'] }}</span>@endif
      @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
      @include('themes._shared.partials.segment-info-icon', ['categories' => [$facility->category], 'id' => 'segment-info-facility-'.$facility->id])
      @if($ministryBadge = $facility->ministryVerificationBadge())<span class="{{ $ministryBadge['classes'] }} text-xs font-semibold px-2 py-1 rounded-full">{{ $ministryBadge['label'] }}</span>@endif
    </div>
    <h1 class="text-3xl font-black text-gray-950">{{ $facility->name }}</h1>
    <p class="text-gray-500 mt-1">{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</p>
    @endunless
    <p class="text-sm text-gray-600 {{ $heroImage ? 'mt-5' : 'mt-3' }} italic">{{ facility_brand_framing($facility, $brand)['intro'] }}</p>

    {{-- 29 Agustos 2026: kullanicinin talebi - anlasmali kurumlarda boş yer
         bilgisinin guncel tutulma ihtimali daha yuksek (biz yonetiyoruz),
         bu yuzden POZITIF (Var) durumda sayfanin EN USTUNDE, kacirilamaz
         bir seride vurgulanir. Rakam YOK, sadece Var/Yok - bkz. onceki
         Facility::usesGenderSplitVacancy() yorumu, kurumlar birbirine
         tam doluluk sayisini gostermek istemiyor. Sadece "Yok" durumunda
         hic gosterilmez (asagidaki detay tabloda zaten var), cesaret
         kirici bir sey one cikarilmaz. --}}
    @if($facility->is_broker_managed)
      @php
        $vacancyHighlight = null;
        if ($facility->usesGenderSplitVacancy()) {
          if ($facility->vacancy_male === true && $facility->vacancy_female === true) {
            $vacancyHighlight = 'Bay ve Bayan için boş yer mevcut';
          } elseif ($facility->vacancy_male === true) {
            $vacancyHighlight = 'Bay için boş yer mevcut';
          } elseif ($facility->vacancy_female === true) {
            $vacancyHighlight = 'Bayan için boş yer mevcut';
          }
        } elseif ($facility->vacancy_general === true) {
          $vacancyHighlight = 'Boş yer mevcut';
        }
      @endphp
      @if($vacancyHighlight)
        <div class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
          <span class="text-emerald-600 font-black">✓</span>
          <span class="text-sm font-black text-emerald-800">{{ $vacancyHighlight }}</span>
        </div>
      @endif
    @endif

    {{-- 12 Agustos 2026: kullanicinin talebi - "ucret bilgisi al" formu
         sayfada VAR ama sag sutunda (mobilde galeri/yorum/soru/benzer
         kurumlar gibi UZUN bir icerigin ALTINA dusuyor) - aile mobilde
         hic gormeden vazgecebiliyordu. Bu buyuk, birincil renkli buton
         asagidaki forma dogrudan kaydiriyor, boylece en onemli aksiyon
         sayfanin en ustunde HER ZAMAN gorunur/erisilebilir. --}}
    @if($acceptsFamilyRequests)
      <a href="#teklif-talebi" class="mt-5 flex items-center justify-center gap-2 rounded-xl px-5 py-4 text-base font-black text-white text-center shadow-sm hover:shadow-md transition" style="background: {{ $colors['primary'] }};">
        💬 Ücret / Teklif Bilgisi Al
      </a>
    @endif
    <div class="mt-3 grid sm:grid-cols-3 gap-3">
      @if($acceptsFamilyRequests)
        <button type="button" class="js-engagement-toggle rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}">Favori</button>
        <button type="button" class="js-engagement-toggle rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      @endif
      <a href="{{ brand_route('engagement.wizard', $sectionSlug ? ['bolum' => $sectionSlug] : []) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-center text-gray-700">Sihirbaza git</a>
    </div>


    @php
      $brandTheme = current_brand()['theme'];
      $quality = $facility->profileQuality();
      $scoreBase = $quality['score'];
      $scoreLabel = $brandTheme === 'bakimeviara' ? 'Aile uygunluk skoru' : ($brandTheme === 'bakimevleri' ? 'Veri kalite skoru' : 'Hızlı skor');
    @endphp
    <div class="mt-6 grid sm:grid-cols-3 gap-3">
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-black text-gray-500">{{ $scoreLabel }}</div>
        <div class="text-3xl font-black mt-1" style="color: {{ $colors['primary'] }};">{{ round($scoreBase) }}/100</div>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        @if($facility->approved_reviews_avg_rating)
          <div class="text-sm font-black text-gray-500">Yorum puanı</div>
          <div class="text-3xl font-black text-amber-500 mt-1">★ {{ number_format($facility->approved_reviews_avg_rating, 1) }}</div>
        @elseif($facility->rating > 0)
          <div class="text-sm font-black text-gray-500">Google puanı</div>
          <div class="text-3xl font-black text-amber-500 mt-1">★ {{ number_format($facility->rating, 1) }}</div>
        @else
          <div class="text-sm font-black text-gray-500">Yorum puanı</div>
          <div class="text-lg font-black text-gray-400 mt-2">Henüz puan yok</div>
        @endif
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-black text-gray-500">Güven sinyali</div>
        @if($facility->site_visited_at)
          <div class="text-lg font-black text-emerald-700 mt-2">Ekibimizce ziyaret edildi</div>
        @else
          <div class="text-lg font-black text-gray-950 mt-2">{{ $acceptsFamilyRequests ? 'Onaylı kurum' : 'Profil doğrulama bekliyor' }}</div>
        @endif
      </div>
    </div>
    @php
      $galleryImages = $facility->images->take(10);
      $galleryCount = $galleryImages->count();
      // 19 Agustos 2026: kullanicinin talebi - admin/kurum yetkilisi hangi
      // gorselin ANA (kapak) gorsel oldugunu secebiliyor (bkz.
      // Facility::primaryImage()). Ana gorsel her zaman BUYUK alanda
      // gosterilir, galerideki dogal sirasi (kucuk resim seridi) BOZULMAZ -
      // sadece hangisinin baslangicta acik oldugu degisir.
      $primaryImage = $facility->primaryImage();
      $primaryIndex = 0;
      $secondaryEntries = [];
      foreach ($galleryImages as $idx => $img) {
        if ($primaryImage && $img->id === $primaryImage->id) {
          $primaryIndex = $idx;
          continue;
        }
        if (count($secondaryEntries) < 4) {
          $secondaryEntries[] = ['index' => $idx, 'image' => $img];
        }
      }
    @endphp
    {{-- 27 Agustos 2026: kullanicinin talebi - SADECE anlasmali kurumlar
         icin tanitim videosu (bkz. Admin\FacilityController::storeUploadedVideo()
         ayni tarihli yorum). Galeriden ONCE, cunku video fotografdan
         daha ikna edici bir arac - aile once onu gormeli. --}}
    @if($facility->video_path)
      <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-black mb-3" style="color: {{ $colors['primary'] }};">Tanıtım Videosu</div>
        <video src="{{ facility_asset($facility->video_path) }}" controls playsinline preload="metadata" class="w-full rounded-xl bg-black max-h-[480px]"></video>
      </section>
    @endif

    <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-4">
        <div>
          <div class="text-sm font-black" style="color: {{ $colors['primary'] }};">Kurum görselleri</div>
          <h2 class="text-xl font-black text-gray-950">Fotoğraf galerisi</h2>
        </div>
        <div class="text-xs font-semibold text-gray-500">{{ $galleryCount }}/10 görsel</div>
      </div>

      @php $galleryId = 'ps-gallery-'.$facility->id; @endphp
      @if($galleryImages->isNotEmpty())
        {{-- 19 Agustos 2026: kullanicinin bildirdigi gercek hata - 5'ten az
             gorsel oldugunda kalan slotlar "Ek görsel alanı" yazan bos,
             kesik cizgili kutularla doldueruluyordu; ziyaretcilere bozuk/
             eksik gorunuyordu. Bu doldurma kaldirildi - sadece GERCEKTEN var
             olan gorseller gosterilir. Ayrica kullanicinin talebi: ana
             gorselin uzerine sol/sag ok (tiklayinca sayfa icinde diger
             gorsele gecer, sayfa yenilenmez) ve sag ustte "x/toplam" sayaci
             eklendi - buyutme (lightbox) davranisi ayni sekilde calismaya
             devam ediyor, sadece tiklanan gorselin GUNCEL index'inden acilir. --}}
        <div class="grid lg:grid-cols-[1.5fr_1fr] gap-3">
          <div class="relative">
            <img id="{{ $galleryId }}-main" src="{{ facility_asset(($primaryImage ?? $galleryImages->first())->path) }}" onclick="openFacilityGalleryAt('{{ $galleryId }}', window.facilityGalleryIndex['{{ $galleryId }}'] ?? {{ $primaryIndex }})" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openFacilityGalleryAt('{{ $galleryId }}', window.facilityGalleryIndex['{{ $galleryId }}'] ?? {{ $primaryIndex }});}" tabindex="0" role="button" aria-label="Galeriyi büyük görüntüle" class="h-72 w-full rounded-xl object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition focus:outline-none focus:ring-2 focus:ring-offset-2" style="--tw-ring-color: {{ $colors['primary'] }};" alt="{{ $facility->name }} ana görseli">
            @if($galleryCount > 1)
              <button type="button" onclick="event.stopPropagation(); shiftFacilityMainImage('{{ $galleryId }}', -1)" aria-label="Önceki görsel" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-gray-950/60 text-white text-lg flex items-center justify-center hover:bg-gray-950/80 transition">‹</button>
              <button type="button" onclick="event.stopPropagation(); shiftFacilityMainImage('{{ $galleryId }}', 1)" aria-label="Sonraki görsel" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-gray-950/60 text-white text-lg flex items-center justify-center hover:bg-gray-950/80 transition">›</button>
              <span id="{{ $galleryId }}-counter" class="absolute top-2 right-2 bg-gray-950/70 text-white text-xs font-bold px-2 py-1 rounded-full pointer-events-none">{{ $primaryIndex + 1 }}/{{ $galleryCount }}</span>
            @endif
          </div>
          @if($galleryCount > 1)
            <div class="grid grid-cols-2 gap-3">
              @foreach($secondaryEntries as $entry)
                <img src="{{ facility_asset($entry['image']->path) }}" onclick="openFacilityGalleryAt('{{ $galleryId }}', {{ $entry['index'] }})" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openFacilityGalleryAt('{{ $galleryId }}', {{ $entry['index'] }});}" tabindex="0" role="button" aria-label="Galeri görseli, büyük görüntüle" class="h-[132px] w-full rounded-xl object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition focus:outline-none focus:ring-2 focus:ring-offset-2" style="--tw-ring-color: {{ $colors['primary'] }};" alt="{{ $facility->name }} görseli">
              @endforeach
            </div>
          @endif
        </div>
        <script>
          window.facilityGalleryImages = window.facilityGalleryImages || {};
          window.facilityGalleryIndex = window.facilityGalleryIndex || {};
          window.facilityGalleryIndex['{{ $galleryId }}'] = {{ $primaryIndex }};
          window.facilityGalleryImages['{{ $galleryId }}'] = @json($galleryImages->map(fn ($img) => facility_asset($img->path))->values());
          window.shiftFacilityMainImage = window.shiftFacilityMainImage || function (containerId, delta) {
            var imgs = window.facilityGalleryImages[containerId];
            if (!imgs || imgs.length < 2) return;
            var idx = (window.facilityGalleryIndex[containerId] || 0) + delta;
            idx = ((idx % imgs.length) + imgs.length) % imgs.length;
            window.facilityGalleryIndex[containerId] = idx;
            var mainImg = document.getElementById(containerId + '-main');
            if (mainImg) mainImg.src = imgs[idx];
            var counter = document.getElementById(containerId + '-counter');
            if (counter) counter.textContent = (idx + 1) + '/' + imgs.length;
          };
        </script>
      @else
        <div class="h-72 rounded-xl relative overflow-hidden flex flex-col items-center justify-center text-center px-6" style="background: {{ $colors['soft'] }};">
          @if($section['hero_image'] ?? null)
            <img src="{{ $section['hero_image'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-25">
          @endif
          <div class="relative">
            <span class="inline-flex rounded-xl p-3 mb-3 bg-white shadow-sm" style="color: {{ $colors['primary'] }};">
              @if($section)@include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-6 h-6'])@endif
            </span>
            <div class="text-lg font-black text-gray-800">Bu kurum henüz galeri görseli eklememiş</div>
            <p class="text-sm text-gray-500 mt-2 max-w-md">Kurum yetkilisi panelden en fazla 10 gerçek kurum görseli yükleyebilir. Görseller eklendiğinde ziyaretçiler odaları, ortak alanları ve hizmet ortamını burada inceleyebilir.</p>
          </div>
        </div>
      @endif

      <div id="{{ $galleryId }}" class="grid grid-cols-5 sm:grid-cols-10 gap-2 mt-3">
        @foreach($galleryImages as $img)
          <a href="{{ facility_asset($img->path) }}" data-pswp-width="1600" data-pswp-height="1200" data-image-id="{{ $img->id }}" target="_blank" rel="noopener">
            <img src="{{ facility_asset($img->path) }}" class="h-16 w-full rounded-lg object-cover border border-gray-100 cursor-zoom-in hover:opacity-80 transition" alt="{{ $facility->name }} küçük görsel">
          </a>
        @endforeach
      </div>
    </section>
    @include('themes._shared.partials.image-lightbox')
    <script>document.addEventListener('DOMContentLoaded', function () { initFacilityGallery('{{ $galleryId }}', @json(brand_route('facilities.image.viewed', ['slug' => $facility->slug, 'image' => '__IMAGE_ID__']))); });</script>

    {{-- 19 Agustos 2026: kullanicinin talebi - kurum yetkilisinin panelden
         yukledigi haftalik yemek listesi, ziyaretcinin tiklayip
         buyutebilecegi sekilde kurum sayfasinda gosterilir. Kullanicinin
         ek talebi: "farkli bir alanda yayinlanmali, dikkat cekmeli" -
         galeriden/diger kartlardan bilerek farkli (turuncu/amber vurgulu,
         rozetli) bir tasarim; sade beyaz kart yerine goze carpsin. --}}
    @if($facility->menu_image_path)
      @php $menuGalleryId = 'ps-menu-'.$facility->id; @endphp
      <div class="mt-6 bg-gradient-to-br from-orange-50 to-amber-50 border-2 border-orange-300 rounded-xl p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-2xl leading-none">🍽️</span>
          <h3 class="font-black text-orange-950 text-lg">Yemek Listesi</h3>
          @if($facility->menu_image_updated_at && $facility->menu_image_updated_at->gt(now()->subDays(7)))
            <span class="bg-orange-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full">GÜNCEL</span>
          @endif
        </div>
        <div id="{{ $menuGalleryId }}">
          <a href="{{ facility_asset($facility->menu_image_path) }}" data-pswp-width="1600" data-pswp-height="2000" target="_blank" rel="noopener" class="relative inline-block group">
            <img src="{{ facility_asset($facility->menu_image_path) }}" class="rounded-lg h-20 object-cover border-2 border-white shadow cursor-zoom-in group-hover:opacity-90 transition" alt="{{ $facility->name }} yemek listesi">
            <span class="absolute bottom-1 right-1 bg-gray-950/80 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full flex items-center gap-1">🔍 Büyüt</span>
          </a>
        </div>
        @if($facility->menu_image_updated_at)
          <p class="text-xs text-orange-800/70 mt-2">Son güncelleme: {{ $facility->menu_image_updated_at->diffForHumans() }}</p>
        @endif
      </div>
      <script>document.addEventListener('DOMContentLoaded', function () { initFacilityGallery('{{ $menuGalleryId }}'); });</script>
    @endif

    {{-- 17 Agustos 2026: kullanicinin talebi - Kurum Performansi karti
         galerinin hemen altina tasindi. Herkese acik alanda SADECE
         goruntulenme rakami gosterilir; telefon/WhatsApp tiklamasi baslik
         olarak gorunur ama rakami kilitlidir - bu veriyi sadece sahiplenmis
         kurumun yetkilisi kendi panelinden gorebilir (bkz. facility/
         dashboard.blade.php Performans Trendi). Amac: somut talep kanitini
         gostermek ama detayi sahiplenme icin bir tesvik olarak saklamak.
         28 Agustos 2026: kullanicinin talebi - bu karttaki goruntulenme
         sayisi eskiden SON 30 GUN (engagementStats30d) idi, sahiplenme
         sayfasindaki TUM ZAMANLAR sayisiyla (views_count) FARKLI
         gorunuyordu ("111 vs 9" gibi) ve kafa karistiriyordu. Artik HER
         YERDE (bu kart, sahiplenme sayfasi, kurum panelinin kendi
         gosterge paneli) AYNI TEK sayi (views_count, hic kucuk gorunmez,
         sadece buyur) kullaniliyor - iki farkli sayi asla gorunmez. --}}
    @php $perf = $facility->performanceSummary(); @endphp
    <div class="mt-6 bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
      <h3 class="font-black text-gray-950 mb-1">Kurum Performansı</h3>
      <p class="text-xs text-gray-400 mb-3">Yayına başladığından bu yana</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
          <div class="text-gray-500 text-xs">👁️ Profil görüntüleme</div>
          <div class="font-black">{{ number_format($perf['views_count'], 0, ',', '.') }}</div>
        </div>
        <div>
          <div class="text-gray-500 text-xs">📞 Telefon tıklaması</div>
          <div class="font-black text-gray-300">🔒</div>
        </div>
        <div>
          <div class="text-gray-500 text-xs">💬 WhatsApp tıklaması</div>
          <div class="font-black text-gray-300">🔒</div>
        </div>
        <div>
          <div class="text-gray-500 text-xs">Son Güncelleme</div>
          <div class="font-black">{{ $perf['last_updated_at']->diffForHumans() }}</div>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-2">🔒 Bu veriyi sadece kurum yetkilileri görebilir.</p>
      <div class="mt-3 flex gap-2 flex-wrap">
        @if($perf['is_claimed'])
          <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Yetkilisi tarafından doğrulandı{{ $perf['claimed_at'] ? ' · '.$perf['claimed_at']->format('d.m.Y') : '' }}</span>
        @else
          <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Henüz doğrulanmadı (ön kayıtlı profil)</span>
        @endif
      </div>
    </div>

    @if($facility->hasPreciseLocation())
      {{-- 17 Agustos 2026: kullanicinin talebi - Google Haritalar konumu.
           SADECE gercek adresten geocode edilmis kurumlarda gosterilir
           (bkz. Facility::hasPreciseLocation) - il merkezi yedek
           koordinatiyla aileyi yanlis yere yonlendirmemek icin. --}}
      <div class="mt-6 bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
        <h3 class="font-black text-gray-950 mb-3">Konum</h3>
        <div class="rounded-lg overflow-hidden border border-gray-100">
          <iframe
            title="{{ $facility->name }} konumu"
            src="https://maps.google.com/maps?q={{ $facility->lat }},{{ $facility->lng }}&z=15&output=embed"
            width="100%" height="280" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $facility->lat }},{{ $facility->lng }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-black" style="color: {{ $colors['primary'] }};">📍 Yol tarifi al</a>
      </div>
    @endif

    <div class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="text-sm font-black mb-3" style="color: {{ $colors['primary'] }};">Bu bölümde sorulacak aksiyonlar</div>
      <div class="grid sm:grid-cols-3 gap-3">
        @foreach($sectionActions as $action)
          <div class="rounded-lg px-3 py-3 text-sm font-semibold" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">{{ $action }}</div>
        @endforeach
      </div>
    </div>

    @if(!empty($sectionChecklist))
    <div class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between gap-4 mb-4">
        <div>
          <div class="text-sm font-black" style="color: {{ $colors['primary'] }};">Detaylı kontrol listesi</div>
          <h2 class="text-xl font-black text-gray-950">Görüşmeden önce işaretleyin</h2>
        </div>
        <a href="{{ brand_route('engagement.compare') }}" class="text-sm font-black text-primary">Karşılaştırmaya git →</a>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($sectionChecklist as $item)
          <label class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-700">
            <input type="checkbox" class="mt-1 rounded border-gray-300">
            <span>{{ $item }}</span>
          </label>
        @endforeach
      </div>
    </div>
    @endif

    {{-- 31 Agustos 2026: kullanicinin talebi - "kurum kartlarinda bazi
         ozellikler yazmadan kullanicilar goremiyor, bos dahi olsa BUTUN
         ozellik ve alanlari kullanicilar gorsun". Eskiden SADECE kurumun
         isaretledigi ozellikler (varsa) rozet olarak goruluyordu, hicbiri
         isaretlenmemisse bolum tamamen kayboluyordu. Artik bolumun TUM
         olasi ozellikleri (config/brands.php service_sections.*.features)
         her zaman listelenir, kurumda VARSA vurgulu/isaretli, YOKSA soluk
         gorunur - "hicbir sey girilmemis" ile "hicbir ozelligi yok" farki
         artik gorunur, bilgi eksikligi gizlenmiyor. --}}
    @if(!empty($section['features']))
      <div class="mt-5">
        <div class="text-sm font-black text-gray-950 mb-2">{{ $section['title'] ?? 'Kurum' }} Özellikleri</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          @foreach($section['features'] as $feature)
            @php $hasFeature = in_array($feature, $facility->services ?? [], true); @endphp
            <div class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg border {{ $hasFeature ? 'bg-green-50 border-green-200 text-green-800 font-semibold' : 'bg-gray-50 border-gray-100 text-gray-400' }}">
              <span>{{ $hasFeature ? '✓' : '—' }}</span>
              <span>{{ $feature }}</span>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    @php $extraServices = collect($facility->services ?? [])->diff($section['features'] ?? []); @endphp
    @if($extraServices->isNotEmpty())
      <div class="flex gap-2 flex-wrap mt-3">
        @foreach($extraServices as $service)
          <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">{{ $service }}</span>
        @endforeach
      </div>
    @endif

    <p class="mt-6 text-gray-700 leading-relaxed">{{ $facility->description }}</p>

    <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="text-gray-500">Kapasite</div>
        <div class="font-black text-gray-950">{{ $facility->capacity ?? '-' }} kişi</div>
      </div>
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="text-gray-500">Fiyat Aralığı</div>
        <div class="font-black text-gray-950">
          @if($facility->price_min && $facility->price_max)
            {{ number_format($facility->price_min,0,',','.') }} TL - {{ number_format($facility->price_max,0,',','.') }} TL
          @elseif($facility->price_min)
            {{ number_format($facility->price_min,0,',','.') }} TL'den başlıyor
          @else
            Bilgi için iletişime geçin
          @endif
        </div>
      </div>
    </div>

    @if($facility->usesGenderSplitVacancy() ? ($facility->vacancy_male !== null || $facility->vacancy_female !== null) : $facility->vacancy_general !== null)
      <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
        @if($facility->usesGenderSplitVacancy())
          @if($facility->vacancy_male !== null)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
              <div class="text-gray-500">Bay için Yer</div>
              <div class="font-black {{ $facility->vacancy_male ? 'text-green-700' : 'text-red-600' }}">{{ $facility->vacancy_male ? 'Var' : 'Yok' }}</div>
            </div>
          @endif
          @if($facility->vacancy_female !== null)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
              <div class="text-gray-500">Bayan için Yer</div>
              <div class="font-black {{ $facility->vacancy_female ? 'text-green-700' : 'text-red-600' }}">{{ $facility->vacancy_female ? 'Var' : 'Yok' }}</div>
            </div>
          @endif
        @else
          <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="text-gray-500">Boş Yer</div>
            <div class="font-black {{ $facility->vacancy_general ? 'text-green-700' : 'text-red-600' }}">{{ $facility->vacancy_general ? 'Var' : 'Yok' }}</div>
          </div>
        @endif
      </div>
    @endif

    {{-- 31 Agustos 2026: kullanicinin talebi - bkz. yukaridaki "Ozellikleri"
         blogundaki ayni tarihli yorum. Kuruma-ozel detay alanlari (Oda
         tipleri, Hemsire destegi, Yas araligi vb. - config/brands.php
         service_sections.*.profile_fields) daha once ADMIN/kurum panelinde
         doldurulsa bile genel kurum sayfasinda HIC GORUNMUYORDU. Artik
         hepsi listelenir, doldurulmamissa "Belirtilmedi" yazar. --}}
    @php
      $sectionDetailRecord = match($sectionSlug) {
        'yasli-bakim' => $facility->elderlyDetail,
        'cocuk' => $facility->childDetail,
        'rehabilitasyon' => $facility->rehabDetail,
        default => null,
      };
      $sectionDetailValues = $sectionDetailRecord->details ?? [];
      $sectionDetailFieldList = collect($section['profile_fields'] ?? [])->map(fn ($label) => [
        'key' => \Illuminate\Support\Str::slug($label),
        'label' => $label,
      ]);
    @endphp
    @if($sectionDetailFieldList->isNotEmpty())
      <div class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="text-sm font-black mb-3" style="color: {{ $colors['primary'] }};">{{ $section['title'] ?? 'Kurum' }} Detayları</div>
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
          @foreach($sectionDetailFieldList as $field)
            <div>
              <div class="text-gray-500">{{ $field['label'] }}</div>
              <div class="font-semibold text-gray-950">{{ $sectionDetailValues[$field['key']] ?? 'Belirtilmedi' }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    @include('themes._shared.partials.price-options-table', ['optionsTitle' => 'Oda Tipine Göre Fiyat Aralığı', 'optionsItems' => $facility->roomTypes])
    @include('themes._shared.partials.price-options-table', ['optionsTitle' => 'Yaş Grubuna Göre Fiyat Aralığı', 'optionsItems' => $facility->ageGroups])
    @include('themes._shared.partials.price-options-table', ['optionsTitle' => 'Program Süresine Göre Fiyat Aralığı', 'optionsItems' => $facility->programTypes])

    @php
      // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - anlasmali
      // (is_broker_managed) bir kurumdan gercekten teklif istemis bir
      // aile bile (bkz. FacilityReviewController, ayni tarihli fix) bu
      // kontrol hala SADECE is_claimed oldugu icin yorum birakamiyordu.
      $canReview = $acceptsFamilyRequests
        && session('family_user_id')
        && \App\Models\OfferRequest::where('family_user_id', session('family_user_id'))->where('facility_id', $facility->id)->exists();
    @endphp
    <section class="mt-10 grid lg:grid-cols-[1fr_360px] gap-6">
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
        <h2 class="text-xl font-black text-gray-950 mb-4">Kurum yorumları</h2>
        <div class="space-y-3">
          @forelse(($facility->approvedReviews ?? collect()) as $review)
            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
              <div class="flex items-center justify-between"><div class="font-black text-gray-950">{{ $review->reviewer_name }}</div><div class="text-amber-700 font-black">★ {{ $review->rating }}</div></div>
              <p class="text-sm text-gray-600 mt-2">{{ $review->body }}</p>
              @if($review->facility_reply)
                <div class="mt-3 rounded-lg bg-white border border-gray-200 p-3">
                  <div class="text-xs font-black" style="color: {{ $colors['primary'] }};">Kurum Yanıtı</div>
                  <p class="text-sm text-gray-600 mt-1">{{ $review->facility_reply }}</p>
                </div>
              @endif
            </div>
          @empty
            <div class="rounded-lg p-5 text-sm text-gray-600 flex items-center gap-3" style="background: {{ $colors['soft'] }};">
              <span class="text-2xl">💬</span>
              <span>Bu kurum için henüz onaylı bir yorum yok — ilk yorumu bırakan siz olabilirsiniz.</span>
            </div>
          @endforelse
        </div>
      </div>
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm h-fit">
        <h3 class="font-black text-gray-950 mb-3">Yorum bırak</h3>
        @if($canReview)
          <form method="POST" action="{{ brand_route('reviews.store', ['slug' => $facility->slug]) }}" class="space-y-3">
            @csrf
            <select name="rating" required class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Puan seçin</option><option value="5" @selected(old('rating')=='5')>5 - Çok iyi</option><option value="4" @selected(old('rating')=='4')>4 - İyi</option><option value="3" @selected(old('rating')=='3')>3 - Orta</option><option value="2" @selected(old('rating')=='2')>2 - Zayıf</option><option value="1" @selected(old('rating')=='1')>1 - Kötü</option></select>
            <textarea name="body" placeholder="Deneyiminizi veya görüşme notunuzu yazın" rows="4" class="border rounded-lg px-3 py-2 w-full">{{ old('body') }}</textarea>
            <button class="btn-primary w-full rounded-lg py-2 font-black">Yorumu Gönder</button>
            <p class="text-xs text-gray-400">Yorumlar admin onayından sonra yayınlanır.</p>
          </form>
        @elseif(! $acceptsFamilyRequests)
          <p class="text-sm text-gray-500">Bu kurum henüz sahiplenilmedi. Yorum yapabilmek için kurumun onaylanmış olması gerekir.</p>
        @else
          <p class="text-sm text-gray-500">Yorum yapabilmek için önce bu kurumdan <a href="#teklif-talebi" class="font-black text-primary underline">ücret/teklif bilgisi</a> istemelisiniz.</p>
        @endif
      </div>
    </section>

    <section class="mt-10 bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
      <h2 class="text-xl font-black text-gray-950 mb-4">Aile Soruları</h2>
      <div class="space-y-3 mb-5">
        @forelse($facility->answeredQuestions as $question)
          <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
            <div class="text-sm font-black text-gray-900 mb-1">S: {{ $question->question }}</div>
            <div class="text-sm text-gray-600">C: {{ $question->answer }}</div>
          </div>
        @empty
          <div class="rounded-lg p-5 text-sm text-gray-600 flex items-center gap-3" style="background: {{ $colors['soft'] }};">
            <span class="text-2xl">❓</span>
            <span>Henüz yanıtlanmış soru yok.@if($acceptsFamilyRequests) İlk soruyu siz sorabilirsiniz; kurum yetkilisi cevapladığında burada görünür.@endif</span>
          </div>
        @endforelse
      </div>
      @if($acceptsFamilyRequests)
        <form method="POST" action="{{ brand_route('questions.store', ['slug' => $facility->slug]) }}" class="flex flex-col sm:flex-row gap-2">
          @csrf
          @include('themes._shared.partials.honeypot')
          <label for="q-asker-name" class="sr-only">Adınız (opsiyonel)</label>
          <input type="text" id="q-asker-name" name="asker_name" value="{{ old('asker_name') }}" placeholder="Adınız (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm sm:w-48">
          <label for="q-question" class="sr-only">Sorunuz</label>
          <input type="text" id="q-question" name="question" value="{{ old('question') }}" placeholder="Örn: Alzheimer hastası kabul ediyor musunuz?" required class="border rounded-lg px-3 py-2 text-sm flex-1">
          <button class="btn-primary rounded-lg px-5 py-2 text-sm font-black whitespace-nowrap">Soru Sor</button>
        </form>
      @else
        <p class="text-sm text-gray-500">Bu kurum henüz sahiplenilmedi; soru sorabilmek için kurumun onaylanmış olması gerekir.</p>
      @endif
    </section>

  </div>

  <div>
    <div id="teklif-talebi" class="bg-white p-6 rounded-xl shadow-sm sticky top-24 border border-gray-100">
      {{-- 31 Agustos 2026: kullanicinin talebi - anlasmali (is_broker_managed)
           bir kurum, sahiplenilmemis olsa bile ZATEN yonetiliyor - "bu
           kurumun yetkilisi misiniz" sorusu artik anlamsiz/kafa karistirici.
           Bunun yerine aileye somut bir deger anlatan bir ibare gosterilir.
           Claim durumundan BAGIMSIZ (sahiplenilmis olsa bile bu bilgi
           degerli), ve kullanicinin ikinci talebi uzerine kutunun ustune,
           en dikkat cekici konuma tasindi + daha belirgin (sari) renk
           verildi - once soluk yesil zeminde secilmiyordu. --}}
      @if($facility->is_broker_managed)
        <div class="rounded-lg bg-amber-100 border-2 border-amber-300 p-4 mb-6">
          <p class="text-sm font-black text-amber-900 mb-1">🤝 Bu kurumu seçerseniz</p>
          <p class="text-sm text-amber-800">Güven Bakım Hizmetleri, ayda 2 defa sizin adınıza ücretsiz yerinde ziyaret gerçekleştirir ve sizi bilgilendirir.</p>
        </div>
      @endif
      @if($acceptsFamilyRequests)
        <h3 class="font-black mb-1 text-gray-950">Ücret / Teklif Bilgisi Al</h3>
        {{-- 29 Agustos 2026: kullanicinin talebi - anlasmali kurumlarda daha
             kisisel bir dokunus, ama abartisiz/durust: SADECE gercekten
             ziyaret edilmisse (site_visited_at) gorunur, "biz her kurumu
             taniyoruz" gibi genel/asilsiz bir vaat degil. Satis dili degil,
             sakin ve bilgilendirici - unlem/vurgu/aciliyet kelimesi yok. --}}
        @if($facility->site_visited_at)
          <p class="text-xs text-gray-500 mb-4">Bu kurumu ekibimiz yerinde ziyaret etti ve yakından tanıyor. Sorularınızı gerçek bilgiyle yanıtlarız.</p>
        @else
          <div class="mb-4"></div>
        @endif
        <form method="POST" action="{{ brand_route('offer-requests.store') }}" class="space-y-3">
          @csrf
          @include('themes._shared.partials.honeypot')
          <input type="hidden" name="facility_id" value="{{ $facility->id }}">
          <label for="offer-care-for" class="sr-only">Kimin için?</label>
          <select id="offer-care-for" name="care_for" class="border rounded-lg px-3 py-2 w-full bg-white">
            <option value="">Kimin için? (opsiyonel)</option>
            <option value="kendisi" @selected(old('care_for')=='kendisi')>Kendim için</option>
            <option value="anne-baba" @selected(old('care_for')=='anne-baba')>Anne/Babam için</option>
            <option value="cocuk" @selected(old('care_for')=='cocuk')>Çocuğum için</option>
            <option value="yakin" @selected(old('care_for')=='yakin')>Yakınım için</option>
          </select>
          <label for="offer-patient-name" class="sr-only">Hasta/çocuk adı (opsiyonel)</label>
          <input type="text" id="offer-patient-name" name="patient_name" value="{{ old('patient_name') }}" placeholder="Hasta/çocuk adı (opsiyonel)" class="border rounded-lg px-3 py-2 w-full">
          <label for="offer-full-name" class="sr-only">Adınız Soyadınız</label>
          <input type="text" id="offer-full-name" name="full_name" value="{{ old('full_name') }}" placeholder="Adınız Soyadınız" required class="border rounded-lg px-3 py-2 w-full">
          <label for="offer-phone" class="sr-only">Telefon</label>
          <input type="text" id="offer-phone" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
          <label for="offer-email" class="sr-only">E-posta</label>
          <input type="email" id="offer-email" name="email" value="{{ old('email') }}" placeholder="E-posta" class="border rounded-lg px-3 py-2 w-full">
          <label for="offer-message" class="sr-only">Mesajınız / ihtiyaç detayı</label>
          <textarea id="offer-message" name="message" placeholder="Mesajınız / ihtiyaç detayı" rows="3" class="border rounded-lg px-3 py-2 w-full">{{ old('message') }}</textarea>
          <button class="btn-primary w-full py-2 rounded-lg font-black">Ücret Bilgisi İste</button>
          <p class="text-xs text-gray-400">Devam ederseniz, ücret bilgisi alabilmek için ücretsiz bir aile hesabı oluşturmanız istenecektir.</p>
        </form>
        {{-- 31 Agustos 2026: kullanicinin bildirdigi gercek hata - burada
             anlasmali kurumlarda bile HALA kurumun kendi ham numarasi
             yaziyordu, facility_public_contact_phone() ayni kurali
             (anlasmaliysa Guven Bakim numarasi) burada da uygular.
             1 Eylul 2026: bu numara sadece DUZ METIN olarak yaziyordu
             (tel: linki degildi) ve WhatsApp butonu hic yoktu - anlasmali
             ama sahiplenilmemis bir kurum artik BU dala dustugu icin
             (bkz. $acceptsFamilyRequests), direkt arama/WhatsApp imkani
             kaybolmus olurdu. --}}
        @if($contactPhoneClaimed = facility_public_contact_phone($facility))
          <a href="tel:{{ $contactPhoneClaimed }}" class="block mt-4 text-sm font-black text-center" style="color: {{ $colors['primary'] }};">📞 {{ $contactPhoneClaimed }}</a>
        @endif
        @if($claimedWhatsappUrl = facility_public_whatsapp_url($facility))
          <a href="{{ $claimedWhatsappUrl }}" target="_blank" rel="noopener" class="block mt-2 text-sm font-black text-center" style="color: #128C4A;">💬 WhatsApp'tan Yaz</a>
        @endif


        <div class="mt-6 pt-6 border-t">
          <h3 class="font-black mb-3 text-gray-950">Ziyaret / randevu talebi</h3>
          <form method="POST" action="{{ brand_route('visit-requests.store', ['slug' => $facility->slug]) }}" class="space-y-3">
            @csrf
            @include('themes._shared.partials.honeypot')
            <label for="visit-full-name" class="sr-only">Adınız Soyadınız</label>
            <input type="text" id="visit-full-name" name="full_name" value="{{ old('full_name') }}" placeholder="Adınız Soyadınız" required class="border rounded-lg px-3 py-2 w-full">
            <label for="visit-phone" class="sr-only">Telefon</label>
            <input type="text" id="visit-phone" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
            <label for="visit-email" class="sr-only">E-posta</label>
            <input type="email" id="visit-email" name="email" value="{{ old('email') }}" placeholder="E-posta" class="border rounded-lg px-3 py-2 w-full">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label for="visit-preferred-day" class="sr-only">Tercih edilen gün</label>
                <select id="visit-preferred-day" name="preferred_day" class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Gün</option><option @selected(old('preferred_day')=='Hafta içi')>Hafta içi</option><option @selected(old('preferred_day')=='Hafta sonu')>Hafta sonu</option><option @selected(old('preferred_day')=='Fark etmez')>Fark etmez</option></select>
              </div>
              <div>
                <label for="visit-preferred-time" class="sr-only">Tercih edilen saat</label>
                <select id="visit-preferred-time" name="preferred_time" class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Saat</option><option @selected(old('preferred_time')=='Sabah')>Sabah</option><option @selected(old('preferred_time')=='Öğlen')>Öğlen</option><option @selected(old('preferred_time')=='Akşamüstü')>Akşamüstü</option></select>
              </div>
            </div>
            <label for="visit-message" class="sr-only">Ziyaret notu</label>
            <textarea id="visit-message" name="message" placeholder="Ziyaret notu" rows="2" class="border rounded-lg px-3 py-2 w-full">{{ old('message') }}</textarea>
            <button class="w-full rounded-lg border border-primary text-primary font-black py-2">Ziyaret Talebi Gönder</button>
          </form>
        </div>

        <div class="mt-6 pt-6 border-t">
          <h3 class="font-black mb-1 text-gray-950">Kontenjan Sor</h3>
          <p class="text-xs text-gray-500 mb-3">Tek tıkla "Boş yer var mı?" sorusu kuruma iletilir.</p>
          <form method="POST" action="{{ brand_route('visit-requests.availability', ['slug' => $facility->slug]) }}" class="flex gap-2">
            @csrf
            @include('themes._shared.partials.honeypot')
            <label for="avail-full-name" class="sr-only">Adınız</label>
            <input type="text" id="avail-full-name" name="full_name" value="{{ old('full_name') }}" placeholder="Adınız" required class="border rounded-lg px-3 py-2 w-1/2 text-sm">
            <label for="avail-phone" class="sr-only">Telefon</label>
            <input type="text" id="avail-phone" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-1/2 text-sm">
            <button class="whitespace-nowrap rounded-lg bg-gray-900 text-white font-black px-3 text-sm">Sor</button>
          </form>
        </div>
      @else
        <h3 class="font-black mb-2 text-gray-950">Bu kurum henüz sahiplenilmedi</h3>
        {{-- 17 Agustos 2026: kullanicinin talebi - "otomatik toplanmis"
             ifadesi kazima/scraping cagrisimi yaptigi icin sakincali
             olabilir, "olusturulmus" ile degistirildi. --}}
        <p class="text-sm text-gray-500 mb-4">Ücret/teklif bilgisi, ziyaret talebi ve kontenjan sorgusu ancak kurum yetkilisi profili sahiplenip onayladıktan sonra kullanılabilir. Bu bilgiler Google Maps verilerinden oluşturulmuş ön kayıt profilidir.</p>
        {{-- 17 Agustos 2026: kullanicinin talebi - sahiplenilmemis kurumda
             platform-ici teklif/ziyaret akisi olmadigi icin, ziyaretcinin
             kuruma DOGRUDAN ulasabilecegi 2 buton (arama+WhatsApp) eklendi.
             Tiklamalar Facility::engagementStats30d() icin kaydedilir -
             kurum sahiplenmeye tesvik edilirken gercek talep kanitina
             donusur (bkz. yukaridaki Kurum Performansi karti). --}}
        {{-- 31 Agustos 2026: kullanicinin bildirdigi gercek hata - bu 2 buton
             ailelere gosterildigi halde kurumun KENDI numarasina ve
             (WhatsApp'ta) kuruma hitap eden sahiplenme davet metnine
             gidiyordu. facility_public_contact_phone()/facility_public_whatsapp_url()
             anlasmali (is_broker_managed) kurumlarda Guven Bakim
             numarasina yonlendirir, aile-hitapli normal bir mesaj kullanir. --}}
        @if($contactPhoneUnclaimed = facility_public_contact_phone($facility))
          <a href="tel:{{ $contactPhoneUnclaimed }}" data-contact-track="phone_click" class="flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-black text-white text-center shadow-sm hover:shadow-md transition mb-2" style="background: {{ $colors['primary'] }};">📞 Kurumu Ara</a>
        @endif
        @if($facilityWhatsappUrl = facility_public_whatsapp_url($facility))
          <a href="{{ $facilityWhatsappUrl }}" target="_blank" rel="noopener" data-contact-track="whatsapp_click" class="flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-black text-center border-2" style="border-color:#25D366; color:#128C4A;">💬 WhatsApp'tan Yaz</a>
        @endif
        <script>
        document.querySelectorAll('[data-contact-track]').forEach(function (el) {
          el.addEventListener('click', function () {
            fetch(@json(brand_route('facilities.contact-click', ['slug' => $facility->slug])), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
              body: JSON.stringify({ type: el.dataset.contactTrack }),
              keepalive: true,
            }).catch(function () {});
          });
        });
        </script>
      @endif

      @if(! $facility->is_claimed && ! $facility->is_broker_managed)
        <div class="mt-6 pt-6 border-t">
          <p class="text-sm text-gray-600 mb-2">Bu kurumun yetkilisi misiniz?</p>
          {{-- 28 Agustos 2026: kullanicinin talebi - buton adi daha dogrudan
               bir soru haline getirildi ve WhatsApp butonuyle (yesil)
               karismamasi icin amber tonu secildi - dikkat cekmeli ama
               abartili olmamali diye duz beyaz-yazili degil, yumusak
               amber zeminli/koyu amber yazili bir stil kullanildi. --}}
          <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="block text-center bg-amber-50 border-2 border-amber-400 text-amber-800 font-black py-2 rounded-lg hover:bg-amber-100 transition">Kurum Size mi Ait?</a>
        </div>
      @endif

      {{-- 26 Agustos 2026: kullanicinin talebi - is basvurusu butonu SADECE
           sahiplenilmis veya aracilik (is_broker_managed) kurumlarda, VE
           SADECE yasli-bakim/cocuk bolumlerinde gorunur - hicbir haberi
           olmayan bir on-kayitli kuruma "sana eleman buldum" demek anlamsiz
           olurdu (kullanicinin acik talebi). Ayni gun bulunan gercek hata:
           "!== 'rehabilitasyon'" kontrolu SADECE o tek brand_scope degerini
           disliyordu, ama "Nörolojik Rehabilitasyon Merkezi" kategorisinin
           brand_scope'u 'fizik-tedavi' oldugu icin sizip buton yanlislikla
           bir rehabilitasyon kurumunda goruyordu - DAHIL ETME listesine
           cevrildi (bkz. JobApplicationController::abortUnlessEligible()
           ayni tarihli yorum, sunucu tarafi kontrol de ayni sekilde duzeltildi). --}}
      @if(($facility->is_claimed || $facility->is_broker_managed) && in_array($facility->category->brand_scope ?? null, ['yasli-bakim', 'cocuk-bakim'], true))
        <div class="mt-6 pt-6 border-t">
          <p class="text-sm text-gray-600 mb-2">Bu kurumda çalışmak ister misiniz?</p>
          <a href="{{ brand_route('job-application.create', ['slug' => $facility->slug]) }}" class="block text-center border border-primary text-primary font-black py-2 rounded-lg">Burada Çalışmak İstiyorum</a>
        </div>
      @endif

      {{-- 26 Agustos 2026: kullanicinin talebi - "bakim takip ziyareti"
           (ucretli, aile paneli uzerinden talep edilen periyodik ziyaret+
           rapor hizmeti). SADECE yasli-bakim bolumunde VE admin'in acikca
           izin verdigi (allows_visit_service) kurumlarda gorunur - kurumun
           onayi olmadan ucuncu bir ziyaretci gonderilmesi rahatsizlik
           yaratabilir. Sahiplenme durumundan (is_claimed/is_broker_managed)
           BILEREK BAGIMSIZ - on kayitli bir kurum da admin onayiyla acik
           olabilir (kullanicinin acik talebi). --}}
      @if(($facility->category->brand_scope ?? null) === 'yasli-bakim' && $facility->allows_visit_service)
        <div class="mt-6 pt-6 border-t">
          <p class="text-sm text-gray-600 mb-2">Yakınınız burada mı kalıyor? Ekibimiz düzenli ziyaret edip size raporlayabilir.</p>
          <a href="{{ brand_route('family.visit-service.create', ['facility' => $facility->slug]) }}" class="block text-center bg-primary text-white font-black py-2 rounded-lg">Yakınımı Ziyaret Et</a>
        </div>
      @endif
    </div>
  </div>
</div>

@if($related->isNotEmpty())
  {{-- 12 Agustos 2026: kullanicinin talebi - "Benzer Kurumlar" tum kurum
       detaylari ve islemler BITTIKTEN SONRA, sayfanin gercek sonunda,
       tam genislikte ayri bir kesif bolumu olarak gorunmeli - 2 sutunlu
       izlem/form alaninin ortasina sikismis olmamali. --}}
  {{-- 14 Agustos 2026: kullanicinin talebi - burada listeleme sayfasindaki
       BUYUK karti (foto+aciklama+4 buton) kullanmak, ozellikle mobilde,
       kurumlarin "ic ice gecmis" gibi hissettiren, ekran boyu kartlar
       yiginina yol aciyordu. Bu sadece bir kesif/yonlendirme listesi -
       kucuk, yatay mini-kartlar (kucuk gorsel + isim + ilce) yeterli. --}}
  <div class="max-w-6xl mx-auto px-4 pb-10">
    <h2 class="font-black text-xl mt-4 mb-4">Benzer Kurumlar</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      @foreach($related as $r)
        @php $rImg = facility_card_image($r, $section); @endphp
        <a href="{{ brand_route('facilities.show', ['slug' => $r->slug]) }}" class="flex items-center gap-3 bg-white border border-gray-100 rounded-lg p-2 hover:shadow-md transition group">
          <div class="w-14 h-14 shrink-0 rounded-md overflow-hidden bg-gray-50">
            <img src="{{ $rImg }}" alt="{{ $r->name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
          </div>
          <div class="min-w-0">
            <div class="text-sm font-black text-gray-950 line-clamp-1">{{ $r->name }}</div>
            <div class="text-xs text-gray-500 line-clamp-1">{{ $r->city->name }} · {{ $r->district }}</div>
            @if($r->is_featured)<span class="inline-block mt-0.5 text-[10px] font-black text-amber-700">⭐ Öne Çıkan</span>@endif
          </div>
        </a>
      @endforeach
    </div>
  </div>
@endif

@if($acceptsFamilyRequests)
  {{-- 12 Agustos 2026: kullanicinin talebi - "bu uygulamanin kalbi"
       (fiyat/teklif talebi) mobilde sayfa kaydirilirken GOZDEN
       KAYBOLMAMALI. Masaustunde sag sutundaki form zaten sticky; mobilde
       o sutun icerigin en altina dustugu icin, ekranin altina SABIT,
       her an tiklanabilir bir CTA cubugu eklendi (yalniz mobilde
       gorunur, lg: ve ustunde gizli - masaustunde zaten gorunur form var). --}}
  <div class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] px-4 py-3">
    <a href="#teklif-talebi" class="flex items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-sm font-black text-white text-center" style="background: {{ $colors['primary'] }};">
      💬 Ücret / Teklif Bilgisi Al
    </a>
  </div>
  <div class="lg:hidden h-20"></div>
  {{-- Sohbet ikonu bu sabit CTA cubuguyla cakismasin diye mobilde yukari itiliyor. --}}
  <style>@media (max-width: 1023px) { :root { --chat-toggle-bottom: 5.75rem; } }</style>
@endif

@php
  // 14 Agustos 2026: kullanicinin talebi uzerine yapilan SEO denetiminde
  // bulundu - aggregateRating HER kurumda kosulsuz yayinlaniyordu,
  // reviewCount site-ici gercek yorum sayisi 0 olsa bile max(1, 0) ile
  // hep en az 1 gosteriliyordu (Google Maps'ten aktarilan rating - onlarca/
  // yuzlerce GERCEK Google yorumunun ortalamasi - sanki sitede 1 yorumla
  // olusmus gibi sunuluyordu, bu Google'in yapilandirilmis veri kurallarina
  // aykiri). Artik SADECE sitede gercekten onayli yorum varsa yayinlaniyor,
  // reviewCount de gercek sayiyi yansitiyor.
  //
  // 17 Agustos 2026: kullanicinin talebi uzerine yapilan SEO gelistirmesi
  // sirasinda canli sayfada bulundu - anahtar '@@context' olarak yazilmisti
  // (cift @), JSON ciktisinda GERCEKTEN "@@context" olarak yayinlaniyordu.
  // Google'in yapilandirilmis veri ayristiricisi tam olarak '@context'
  // bekler, "@@context" gecersiz sayilip TUM blok sessizce yok sayilir -
  // yani bu ozellik simdiye kadar hicbir zaman calismamis. Ayni gecis
  // sirasinda: bos alanlar artik "null" olarak degil hic yazilmiyor, kurum
  // gorseli/tam kategoriye gore tur/fiyat araligi/gercek konum (varsa)
  // eklendi - bkz. GeocodingService ayni tarihli calisma, artik cok daha
  // fazla kurumun gercek koordinati var.
  $reviewCount = $facility->approvedReviews->count();
  $schemaType = match ($sectionSlug) {
    'cocuk' => 'ChildCare',
    'rehabilitasyon' => 'MedicalBusiness',
    default => 'LocalBusiness',
  };
  $facilitySchema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => $schemaType,
    'name' => $facility->name,
    'description' => filled($facility->description) ? strip_tags($facility->description) : null,
    'telephone' => $facility->phone ?: null,
    'image' => facility_card_image($facility),
    'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
    'address' => array_filter([
      '@type' => 'PostalAddress',
      'streetAddress' => $facility->address ?: null,
      'addressLocality' => trim(($facility->district ? $facility->district.', ' : '').($facility->city->name ?? '')) ?: null,
      'addressCountry' => 'TR',
    ]),
  ]);
  if ($facility->hasPreciseLocation()) {
    $facilitySchema['geo'] = [
      '@type' => 'GeoCoordinates',
      'latitude' => (float) $facility->lat,
      'longitude' => (float) $facility->lng,
    ];
  }
  if ($facility->price_min || $facility->price_max) {
    $facilitySchema['priceRange'] = $facility->price_min && $facility->price_max
      ? number_format($facility->price_min, 0).' - '.number_format($facility->price_max, 0).' TL'
      : number_format($facility->price_min ?: $facility->price_max, 0).' TL';
  }
  if ($reviewCount > 0) {
    $facilitySchema['aggregateRating'] = [
      '@type' => 'AggregateRating',
      'ratingValue' => (float) $facility->approved_reviews_avg_rating,
      'reviewCount' => $reviewCount,
    ];
  }
@endphp
<script type="application/ld+json">
{!! json_encode($facilitySchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

@include('themes._shared.partials.engagement-script')
@endsection
