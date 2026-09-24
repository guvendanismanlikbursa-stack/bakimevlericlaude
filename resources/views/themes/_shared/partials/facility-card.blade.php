@php
  $section = service_section_for_scope($facility->category->brand_scope);
  $cardImage = facility_card_image($facility, $section);
  $ownImagePath = $facility->relationLoaded('images') ? $facility->primaryImage()?->path : null;
  // 12 Agustos 2026: kullanicinin talebi - listeleme "sahte" hissettiriyordu
  // cunku cogu kart ayni ornek gorseli kullaniyor; gorseli gercek kurum
  // yuklemesi olmayan kartlarda kucuk, durust bir "Ornek gorsel" etiketi
  // gosteriyoruz - gizlemek yerine seffaf olmak guveni artirir.
  $isSampleImage = ! $ownImagePath || str_starts_with($ownImagePath, 'facilities/demo/');
  // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bu sart eskiden
  // SADECE is_claimed'e bakiyordu, yani anlasmali (is_broker_managed) ama
  // henuz sahiplenilmemis HER kurum, sitenin HER YERINDE (arama sonuclari,
  // anasayfa, kategori listeleri, "Benzer Kurumlar", rehber sayfalari)
  // aksiyon butonu olmayan kucuk "Ön Kayıtlı" mini-karta dusuyordu - tıpkı
  // gercekten hicbir iliskimiz olmayan rastgele bir kurum gibi. Facility::
  // scopeAcceptsFamilyRequests() ile AYNI kural: anlasmali kurumlar da
  // artik buyuk, aksiyon butonlu karti kullanir.
  // 22 Eylul 2026: kullanicinin talebi - odemesi GECERLI olan abone
  // kurumlar da artik anlasmali/sahiplenilmis kurumlarla AYNI buyuk,
  // aksiyon butonlu karti kullanir (kucuk mini-karta dusmez) - "Yardımsever
  // Kurum" rozetiyle one cikarilirlar.
  $isActiveSubscriberCard = $facility->isActiveSubscriber();
  $isPreRegisteredCard = ! $facility->is_claimed && ! $facility->is_broker_managed && ! $isActiveSubscriberCard;
@endphp
@if($isPreRegisteredCard)
  {{-- 17 Agustos 2026: kullanicinin talebi - on kayitli kurumlar buyuk
       kartla ayni yeri kapladigi icin listelerde az kurum gorunuyordu,
       ayrica sahiplenilmis kurumlardan gorsel olarak ayrilmiyordu. "Benzer
       Kurumlar" bolumundeki kucuk yatay mini-kart deseni burada da
       kullanildi - tiklaninca yine ayni tam kurum detay sayfasi acilir,
       ayrica bir aksiyon butonu yok. Sahiplenilmis kurumlarda buyuk kart
       (asagida) DEGISMEDEN kaliyor. --}}
  {{-- 18 Eylul 2026: kullanicinin bildirdigi gercek hata - listeleme
       sayfasinda buyuk kartlarla ayni CSS grid satirinda bu kucuk kart
       varsayilan olarak (align-items:stretch) satir yuksekligine
       geriliyordu, kenarligi de gerilince kartin altinda buyuk bos beyaz
       bir kutu gorunuyordu. self-start ile kart artik sadece kendi gercek
       icerigi kadar yer kaplar, gerilmez. --}}
  {{-- 20 Eylul 2026: kullanicinin talebi - on kayitli kurumlarda da
       Karşılaştır ozelligi kullanilabilmeli. Buton <a> disina, kardes
       olarak eklendi (buton icinde <a>/<button> ic ice OLAMAZ - bkz.
       nested-form/nested-link sinifi hatalar, gecmiste sorun cikarmisti). --}}
  <div class="self-start bg-white border border-gray-100 rounded-lg p-2 hover:shadow-md transition group">
    <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="flex items-center gap-3">
      <div class="w-14 h-14 shrink-0 rounded-md overflow-hidden bg-gray-50 relative">
        <img src="{{ $cardImage }}" alt="{{ $facility->imageAltText() }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition">
      </div>
      <div class="min-w-0">
        {{-- 22 Eylul 2026: kullanicinin talebi - "Ön Kayıtlı" etiketi
             kaldirildi (kafa karistirici/itici bulundu). Aktif abone
             kurumlar artik bu mini karta hic dusmuyor (bkz. yukaridaki
             $isPreRegisteredCard hesaplamasi) - buyuk kartta "Yardımsever
             Kurum" rozetiyle gosteriliyorlar. --}}
        <div class="flex items-center gap-1.5 flex-wrap">
          @if($facility->is_featured)<span class="text-[10px] font-black text-amber-700 shrink-0">⭐</span>@endif
        </div>
        <div class="text-sm font-black text-gray-950 line-clamp-1">{{ $facility->name }}</div>
        <div class="text-xs text-gray-500 line-clamp-1 flex items-center gap-1.5">
          <span>{{ $facility->city->name }} · {{ $facility->district }}</span>
          {{-- 20 Eylul 2026: kullanicinin talebi - il/ilce satirinin yanina,
               salt sayi degil "X yorumu var" seklinde, platform + Google
               yorumlari toplami (canli hesaplanir, yeni yorum eklendikce
               otomatik guncellenir - sabit/onbelleklenmis bir sayi degil). --}}
          @if(($miniReviewCount = $facility->totalReviewCount()) > 0)
            <span class="text-gray-400 shrink-0">· 💬 {{ $miniReviewCount }} yorumu var</span>
          @endif
        </div>
      </div>
    </a>
    <div class="mt-2 pt-2 border-t border-gray-50">
      {{-- 20 Eylul 2026: kullanicinin talebi - buton "belli olmuyordu" (duz
           gri kenarlik digerlerinden ayirt edilmiyordu); 2px mavi kenarlik +
           mavi metin ile digerlerinden aciyor ama dolgu yok (asiri degil).
           Sadece zaten derlenmis (app-*.css'de dogrulandi) siniflar
           kullanildi: border-2, border-blue-400, text-blue-700. --}}
      <button type="button" class="js-engagement-toggle w-full rounded-md border-2 border-blue-400 px-2 py-1.5 text-xs font-black text-blue-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
    </div>
  </div>
@else
{{--
  14 Agustos 2026: kullanicinin talebi - "one cikan" kurumlar gorsel
  olarak da fark edilmeli, diger kartlarla ayni gorunmemeli (aksi halde
  kurum yetkilisi "one cikan"in gercek bir deger oldugunu hissetmez).
  Altin/amber tonlu bir cerceve + kose seridi ekleniyor - marka rengine
  bagli degil (premium = altin, evrensel bir dil), digerlerinden aciyor.
--}}
{{--
  4 Eylul 2026: kullanicinin talebi - anlaşmalı (is_broker_managed) kurumlar
  da gorsel olarak fark edilsin, ama "⭐ ÖNE ÇIKAN" (is_featured, admin'in
  bilerek one cikardigi) ile KARISTIRILMASIN diye BILEREK farkli renk
  (amber degil mavi) ve kurdele yerine sade cerceve kullanildi - iki
  etiketin anlami farkli (biri "vitrin", digeri "bizimle ticari anlasmasi
  var"), gorsel olarak da ayirt edilebilir kalmali.
--}}
@php
  $isBrokerHighlight = $facility->is_broker_managed && ! $facility->is_featured;
@endphp
<article class="bg-white rounded-xl overflow-hidden group relative transition
  {{ $facility->is_featured
      ? 'border-2 border-amber-300 shadow-lg shadow-amber-200/50 hover:shadow-xl hover:shadow-amber-300/50'
      : ($isBrokerHighlight
          ? 'border-2 border-blue-400 shadow-lg shadow-blue-300/60 hover:shadow-xl hover:shadow-blue-400/60 bg-gradient-to-b from-blue-50/70 via-white to-white'
          : 'border border-gray-100 shadow-sm hover:shadow-lg') }}">
  @if($facility->is_featured)
    {{-- 18 Eylul 2026: kullanicinin bildirdigi gercek hata - "ÖNE ÇIKAN"
         yazisinin son harfi (N) kesiliyordu (rotate-[-45deg] + kartin
         overflow-hidden'i yuzunden rozetin "yuksek" ucu kartin ust
         kenarinin disinda kaliyordu). Once top-5 class'i denendi ama
         canliya yansimadi - bu proje FTP-only deploy edildiginde CSS
         YENIDEN DERLENMIYOR, derlenmis app-*.css dosyasinda daha once hic
         kullanilmamis Tailwind class'lari (top-5, top-9, -left-11 vb.)
         HICBIR KURALA sahip degil, yani class degisikligi sessizce
         etkisiz kalıyordu (dogrulandi: /build/assets/app-*.css icinde
         .top-5{ hic yok, .top-3{ var). Bu yuzden Tailwind class'i yerine,
         derlemeye bagli olmayan satir ici style ile kesin calisan bir
         konum kullanildi - emoji/genislik/pozisyon aynen korunuyor. --}}
    <div class="absolute -left-9 z-10 w-36 rotate-[-45deg] bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-center text-[10px] font-black text-amber-950 py-1 shadow-md pointer-events-none" style="top: 20px;">
      ⭐ ÖNE ÇIKAN
    </div>
  @endif
  {{-- 4 Eylul 2026: kullanicinin talebi - ince bir cizgi ("kartlar hala
       ayni gorunuyor") yetersiz kaldi, "Öne Çıkan"in AYNI gorsel gucunde
       ikinci bir kurdele lazimdi - karsi kosede (sag ust), mavi/lacivert,
       BEYAZ kalin yazi ile en az onun kadar dikkat cekici. --}}
  @if($facility->is_broker_managed)
    {{-- 18 Eylul 2026: ayni tarihli "ÖNE ÇIKAN" duzeltmesiyle ayni kok
         neden ve ayni cozum (derlenmemis Tailwind class yerine satir ici
         style), bkz. o yorumu. --}}
    <div class="absolute -right-9 z-10 w-36 rotate-[45deg] bg-gradient-to-r from-blue-600 via-blue-500 to-sky-500 text-center text-[10px] font-black text-white py-1 shadow-md pointer-events-none" style="top: 20px;">
      🤝 ANLAŞMALI
    </div>
  @endif
  @unless($isPreRegisteredCard)
    <button type="button" class="js-engagement-toggle absolute top-2 right-2 z-10 w-8 h-8 rounded-full bg-white/90 shadow flex items-center justify-center text-lg text-gray-400" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}" data-icon="1" aria-label="Favori">♥</button>
  @endunless
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
    <div class="h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center relative">
      <img src="{{ $cardImage }}" alt="{{ $facility->imageAltText() }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
      @if($isSampleImage)
        <span class="absolute bottom-2 left-2 bg-gray-950/70 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">Örnek görsel</span>
      @endif
    </div>
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        @if($section)<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $section['title'] }}</span>@endif
        @if($facility->is_claimed)<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Onaylı</span>
        @elseif($facility->is_broker_managed)<span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">🤝 Anlaşmalı</span>@endif
        @if($facility->hasFastResponseBadge())<span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">⚡ Hızlı Yanıt</span>@endif
        @if($facility->hasFreeChildAccidentInsurance())<span class="text-xs font-black px-2 py-0.5 rounded-full" style="background:#0d9488;color:#ffffff;">🛡️ Ücretsiz Ferdi Kaza Sigortası</span>@endif
        @if($ministryBadge = $facility->ministryVerificationBadge())<span class="{{ $ministryBadge['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full">{{ $ministryBadge['label'] }}</span>@endif
        {{-- 22 Eylul 2026: kullanicinin talebi - abonelik odemesi GECERLI
             olan (ama sahiplenilmemis/anlasmasiz) kurumlar artik anlasmali/
             sahiplenilmis kurumlarla ayni buyuk kartta, "Yardımsever Kurum"
             rozetiyle gosterilir. --}}
        @if($isActiveSubscriberCard)
          <span class="text-xs font-black px-2 py-0.5 rounded-full" style="background:#fae8ff;color:#a21caf;">💜 Yardımsever Kurum</span>
        @endif
        @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
        @isset($badge){!! $badge !!}@endisset
      </div>
      <h2 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h2>
      <p class="text-sm text-gray-500 mb-1 flex items-center gap-1.5 flex-wrap">
        <span>{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</span>
        {{-- 20 Eylul 2026: kullanicinin talebi - yorum ikon+sayisi il/ilce
             satirinin yanina; platform + Google yorumlari toplami, canli
             hesaplanir (bkz. mini-kartla ayni yorum, yukarida). --}}
        @if(($totalReviewCount = $facility->totalReviewCount()) > 0)
          <span class="text-gray-400 shrink-0">· 💬 {{ $totalReviewCount }} yorumu var</span>
        @endif
      </p>
      @if($facility->views_count > 0)
        <p class="text-xs text-gray-400 mb-3 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M10 3.5c-4.5 0-7.5 3.5-8.5 6.5 1 3 4 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4-6.5-8.5-6.5Zm0 10.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/><circle cx="10" cy="10" r="2"/></svg>
          {{ number_format($facility->views_count, 0, ',', '.') }} kez görüntülendi
        </p>
      @else
        <div class="mb-3"></div>
      @endif
      <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $facility->description }}</p>
      <div class="flex items-center justify-between">
        @if($facility->rating > 0)
          <span class="text-amber-700 font-black text-sm">★ {{ number_format($facility->rating, 1) }}</span>
          @if($facility->source === 'google_maps_veri_cekici')<span class="text-[10px] text-gray-400 font-semibold -ml-1">(Google)</span>@endif
        @else
          <span></span>
        @endif
        {{-- 7 Eylul 2026: kullanicinin talebi - bkz. price-locked.blade.php ayni tarihli yorum. --}}
        @if(session('family_user_id'))
          @if($facility->price_min)<span class="text-gray-700 font-black text-sm">{{ number_format($facility->price_min,0,',','.') }} TL<span class="text-gray-400 font-normal">/ay</span></span>@else<span class="text-primary text-sm font-black">Fiyat iste</span>@endif
        @else
          @include('themes._shared.partials.price-locked')
        @endif
      </div>
    </div>
  </a>
  {{-- 4 Eylul 2026: kullanicinin acik talebi - anlaşmalı kurumlar sadece
       gorsel olarak degil, AILEYI GERCEKTEN TEKLIF ISTEMEYE tesvik edecek
       sekilde one cikmali. Buyuk, tek basina, ikonlu bir birincil CTA -
       diger 4 buton (İncele/Karşılaştır/Toplu Fiyat Al dahil "Fiyat Al")
       kucuk/ikincil kalir, tekrar etmez.
       22 Eylul 2026: kullanicinin talebi - aktif abone (Yardımsever Kurum)
       kurumlar da ayni tesvik edici CTA'yi alir. --}}
  @if($facility->is_broker_managed || $isActiveSubscriberCard)
    <div class="px-4 pb-3">
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-black text-white text-center bg-gradient-to-r from-blue-600 to-sky-500 shadow-md shadow-blue-400/40 hover:shadow-lg hover:shadow-blue-400/60 transition">
        💬 Hemen Ücret Sor <span class="font-normal opacity-90">· Öncelikli Yanıt</span>
      </a>
    </div>
  @endif
  <div class="px-4 pb-4 grid grid-cols-2 gap-2">
    <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 text-center hover:bg-gray-50">İncele</a>
    @if($facility->is_broker_managed)
      <button type="button" class="js-engagement-toggle rounded-lg border-2 border-blue-400 px-3 py-2 text-sm font-black text-blue-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50 col-span-2" data-mode="bulk-quote" data-id="{{ $facility->id }}">Toplu Fiyat Al</button>
    @else
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="rounded-lg px-3 py-2 text-sm font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Fiyat Al</a>
      <button type="button" class="js-engagement-toggle rounded-lg border-2 border-blue-400 px-3 py-2 text-sm font-black text-blue-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50" data-mode="bulk-quote" data-id="{{ $facility->id }}">Toplu Fiyat Al</button>
    @endif
  </div>
</article>
@endif
