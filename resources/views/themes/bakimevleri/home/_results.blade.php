@php
  $section = $activeSection;
  $colors = $section['theme'];
  $content = site_section_content(current_brand()['slug'], $section['slug']);
@endphp
@if($isFiltering)
  {{-- 12 Agustos 2026: kullanicinin acik talebi - filtreleme yapildiginda
       tanitim ("Bilgi merkezi"/"Öne çıkanlar") blogu YERINE, ayni sayfada,
       ayni konumda GERCEK filtre sonuclari gorunur. --}}
  <section class="max-w-6xl mx-auto px-4 py-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
      <div>
        <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Filtre sonuçları</div>
        <h2 class="text-3xl font-black text-gray-950">{{ $filteredFacilities->total() + $filteredFeatured->count() }} kurum bulundu</h2>
      </div>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Tam sayfada aç →</a>
    </div>

    @if(request()->filled('q') && ! empty($sectionBreakdown ?? []))
      <div class="mb-6 flex flex-wrap items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
        <span class="font-black text-gray-500">Tüm bölümlerde "{{ request('q') }}" için bulunanlar:</span>
        @foreach($sectionBreakdown as $item)
          <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 font-black text-gray-700">
            {{ $item['title'] }} <span class="text-gray-400">·</span> {{ $item['total'] }}
          </span>
        @endforeach
      </div>
    @endif

    @if($filteredFeatured->isNotEmpty())
      <div class="text-sm font-black text-gray-500 mb-3">Öne çıkanlar</div>
      <div class="grid md:grid-cols-3 gap-5 mb-8">
        @foreach($filteredFeatured as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
    @endif

    @if($filteredFacilities->isEmpty() && $filteredFeatured->isEmpty())
      <div class="bg-white border border-dashed rounded-xl p-10 text-center text-gray-500">
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="text-primary underline mt-2 inline-block">Filtreleri temizle</a>
      </div>
    @else
      @if($filteredFacilities->isNotEmpty())
        <div class="text-sm font-black text-gray-500 mb-3">Filtreye uygun kurumlar</div>
        <div class="grid md:grid-cols-3 gap-5">
          @foreach($filteredFacilities as $facility)
            @include('themes._shared.partials.facility-card', ['facility' => $facility])
          @endforeach
        </div>
        <div class="mt-8">{{ $filteredFacilities->links() }}</div>
      @endif
    @endif
  </section>
@else
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid lg:grid-cols-[320px_1fr] gap-6">
    <div class="bg-gray-950 text-white rounded-xl p-6 h-fit">
      <div class="text-sm font-black text-white/70 mb-2">Bilgi merkezi</div>
      <h2 class="text-2xl font-black mb-3">{{ $content['headline'] ?? 'Kapsamlı rehber' }}</h2>
      <p class="text-sm text-white/72 leading-relaxed">{{ $content['intro'] ?? '' }}</p>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      @foreach(($content['articles'] ?? []) as $article)
        <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm hover:shadow-xl transition">
          <div class="text-xs font-black uppercase tracking-wide mb-2" style="color: {{ $colors['primary'] }};">Makale ve SSS</div>
          <h3 class="font-black text-gray-950 mb-2">{{ $article['title'] }}</h3>
          <p class="text-sm text-gray-500 leading-relaxed">{{ $article['summary'] }}</p>
        </a>
      @endforeach
      <div class="md:col-span-2 bg-white border border-gray-100 rounded-xl p-5">
        <div class="font-black text-gray-950 mb-3">Hızlı soru cevap</div>
        <div class="grid md:grid-cols-3 gap-3">
          @foreach(($content['faq_preview'] ?? []) as $qa)
            <div class="rounded-lg bg-gray-50 p-3"><div class="text-sm font-black text-gray-900">{{ $qa[0] }}</div><p class="text-xs text-gray-500 mt-1">{{ $qa[1] }}</p></div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="grid lg:grid-cols-[280px_1fr] gap-8">
    <aside class="bg-white border border-gray-100 rounded-xl p-5 h-fit shadow-sm">
      <div class="font-black text-gray-950 mb-3">{{ $section['title'] }} özellikleri</div>
      <div class="space-y-2">
        @foreach(array_slice($section['features'], 0, 6) as $feature)
          <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'service' => $feature]) }}" class="block rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700 hover:shadow-sm">{{ $feature }}</a>
        @endforeach
      </div>
    </aside>
    <div>
      <div class="flex items-end justify-between mb-6"><div><div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Seçilmiş kurumlar</div><h2 class="text-3xl font-black text-gray-950">Öne çıkanlar</h2></div><a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Listeye git →</a></div>
      <div class="grid md:grid-cols-2 gap-5">
        @forelse($featured as $facility)
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="group bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition grid sm:grid-cols-[150px_1fr]">
            @php $cardImage = facility_card_image($facility, $section); @endphp
            <div class="h-44 sm:h-full overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
            <div class="p-4"><div class="text-xs font-black mb-2" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</div><h3 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h3><p class="text-sm text-gray-500 mb-4">{{ $facility->city->name }}</p><div class="flex items-center justify-between text-sm"><span class="text-amber-500 font-black">★ {{ number_format($facility->rating, 1) }}</span><span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span></div></div>
          </a>
        @empty
          <div class="md:col-span-2 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölüm için öne çıkan kurum eklenmedi.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>

@include('themes._shared.partials.pre-registered-facilities')
@endif
