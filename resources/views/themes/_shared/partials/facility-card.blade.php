@php
  $section = service_section_for_scope($facility->category->brand_scope);
  $cardImage = facility_card_image($facility, $section);
@endphp
<article class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden border border-gray-100 group">
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
    <div class="h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center">
      <img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
    </div>
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        @if($section)<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $section['title'] }}</span>@endif
        @if($facility->is_featured)<span class="badge-secondary text-white text-xs font-semibold px-2 py-0.5 rounded-full">Öne çıkan</span>@endif
        @if($facility->is_claimed)<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Onaylı</span>@endif
        @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
        @isset($badge){!! $badge !!}@endisset
      </div>
      <h2 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h2>
      <p class="text-sm text-gray-500 mb-3">{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</p>
      <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $facility->description }}</p>
      <div class="flex items-center justify-between">
        <span class="text-amber-500 font-black text-sm">★ {{ number_format($facility->rating, 1) }}</span>
        @if($facility->price_min)<span class="text-gray-700 font-black text-sm">{{ number_format($facility->price_min,0,',','.') }} TL<span class="text-gray-400 font-normal">/ay</span></span>@else<span class="text-primary text-sm font-black">Fiyat iste</span>@endif
      </div>
    </div>
  </a>
  <div class="px-4 pb-4 grid grid-cols-2 gap-2">
    @if(! $facility->is_claimed && $facility->source === 'google_maps_veri_cekici')
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 text-center hover:bg-gray-50">İncele</a>
      <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="rounded-lg px-3 py-2 text-sm font-black text-white text-center" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};">Sahiplen</a>
    @else
      <button type="button" class="js-engagement-toggle rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-700 hover:bg-gray-50" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}">Favori</button>
      <button type="button" class="js-engagement-toggle rounded-lg px-3 py-2 text-sm font-black text-white" style="background: {{ $section['theme']['primary'] ?? $brand['primary_color'] }};" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
    @endif
  </div>
</article>
