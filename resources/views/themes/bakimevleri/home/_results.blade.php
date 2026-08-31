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
{{-- 28 Agustos 2026: kullanicinin talebi - Öne Çıkanlar (ve ardindan
     Sahiplenilmiş Kurumlar/Ön Kayıtlı Kurumlar) filtrenin HEMEN ALTINDA,
     arada "Bilgi merkezi" tanitim blogu OLMADAN gorunmeli - o blog asagiya,
     bu 3 bolumden SONRA tasindi. --}}
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
    <div id="one-cikanlar">
      <div class="flex items-end justify-between mb-6"><div><div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Seçilmiş kurumlar</div><h2 class="text-3xl font-black text-gray-950">Öne çıkanlar</h2></div><a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Listeye git →</a></div>
      {{-- 31 Agustos 2026: kullanicinin bildirdigi gercek fark - bu marka
           tek basina kucuk, yatay (sm:grid-cols-[150px_1fr]) kartlar
           kullaniyordu, diger 2 marka (bakimeviara/bakimevibul) buyuk,
           dikey (gorsel ustte, md:grid-cols-3) kart kullaniyor. Ayni
           gorsel dile getirildi - 3 marka artik tutarli buyuklukte. --}}
      <div class="grid md:grid-cols-3 gap-5">
        @forelse($featured as $facility)
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="group bg-white rounded-xl overflow-hidden transition relative border-2 border-amber-300 shadow-lg shadow-amber-200/50 hover:shadow-xl hover:shadow-amber-300/50">
            <div class="absolute top-3 -left-9 z-10 w-36 rotate-[-45deg] bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-center text-[10px] font-black text-amber-950 py-1 shadow-md tracking-wider pointer-events-none">⭐ ÖNE ÇIKAN</div>
            @php $cardImage = facility_card_image($facility, $section); @endphp
            <div class="h-48 overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};"><img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition"></div>
            <div class="p-5">
              <div class="flex items-center justify-between gap-2 mb-2">
                <div class="text-xs font-black" style="color: {{ $colors['primary'] }};">{{ $facility->category->name }}</div>
                {{-- 31 Agustos 2026: kullanicinin talebi - "yerinde ziyaret
                     edildi" bilgisi sadece kurum detay sayfasinda vardi,
                     asil trafigin oldugu ana sayfa/öne cikanlar kartinda
                     hic yoktu, kullanici ayirt edemiyordu. SADECE gercekten
                     isaretlenmisse (bkz. Facility::site_visited_at). --}}
                @if($facility->site_visited_at)
                  <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5 whitespace-nowrap">🤝 Yerinde Ziyaret Edildi</span>
                @endif
              </div>
              <h3 class="font-black text-gray-950 mb-1">{{ $facility->name }}</h3>
              <p class="text-sm text-gray-500 mb-4">{{ $facility->city->name }}</p>
              <div class="flex items-center justify-between text-sm">{{-- 14 Agustos 2026: kullanicinin talebi - puani 0 olan (ozellikle Google'da henuz yorumu olmayan kucuk isletmeler) kurumlarda "★ 0.0" gostermek "veri bozuk" hissi veriyordu; diger kartlarda oldugu gibi puan yoksa yildiz satiri hic gosterilmiyor. --}}@if($facility->rating > 0)<span class="text-amber-700 font-black">★ {{ number_format($facility->rating, 1) }}</span>@else<span></span>@endif<span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span></div>
            </div>
          </a>
        @empty
          <div class="md:col-span-3 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölüm için öne çıkan kurum eklenmedi.</div>
        @endforelse
      </div>
      @if($featured->hasPages())<div class="mt-6">{{ $featured->onEachSide(1)->fragment('one-cikanlar')->links() }}</div>@endif
    </div>
  </div>
</section>

@include('themes._shared.partials.claimed-facilities')
@include('themes._shared.partials.pre-registered-facilities')

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
@endif
