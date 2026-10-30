@php
  $section = service_section_for_scope($facility->category->brand_scope);
  $cardImage = facility_card_image($facility, $section);
  $ownImagePath = $facility->relationLoaded('images') ? $facility->primaryImage()?->path : null;
  // 12 Agustos 2026: kullanicinin talebi - listeleme "sahte" hissettiriyordu
  // cunku cogu kart ayni ornek gorseli kullaniyor; gorseli gercek kurum
  // yuklemesi olmayan kartlarda kucuk, durust bir "Ornek gorsel" etiketi
  // gosteriyoruz - gizlemek yerine seffaf olmak guveni artirir.
  $isSampleImage = ! $ownImagePath || str_starts_with($ownImagePath, 'facilities/demo/');
  $isActiveSubscriberCard = $facility->isActiveSubscriber();
  // 30 Eylul 2026: kullanicinin talebi - TUM kurumlar (ön kayıtlı dahil)
  // ARTIK AYNI buyuk kart tipini kullanir, kucuk mini-kart kaldirildi.
  // Ayrim artik kart BOYUTUYLA degil, 3 net rozetle yapilir (bkz.
  // Facility::scopeOrderByListingPriority ayni tarihli yorum - AYNI
  // oncelik sirasi): Anlaşmalı / Öne Çıkan (abone VEYA admin'in elle
  // one cikardigi is_featured) / Önerilen (sahiplenilmis, digerleri
  // degil). Ön kayitli kurumlar hic rozet almaz.
  $isBrokerManaged = $facility->is_broker_managed;
  // 28 Ekim 2026: TTB kılavuzu - sağlık kuruluşlarında "Öne Çıkan"/"Önerilen" yok, yalnızca "Onaylı".
  $saglikKarti = $facility->isHealthProvider();
  $isFeaturedCard = ! $saglikKarti && ! $isBrokerManaged && ($facility->is_featured || $isActiveSubscriberCard);
  $isRecommendedCard = ! $saglikKarti && ! $isBrokerManaged && ! $isFeaturedCard && $facility->is_claimed;
@endphp
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
<article @if($isRecommendedCard) style="border:2px solid #34d399;box-shadow:0 10px 25px -5px rgba(16,185,129,.35);background:linear-gradient(to bottom,#ecfdf5,#ffffff 45%,#ffffff);" @endif class="bg-white rounded-xl overflow-hidden group relative transition
  {{ $isFeaturedCard
      ? 'border-2 border-amber-300 shadow-lg shadow-amber-200/50 hover:shadow-xl hover:shadow-amber-300/50'
      : ($isBrokerManaged
          ? 'border-2 border-blue-400 shadow-lg shadow-blue-300/60 hover:shadow-xl hover:shadow-blue-400/60 bg-gradient-to-b from-blue-50/70 via-white to-white'
          : 'border border-gray-100 shadow-sm hover:shadow-lg') }}">
  {{-- 28 Eylul 2026: kullanicinin talebi - kosegen kurdeleler ("sacma
       sapan" gorunuyordu) kaldirildi, gorselin sol-ust kosesinde kucuk,
       duz, alt alta rozetlere cevrildi. Sadece daha once buyuk kartta zaten
       kullanilmis (dogrulanmis) renkler/desen tekrarlanir - Tailwind class'i
       degil satir ici style (bkz. asagidaki eski yorum - FTP deploy'da CSS
       yeniden derlenmiyor, kesin calismasi icin). --}}
  <div class="absolute top-2 left-2 z-10 flex flex-col items-start gap-1 pointer-events-none">
    @if($isBrokerManaged)
      <span class="text-[10px] font-black px-2 py-1 rounded-md shadow" style="background:linear-gradient(to right,#2563eb,#0ea5e9);color:#ffffff;">🤝 Anlaşmalı</span>
    @elseif($isFeaturedCard)
      <span class="text-[10px] font-black px-2 py-1 rounded-md shadow" style="background:linear-gradient(to right,#fbbf24,#f59e0b);color:#451a03;">⭐ ÖNE ÇIKAN</span>
    @elseif($isRecommendedCard)
      <span class="text-[10px] font-black px-2 py-1 rounded-md shadow" style="background:linear-gradient(to right,#059669,#10b981);color:#ffffff;">⭐ ÖNERİLEN</span>
    @endif
  </div>
  {{-- 15 Ekim 2026: kullanicinin talebi - favori kalp ikonu SIMDILIK
       gizlendi (silinmedi, "hidden" ile - istenirse tek kelime kaldirilip
       geri acilabilir). --}}
  <button type="button" class="js-engagement-toggle hidden absolute top-2 right-2 z-10 w-8 h-8 rounded-full bg-white/90 shadow flex items-center justify-center text-lg text-gray-400" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}" data-icon="1" aria-label="Favori">♥</button>
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
    <div class="h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center relative">
      <img src="{{ $cardImage }}" alt="{{ $facility->imageAltText() }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
      @if($isSampleImage)
        <span class="absolute bottom-2 left-2 bg-gray-950/70 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">Örnek görsel</span>
      @endif
      {{-- 28 Eylul 2026: kullanicinin talebi - kartta gercek (demo haric)
           gorsel sayisi + yorum sayisi, gorselin uzerinde kompakt bir
           rozetle gosterilsin. Ikisi de 0 ise rozet HIC gorunmez (bos bir
           kutu gostermek yerine). --}}
      @php
        $cardRealImageCount = $facility->realImageCount();
        $cardReviewCount = $facility->hidesPatientReviews() ? 0 : $facility->totalReviewCount();
      @endphp
      @if($cardRealImageCount > 0 || $cardReviewCount > 0)
        <div class="absolute bottom-2 right-2 flex items-center gap-2 bg-gray-950/70 text-white text-[11px] font-bold px-2.5 py-1 rounded-full backdrop-blur-sm">
          @if($cardRealImageCount > 0)
            <span class="flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h1.5A2.25 2.25 0 0 1 6.628 3.982l.44.882A1 1 0 0 0 7.965 5.5h4.07a1 1 0 0 0 .897-.618l.44-.882A2.25 2.25 0 0 1 15.25 3h1.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5ZM10 8a3.25 3.25 0 1 0 0 6.5A3.25 3.25 0 0 0 10 8Z" clip-rule="evenodd"/></svg>{{ $cardRealImageCount }}</span>
          @endif
          @if($cardReviewCount > 0)
            <span class="flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 2c-4.418 0-8 2.91-8 6.5 0 1.593.696 3.05 1.843 4.171-.176.822-.583 1.665-1.256 2.408a.5.5 0 0 0 .421.83c1.518-.14 2.68-.665 3.51-1.199A9.49 9.49 0 0 0 10 15c4.418 0 8-2.91 8-6.5S14.418 2 10 2Z" clip-rule="evenodd"/></svg>({{ $cardReviewCount }})</span>
          @endif
        </div>
      @endif
    </div>
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        @if($section)<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $section['title'] }}</span>@endif
        @if($facility->is_claimed)<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Onaylı</span>
        @elseif($facility->is_broker_managed)<span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">🤝 Anlaşmalı</span>@endif
        @if($facility->hasFastResponseBadge())<span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">⚡ Hızlı Yanıt</span>
        @elseif($promiseBadge = $facility->responseTimePromiseBadge())<span class="bg-blue-50 text-blue-600 text-xs font-semibold px-2 py-0.5 rounded-full">⚡ {{ $promiseBadge }}</span>@endif
        @if($viewsBadge = $facility->viewsBoostBadge())<span class="bg-amber-50 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">👀 {{ $viewsBadge }}</span>@endif
        @if($facility->hasFreeChildAccidentInsurance())<span class="text-xs font-black px-2 py-0.5 rounded-full" style="background:#0f766e;color:#ffffff;">🛡️ Ücretsiz Ferdi Kaza Sigortası</span>@endif
        @if($ministryBadge = $facility->ministryVerificationBadge())<span class="{{ $ministryBadge['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full">{{ $ministryBadge['label'] }}</span>@endif
        {{-- 30 Eylul 2026: kullanicinin talebi - "hepsi tek kart tipi"
             birlestirmesinde (bkz. ust taraftaki ayni tarihli yorum) bu
             rozet eski, artik kaldirilan ozel anasayfa kart sablonunda
             kalmisti, ortak karta hic tasinmamisti - buraya eklendi. --}}
        @if($facility->site_visited_at)<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-0.5 rounded-full">✓ Yerinde Ziyaret</span>@endif
        {{-- 22 Eylul 2026: kullanicinin talebi - abonelik odemesi GECERLI
             olan (ama sahiplenilmemis/anlasmasiz) kurumlar artik anlasmali/
             sahiplenilmis kurumlarla ayni buyuk kartta, "Yardımsever Kurum"
             rozetiyle gosterilir. --}}
        @if($isActiveSubscriberCard)
          @include('themes._shared.partials.membership-badge', ['facility' => $facility])
        @endif
        @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
        @isset($badge){!! $badge !!}@endisset
      </div>
      <h2 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h2>
      <p class="text-sm text-gray-500 mb-1 flex items-center gap-1.5 flex-wrap">
        <span>{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</span>
      </p>
      @php($totalReviewCount = $facility->hidesPatientReviews() ? 0 : $facility->totalReviewCount())
      @php($cardDisplayViews = $facility->monthlyDisplayViewsCount())
      @if($cardDisplayViews > 0)
        <p class="text-xs text-gray-500 mb-3 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M10 3.5c-4.5 0-7.5 3.5-8.5 6.5 1 3 4 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4-6.5-8.5-6.5Zm0 10.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/><circle cx="10" cy="10" r="2"/></svg>
          {{ number_format($cardDisplayViews, 0, ',', '.') }} kez görüntülendi
        </p>
      @else
        <div class="mb-3"></div>
      @endif
      <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $facility->publicDescription() }}</p>
      <div class="flex items-center justify-between">
        <span class="flex items-center gap-1.5 flex-wrap">
          @if($facility->rating > 0 && ! $facility->hidesPatientReviews())
            <span class="text-amber-700 font-black text-sm">★ {{ number_format($facility->rating, 1) }}</span>
            @if($facility->source === 'google_maps_veri_cekici')<span class="text-[10px] text-gray-500 font-semibold -ml-1">(Google)</span>@endif
          @endif
          {{-- 28 Eylul 2026: kullanicinin talebi - yorum sayisi artik il/ilce
               satirinda degil, puanin HEMEN yaninda gosterilir. --}}
          @if($totalReviewCount > 0)
            <span class="text-gray-500 text-xs font-semibold">💬 {{ $totalReviewCount }}</span>
          @endif
        </span>
        {{-- 7 Eylul 2026: kullanicinin talebi - bkz. price-locked.blade.php ayni tarihli yorum. --}}
        @if(session('family_user_id'))
          @if($facility->price_min)<span class="text-gray-700 font-black text-sm">{{ number_format($facility->price_min,0,',','.') }} TL<span class="text-gray-500 font-normal">/ay</span></span>@else<span class="text-primary text-sm font-black">Fiyat iste</span>@endif
        @else
          @include('themes._shared.partials.price-locked', ['inLink' => true])
        @endif
      </div>
    </div>
  </a>
  {{-- 30 Ekim 2026: kullanıcının talebi - site satışı yapılmış (yayındaki) kurumun sitesi. --}}
  @if($kartSiteAdresi = \App\Models\FacilityWebsite::liveUrlMap()[(int) $facility->id] ?? null)
    <div class="px-4 pb-2">
      <a href="{{ $kartSiteAdresi }}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:800;color:#0d6e6a;text-decoration:none;word-break:break-all;">🌐 {{ \App\Models\FacilityWebsite::displayAddress($kartSiteAdresi) }}</a>
    </div>
  @endif
  {{-- 4 Eylul 2026: kullanicinin acik talebi - anlaşmalı kurumlar sadece
       gorsel olarak degil, AILEYI GERCEKTEN TEKLIF ISTEMEYE tesvik edecek
       sekilde one cikmali. Buyuk, tek basina, ikonlu bir birincil CTA -
       diger 4 buton (İncele/Karşılaştır/Toplu Fiyat Al dahil "Fiyat Al")
       kucuk/ikincil kalir, tekrar etmez.
       22 Eylul 2026: kullanicinin talebi - aktif abone (Yardımsever Kurum)
       kurumlar da ayni tesvik edici CTA'yi alir. --}}
  @if($facility->is_broker_managed || $isActiveSubscriberCard)
    <div class="px-4 pb-3">
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="flex flex-col items-center justify-center rounded-lg px-2 py-2.5 text-sm font-black text-white text-center leading-tight bg-gradient-to-r from-blue-600 to-sky-500 shadow-md shadow-blue-400/40 hover:shadow-lg hover:shadow-blue-400/60 transition">
        <span>💬 Hemen Ücret Sor</span>
        <span class="font-normal opacity-90 text-xs">Öncelikli Yanıt</span>
      </a>
    </div>
  @endif
  {{-- 4 Ekim 2026: kullanicinin bildirdigi gercek hata - "Karşılaştır" tek
       kelime oldugu icin dogal satir kirma noktasi yok; CSS Grid'de hucreler
       varsayilan min-width:auto oldugundan (icerigin kirilamayan en genis
       kelimesi kadar) dar ekranlarda metin kutudan tasiyordu. min-w-0 hucreyi
       sutun genisligine kuculmeye zorlar, break-words gerekirse kelimeyi
       kendi icinde kirar.
       5 Ekim 2026: kullanicinin bildirdigi gercek hata - yukaridaki ikisi
       tasmayi teknik olarak engelliyordu ama "Karşılaştır" cok dar ekranlarda
       kenara YAPISIYOR gorunuyordu (gercek piksel payı yoktu). text-sm (14px)
       yerine text-xs (12px) + px-1 (daha az yatay dolgu) ile en uzun kelime
       icin HER ekranda rahat bir bosluk payi birakilir - artik sinirda degil,
       acikca sigacak kadar kucuk render edilir. --}}
  <div class="px-4 pb-4 grid grid-cols-2 gap-2">
    <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="min-w-0 break-words rounded-lg border border-gray-200 px-1 py-2 text-xs font-black text-gray-700 text-center hover:bg-gray-50">İncele</a>
    @if($facility->is_broker_managed)
      <button type="button" class="js-engagement-toggle min-w-0 break-words rounded-lg border-2 border-blue-400 px-1 py-2 text-xs font-black text-blue-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      <button type="button" class="js-engagement-toggle min-w-0 break-words rounded-lg border border-gray-200 px-1 py-2 text-xs font-black text-gray-700 hover:bg-gray-50 col-span-2" data-mode="bulk-quote" data-id="{{ $facility->id }}">Toplu Fiyat Al</button>
    @else
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="min-w-0 break-words rounded-lg px-1 py-2 text-xs font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Fiyat Al</a>
      <button type="button" class="js-engagement-toggle min-w-0 break-words rounded-lg border-2 border-blue-400 px-1 py-2 text-xs font-black text-blue-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      <button type="button" class="js-engagement-toggle min-w-0 break-words rounded-lg border border-gray-200 px-1 py-2 text-xs font-black text-gray-700 hover:bg-gray-50" data-mode="bulk-quote" data-id="{{ $facility->id }}">Toplu Fiyat Al</button>
    @endif
  </div>
</article>
