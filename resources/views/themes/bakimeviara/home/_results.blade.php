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
        <h2 class="text-2xl font-black text-gray-950">{{ $filteredFacilities->total() }} kurum bulundu</h2>
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

    @if($filteredFacilities->isEmpty())
      <div class="bg-white border border-dashed rounded-2xl p-10 text-center text-gray-500">
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="underline mt-2 inline-block" style="color: {{ $colors['primary'] }};">Filtreleri temizle</a>
      </div>
    @else
      {{-- 30 Eylul 2026: kullanicinin talebi - bkz. bakimevleri temasindaki
           ayni tarihli yorum - 3 ayri bolum TEK listeye birlesti. --}}
      <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4">
        @foreach($filteredFacilities as $facility)
          @include('themes._shared.partials.facility-card', ['facility' => $facility])
        @endforeach
      </div>
      <div class="mt-8">{{ $filteredFacilities->links() }}</div>
    @endif
  </section>
  <div class="max-w-6xl mx-auto px-4">
    @include('themes._shared.partials.broker-map')
  </div>
@else
<section class="max-w-6xl mx-auto px-4 py-12" id="one-cikanlar">
  <div class="text-center max-w-2xl mx-auto mb-8">
    <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">{{ $section['title'] }}</div>
    <h2 class="text-3xl font-black text-gray-950">Kurumlar</h2>
  </div>
  {{-- 30 Eylul 2026: kullanicinin talebi - bkz. bakimevleri temasindaki ayni
       tarihli yorum - 4 ayri bolum TEK listeye, TEK paylasilan kart
       sablonuna (facility-card.blade.php) birlesti. --}}
  <div class="grid grid-cols-2 gap-3 md:gap-6 md:grid-cols-4">
    @forelse($facilities as $facility)
      @include('themes._shared.partials.facility-card', ['facility' => $facility])
    @empty
      <div class="md:col-span-4 bg-white border border-dashed rounded-2xl p-8 text-center text-gray-500">Bu bölümde henüz kurum yok.</div>
    @endforelse
  </div>
  {{-- 30 Eylul 2026: kullanicinin talebi - anasayfada sayfalama YOK, sadece
       ilk 12 kurum - devami icin "Kurumlar" (filtre) sayfasina yonlendiren
       bir link. --}}
  <div class="mt-8 text-center">
    <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="inline-flex items-center gap-1.5 text-sm font-black" style="color: {{ $colors['primary'] }};">Devamını görmek için tıklayın →</a>
  </div>
</section>

@include('themes._shared.partials.broker-map')

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
