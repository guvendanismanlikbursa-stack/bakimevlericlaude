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
        <h2 class="text-3xl font-black text-gray-950">{{ $filteredFacilities->total() + $filteredFeatured->count() + $filteredRecommended->count() }} kurum bulundu</h2>
      </div>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Tam sayfada aç →</a>
    </div>

    {{-- 14 Eylul 2026: kullanicinin talebi - il secilince (filtre moduna
         gecildiginde) harita da o ile gore guncellenip burada gorunmeli. --}}

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


    @if($filteredFacilities->isEmpty() && $filteredFeatured->isEmpty() && $filteredRecommended->isEmpty())
      <div class="bg-white border border-dashed rounded-xl p-10 text-center text-gray-500">
        <p>Kriterlere uygun kurum bulunamadı.</p>
        <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="text-primary underline mt-2 inline-block">Filtreleri temizle</a>
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
{{-- 28 Agustos 2026: kullanicinin talebi - Öne Çıkanlar (ve ardindan
     Sahiplenilmiş Kurumlar/Ön Kayıtlı Kurumlar) filtrenin HEMEN ALTINDA,
     arada "Bilgi merkezi" tanitim blogu OLMADAN gorunmeli - o blog asagiya,
     bu 3 bolumden SONRA tasindi. --}}
<section class="max-w-6xl mx-auto px-4 py-12">
  <div>
    <div id="one-cikanlar">
      <div class="flex items-end justify-between mb-6"><div><div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Seçilmiş kurumlar</div><h2 class="text-3xl font-black text-gray-950">ÖNERİLEN KURUMLAR</h2></div><a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="text-sm font-black" style="color: {{ $colors['primary'] }};">Listeye git →</a></div>
      {{-- 31 Agustos 2026: kullanicinin bildirdigi gercek fark - bu marka
           tek basina kucuk, yatay (sm:grid-cols-[150px_1fr]) kartlar
           kullaniyordu, diger 2 marka (bakimeviara/bakimevibul) buyuk,
           dikey (gorsel ustte, md:grid-cols-3) kart kullaniyor. Ayni
           gorsel dile getirildi - 3 marka artik tutarli buyuklukte. --}}
      <div class="grid grid-cols-2 gap-3 md:gap-5 md:grid-cols-4">
        @forelse($featured as $facility)
          {{-- 4 Eylul 2026: kullanicinin talebi - anlaşmalı kurumlara ayri
               bir "Hemen Ücret Sor" CTA'si eklenecekti, ama tum kart TEK bir
               <a> idi - icine ikinci bir <a> koymak GECERSIZ ic ice link
               olurdu (bkz. form.blade.php'deki ic ice <form> hatasi, ayni
               sinif hata). Dis eleman <div>'e cevrildi, tiklanabilir alan
               ic <a>'ya tasindi, CTA kardes bir <a> olarak eklendi. --}}
          {{-- 28 Eylul 2026: kullanicinin talebi - kosegen ("diagonal") rozet
               seridi ve alt alta yiginlan rozet listesi mobilde "sacma sapan"
               gorunuyordu (okul.com.tr referansi verildi). Kosegen serit
               kaldirildi, gorselin sol-ust kosesinde kucuk/duz rozetlere,
               konum bilgisi de gorselin ALTINA (okul.com.tr'deki gibi
               gradyanli beyaz yazi) tasindi. Icerikteki rozet yigini da
               TEK bir kisa satira sigacak sekilde kisaltildi. --}}
          @php
            $isBrokerCard = $facility->is_broker_managed;
            $cornerLabel = ($isBrokerCard || $facility->isActiveSubscriber()) ? '⭐ ÖNERİLEN' : '⭐ ÖNE ÇIKAN';
          @endphp
          <div class="group bg-white rounded-xl overflow-hidden transition border border-gray-100 shadow-sm hover:shadow-lg">
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
              <div class="p-3">
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
                {{-- 28 Eylul 2026: kullanicinin talebi - goruntulenme sayisi
                     sahiplenilmis kurumlarda (facility-card.blade.php) zaten
                     vardi, anlaşmalı/abone kurumlarin kullandigi bu ayri
                     kart sablonunda hic yoktu. --}}
                @if($facility->monthlyDisplayViewsCount() > 0)
                  <p class="text-[11px] text-gray-400 mb-2 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path d="M10 3.5c-4.5 0-7.5 3.5-8.5 6.5 1 3 4 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4-6.5-8.5-6.5Zm0 10.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/><circle cx="10" cy="10" r="2"/></svg>
                    {{ number_format($facility->monthlyDisplayViewsCount(), 0, ',', '.') }} kez görüntülendi
                  </p>
                @endif
                <div class="flex items-center justify-between text-sm">{{-- 14 Agustos 2026: kullanicinin talebi - puani 0 olan (ozellikle Google'da henuz yorumu olmayan kucuk isletmeler) kurumlarda "★ 0.0" gostermek "veri bozuk" hissi veriyordu; diger kartlarda oldugu gibi puan yoksa yildiz satiri hic gosterilmiyor. --}}@if($facility->rating > 0)<span class="text-amber-700 font-black">★ {{ number_format($facility->rating, 1) }}</span>@else<span></span>@endif
                @if(session('family_user_id'))
                  <span class="font-black text-gray-800">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span>
                @else
                  @include('themes._shared.partials.price-locked')
                @endif
                </div>
              </div>
            </a>
            @if($isBrokerCard)
              <div class="px-3 pb-3 -mt-1">
                <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}#teklif-talebi" class="block rounded-lg px-3 py-2 text-xs font-black text-white text-center bg-gradient-to-r from-blue-600 to-sky-500 shadow-sm hover:shadow-md transition">
                  💬 Hemen Ücret Sor
                </a>
              </div>
            @endif
          </div>
        @empty
          <div class="md:col-span-3 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölümde henüz önerilen kurum yok.</div>
        @endforelse
      </div>
      @if($featured->hasPages())<div class="mt-6">{{ $featured->onEachSide(1)->fragment('one-cikanlar')->links() }}</div>@endif
    </div>
  </div>
</section>

{{-- 10 Eylul 2026: kullanicinin bildirdigi hata - Öne Çıkanlar sayfalamasinda
     "2. sayfa"ya gecince tarayici #one-cikanlar capasina dogru kaymiyor,
     sayfa ustunde/asagida (ön kayitli kurumlar hizasinda) aciliyordu. Bu,
     sayfa yuklendikten sonra (gorseller yerlesince) capaya KESIN olarak
     kaydirir - sadece featured_page parametresi varken calisir. --}}
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

{{-- 17 Eylul 2026: kullanicinin talebi - "özellikleri" kutusu daha once
     Öne Çıkanlar'in yanindaki dar kenar cubugundaydi, artik Ön Kayıtlı
     Kurumlar'in HEMEN USTUNDE, kendi genis bolumu olarak duruyor. --}}
<section class="max-w-6xl mx-auto px-4 pt-4">
  <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
    <div class="font-black text-gray-950 mb-3">{{ $section['title'] }} özellikleri</div>
    <div class="flex flex-wrap gap-2">
      @foreach(array_slice($section['features'], 0, 6) as $feature)
        <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'service' => $feature]) }}" class="rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700 hover:shadow-sm">{{ $feature }}</a>
      @endforeach
    </div>
  </div>
</section>

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
