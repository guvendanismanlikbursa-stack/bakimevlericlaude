@php
  $section = service_section_for_scope($facility->category->brand_scope);
  $cardImage = facility_card_image($facility, $section);
  $ownImagePath = $facility->relationLoaded('images') ? $facility->images->first()?->path : null;
  // 12 Agustos 2026: kullanicinin talebi - listeleme "sahte" hissettiriyordu
  // cunku cogu kart ayni ornek gorseli kullaniyor; gorseli gercek kurum
  // yuklemesi olmayan kartlarda kucuk, durust bir "Ornek gorsel" etiketi
  // gosteriyoruz - gizlemek yerine seffaf olmak guveni artirir.
  $isSampleImage = ! $ownImagePath || str_starts_with($ownImagePath, 'facilities/demo/');
  // Sahiplenilmemis (is_claimed=false) HER kurum on kayitli sayilir; kaynagi
  // veri cekici olsun ya da olmasin, karsilastirma/fiyat talebi gibi
  // aksiyonlar sadece sahiplenilmis kurumlarda anlamli.
  $isPreRegisteredCard = ! $facility->is_claimed;
@endphp
{{--
  14 Agustos 2026: kullanicinin talebi - "one cikan" kurumlar gorsel
  olarak da fark edilmeli, diger kartlarla ayni gorunmemeli (aksi halde
  kurum yetkilisi "one cikan"in gercek bir deger oldugunu hissetmez).
  Altin/amber tonlu bir cerceve + kose seridi ekleniyor - marka rengine
  bagli degil (premium = altin, evrensel bir dil), digerlerinden aciyor.
--}}
<article class="bg-white rounded-xl overflow-hidden group relative transition
  {{ $facility->is_featured
      ? 'border-2 border-amber-300 shadow-lg shadow-amber-200/50 hover:shadow-xl hover:shadow-amber-300/50'
      : 'border border-gray-100 shadow-sm hover:shadow-lg' }}">
  @if($facility->is_featured)
    <div class="absolute top-3 -left-9 z-10 w-36 rotate-[-45deg] bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-center text-[10px] font-black text-amber-950 py-1 shadow-md tracking-wider pointer-events-none">
      ⭐ ÖNE ÇIKAN
    </div>
  @endif
  @unless($isPreRegisteredCard)
    <button type="button" class="js-engagement-toggle absolute top-2 right-2 z-10 w-8 h-8 rounded-full bg-white/90 shadow flex items-center justify-center text-lg text-gray-400" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}" data-icon="1" aria-label="Favori">♥</button>
  @endunless
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
    <div class="h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center relative">
      <img src="{{ $cardImage }}" alt="{{ $facility->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
      @if($isSampleImage)
        <span class="absolute bottom-2 left-2 bg-gray-950/70 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">Örnek görsel</span>
      @endif
    </div>
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        @if($section)<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $section['title'] }}</span>@endif
        @if($facility->is_claimed)<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Onaylı</span>@endif
        @if($facility->hasFastResponseBadge())<span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">⚡ Hızlı Yanıt</span>@endif
        @if($ministryBadge = $facility->ministryVerificationBadge())<span class="{{ $ministryBadge['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full">{{ $ministryBadge['label'] }}</span>@endif
        @if($isPreRegisteredCard)<span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Ön Kayıtlı</span>@endif
        @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
        @isset($badge){!! $badge !!}@endisset
      </div>
      <h2 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h2>
      <p class="text-sm text-gray-500 mb-1">{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</p>
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
          <span class="text-amber-500 font-black text-sm">★ {{ number_format($facility->rating, 1) }}</span>
          @if($facility->source === 'google_maps_veri_cekici')<span class="text-[10px] text-gray-400 font-semibold -ml-1">(Google)</span>@endif
        @else
          <span></span>
        @endif
        @if($facility->price_min)<span class="text-gray-700 font-black text-sm">{{ number_format($facility->price_min,0,',','.') }} TL<span class="text-gray-400 font-normal">/ay</span></span>@else<span class="text-primary text-sm font-black">Fiyat iste</span>@endif
      </div>
    </div>
  </a>
  <div class="px-4 pb-4 grid grid-cols-2 gap-2">
    @if($isPreRegisteredCard)
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 text-center hover:bg-gray-50">İncele</a>
      <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="rounded-lg px-3 py-2 text-sm font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Sahiplen</a>
    @else
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 text-center hover:bg-gray-50">İncele</a>
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="rounded-lg px-3 py-2 text-sm font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Fiyat Al</a>
      <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50" data-mode="bulk-quote" data-id="{{ $facility->id }}">Toplu Fiyat Al</button>
    @endif
  </div>
</article>
