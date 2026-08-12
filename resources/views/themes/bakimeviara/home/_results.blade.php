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
        <h2 class="text-2xl font-black text-gray-950">{{ $filteredFacilities->total() + $filteredFeatured->count() }} kurum bulundu</h2>
      </div>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-bold" style="color: {{ $colors['primary'] }};">Tam sayfada aç →</a>
    </div>

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

    @if($filteredFeatured->isNotEmpty())
      <div class="text-sm font-bold text-gray-500 mb-3">Öne çıkanlar</div>
      <div class="grid md:grid-cols-3 gap-5 mb-8">
        @foreach($filteredFeatured as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
    @endif

    @if($filteredFacilities->isEmpty() && $filteredFeatured->isEmpty())
      <div class="bg-white border border-dashed rounded-2xl p-10 text-center text-gray-500">
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="underline mt-2 inline-block" style="color: {{ $colors['primary'] }};">Filtreleri temizle</a>
      </div>
    @elseif($filteredFacilities->isNotEmpty())
      <div class="text-sm font-bold text-gray-500 mb-3">Filtreye uygun kurumlar</div>
      <div class="grid md:grid-cols-3 gap-5">
        @foreach($filteredFacilities as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
      <div class="mt-8">{{ $filteredFacilities->links() }}</div>
    @endif
  </section>
@else
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
<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="text-center max-w-2xl mx-auto mb-8">
    <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">{{ $section['title'] }}</div>
    <h2 class="text-3xl font-black text-gray-950">Ailelerin incelediği kurumlar</h2>
  </div>
  <div class="grid md:grid-cols-3 gap-6">
    @forelse($featured as $facility)
      <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition">
        @php $cardImage = facility_card_image($facility, $section); @endphp
        <div class="h-48 overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
        <div class="p-5"><div class="text-xs font-bold mb-2" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</div><h3 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h3><p class="text-sm text-gray-500 mb-4">{{ $facility->city->name }}</p><div class="flex items-center justify-between text-sm"><span class="text-amber-500 font-black">★ {{ number_format($facility->rating, 1) }}</span><span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Teklif al' }}</span></div></div>
      </a>
    @empty
      <div class="md:col-span-3 bg-white border border-dashed rounded-2xl p-8 text-center text-gray-500">Bu bölüm için öne çıkan kurum eklenmedi.</div>
    @endforelse
  </div>
</section>
@endif
