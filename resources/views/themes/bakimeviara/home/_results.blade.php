@php
  $section = $activeSection;
  $colors = $section['theme'];
  $content = site_section_content(current_brand()['slug'], $section['slug']);
@endphp
@if($isFiltering)
  <section class="max-w-6xl mx-auto px-4 py-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
      <div>
        <div class="text-sm font-bold mb-1" style="color: {{ $colors['primary'] }};">Filtre sonuçları</div>
        <h2 class="text-2xl font-black text-gray-950">{{ $filteredFacilities->total() + $filteredFeatured->count() + $filteredRecommended->count() }} kurum bulundu</h2>
      </div>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-bold" style="color: {{ $colors['primary'] }};">Tam sayfada aç →</a>
    </div>

    {{-- 14 Eylul 2026: bkz. bakimevleri temasindaki ayni tarihli yorum -
         il secilince (filtre moduna gecince) harita da guncellenip burada
         gorunmeli. --}}

    @if(request()->filled('q') && ! empty($sectionBreakdown ?? []))
      <div class="mb-6 flex flex-wrap items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
        <span class="font-bold text-gray-500">Tüm bölümlerde "{{ request('q') }}" için bulunanlar:</span>
        @foreach($sectionBreakdown as $item)
          <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 font-bold text-gray-700">
            {{ $item['title'] }} <span class="text-gray-400">·</span> {{ $item['total'] }}
          </span>
        @endforeach
      </div>
    @endif


    @if($filteredFacilities->isEmpty() && $filteredFeatured->isEmpty() && $filteredRecommended->isEmpty())
      <div class="bg-white border border-dashed rounded-2xl p-10 text-center text-gray-500">
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="underline mt-2 inline-block" style="color: {{ $colors['primary'] }};">Filtreleri temizle</a>
      </div>
    @endif

    @if($filteredRecommended->isNotEmpty())
      <h2 class="text-3xl font-black text-gray-950 mb-6">ÖNERİLEN KURUMLAR</h2>
      <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4 mb-8">
        @foreach($filteredRecommended as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
    @endif

  </section>
  <div class="max-w-6xl mx-auto px-4">
    @include('themes._shared.partials.broker-map')
  </div>
  @if($filteredFeatured->isNotEmpty())
  <section class="max-w-6xl mx-auto px-4 py-10 border-t border-gray-200" id="one-cikan-kurumlar">
    <h2 class="text-3xl font-black text-gray-950 mb-6">ÖNE ÇIKAN KURUMLAR</h2>
    <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4">
      @foreach($filteredFeatured as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
  </section>
  @endif
  <section class="max-w-6xl mx-auto px-4 pb-12">

    @if($filteredFacilities->isNotEmpty())
      <h2 class="text-3xl font-black text-gray-950 mb-6 mt-10">DİĞER KURUMLAR</h2>
      <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4">
        @foreach($filteredFacilities as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
      <div class="mt-8">{{ $filteredFacilities->links() }}</div>
    @endif
  </section>
@else
{{-- 26 Eylul 2026: kullanicinin talebi - Sahiplenilmis Kurumlar HER ZAMAN en ustte; Öne Çıkan/anlasmali/uye kurumlar altinda. --}}
<section class="max-w-6xl mx-auto px-4 py-12" id="one-cikanlar">
  <div class="text-center max-w-2xl mx-auto mb-8">
    <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">{{ $section['title'] }}</div>
    <h2 class="text-3xl font-black text-gray-950">ÖNERİLEN KURUMLAR</h2>
  </div>
  <div class="grid grid-cols-2 gap-3 md:gap-6 md:grid-cols-4">
    @forelse($featured as $facility)
      {{-- 4 Eylul 2026: kullanicinin talebi - bkz. bakimevleri temasindaki ayni tarihli yorum (ic ice <a> onlemek icin dis eleman <div>'e cevrildi). --}}
      {{-- 28 Eylul 2026: kullanicinin talebi - bkz. bakimevleri temasindaki
           ayni tarihli yorum (kosegen kurdele -> kucuk duz kose rozeti,
           konum gorselin altina, rozet yigini kisaltildi). --}}
      @php
        $isBrokerCard = $facility->is_broker_managed;
        $cornerLabel = ($isBrokerCard || $facility->isActiveSubscriber()) ? '⭐ ÖNERİLEN' : '⭐ ÖNE ÇIKAN';
      @endphp
      <div class="group bg-white rounded-2xl overflow-hidden transition border border-gray-100 shadow-sm hover:shadow-lg">
        @php $cardImage = facility_card_image($facility, $section); @endphp
        <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
          <div class="h-40 overflow-hidden relative" style="background: {{ $colors['soft'] }};">
            <img src="{{ $cardImage }}" alt="{{ $facility->imageAltText() }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition">
            <div class="absolute top-2 left-2 flex flex-col items-start gap-1">
              <span class="text-[10px] font-black px-2 py-1 rounded-md shadow-sm bg-gradient-to-r from-amber-400 to-amber-500 text-amber-950">{{ $cornerLabel }}</span>
              @if($isBrokerCard)
                <span class="text-[10px] font-black px-2 py-1 rounded-md shadow-sm bg-blue-600 text-white">🤝 Anlaşmalı</span>
              @endif
            </div>
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent pt-6 pb-1.5 px-2.5">
              <span class="text-white text-xs font-bold drop-shadow">{{ $facility->city->name }}@if($facility->district) / {{ $facility->district }}@endif</span>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-black text-gray-950 text-sm mb-1 line-clamp-2">{{ $facility->name }}</h3>
            <div class="flex items-center flex-wrap gap-1.5 mb-2 text-[11px]">
              <span class="font-bold" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</span>
              @if($facility->site_visited_at)
                <span class="font-bold text-emerald-700">· ✓ Yerinde Ziyaret</span>
              @endif
              @if($facility->isActiveSubscriber())
                <span class="font-bold" style="color:#a21caf;">· 💜 Yardımsever</span>
              @endif
              @if($facility->hasFreeChildAccidentInsurance())
                <span class="font-bold" style="color:#0d9488;">· 🛡️ Sigorta Dahil</span>
              @endif
            </div>
            @if($facility->monthlyDisplayViewsCount() > 0)
              <p class="text-[11px] text-gray-400 mb-2 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path d="M10 3.5c-4.5 0-7.5 3.5-8.5 6.5 1 3 4 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4-6.5-8.5-6.5Zm0 10.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/><circle cx="10" cy="10" r="2"/></svg>
                {{ number_format($facility->monthlyDisplayViewsCount(), 0, ',', '.') }} kez görüntülendi
              </p>
            @endif
            <div class="flex items-center justify-between text-sm">
              @if($facility->rating > 0)<span class="text-amber-700 font-black">★ {{ number_format($facility->rating, 1) }}</span>@else<span></span>@endif
              @if(session('family_user_id'))
                <span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Teklif al' }}</span>
              @else
                @include('themes._shared.partials.price-locked')
              @endif
            </div>
          </div>
        </a>
        @if($isBrokerCard)
          <div class="px-4 pb-4 -mt-1">
            <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="block rounded-lg px-3 py-2 text-xs font-black text-white text-center bg-gradient-to-r from-blue-600 to-sky-500 shadow-sm hover:shadow-md transition">
              💬 Hemen Ücret Sor
            </a>
          </div>
        @endif
      </div>
    @empty
      <div class="md:col-span-3 bg-white border border-dashed rounded-2xl p-8 text-center text-gray-500">Bu bölümde henüz önerilen kurum yok.</div>
    @endforelse
  </div>
  @if($featured->hasPages())<div class="mt-6">{{ $featured->onEachSide(1)->fragment('one-cikanlar')->links() }}</div>@endif
</section>

{{-- 10 Eylul 2026: bkz. bakimevleri temasindaki ayni tarihli yorum -
     Öne Çıkanlar sayfalamasinda capaya kesin kaydirma. --}}
@if(request()->has('featured_page'))
  <script>
    window.addEventListener('load', function () {
      var el = document.getElementById('one-cikanlar');
      if (el) { setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150); }
    });
  </script>
@endif

@include('themes._shared.partials.broker-map')
{{-- 26 Eylul 2026: sira = filtre, ONERILEN, harita, ONE CIKAN, Sahiplenilmis, On Kayitli --}}
@if($featuredOnly->isNotEmpty())
<section class="max-w-6xl mx-auto px-4 py-10 border-t border-gray-200" id="one-cikan-kurumlar">
  <h2 class="text-3xl font-black text-gray-950 mb-6">ÖNE ÇIKAN KURUMLAR</h2>
  <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4">
    @foreach($featuredOnly as $facility)
      @include('themes._shared.partials.facility-card', ['facility' => $facility])
    @endforeach
  </div>
</section>
@endif
@include('themes._shared.partials.claimed-facilities')
@include('themes._shared.partials.pre-registered-facilities')

<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="text-center max-w-3xl mx-auto mb-8">
    <div class="text-sm font-black mb-2" style="color: {{ $colors['primary'] }};">{{ $content['audience'] ?? '' }}</div>
    <h2 class="text-3xl font-black text-gray-950 mb-3">{{ $content['headline'] ?? 'Karar rehberi' }}</h2>
    <p class="text-gray-600 leading-relaxed">{{ $content['intro'] ?? '' }}</p>
  </div>
  <div class="grid lg:grid-cols-3 gap-4 mb-6">
    @foreach(($content['faq_preview'] ?? []) as $qa)
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="font-black text-gray-950 mb-2">{{ $qa[0] }}</div>
        <p class="text-sm text-gray-500 leading-relaxed">{{ $qa[1] }}</p>
      </div>
    @endforeach
  </div>
  <div class="flex flex-wrap justify-center gap-3">
    @foreach(($content['articles'] ?? []) as $article)
      <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-black hover:shadow-md transition" style="color: {{ $colors['primary'] }};">{{ $article['title'] }}</a>
    @endforeach
  </div>
</section>
@endif
