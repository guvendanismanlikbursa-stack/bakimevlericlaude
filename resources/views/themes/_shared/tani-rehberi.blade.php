@extends('layouts.brand')
@section('content')
@php
  $theme = $brand['theme'];
  $placeTitle = $districtName ? $city->name . ' / ' . $districtName : $city->name;
  $title = $placeTitle . ' ' . $tani['title'] . ' Merkezleri';
  $breadcrumbItems = [
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => $tani['title'], 'url' => url()->current()],
  ];
@endphp
@section('title', $title)
@section('og_title', $title)
@section('meta_description', $placeTitle.' bölgesinde '.$tani['title'].' desteği veren rehabilitasyon ve özel eğitim kurumlarını karşılaştırın.')
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => $breadcrumbItems])
  @include('themes._shared.partials.itemlist-jsonld', ['facilities' => $facilities])
  @include('themes._shared.partials.faqpage-jsonld', ['questions' => $faq])
@endsection

<section class="{{ $theme === 'bakimevleri' ? 'bg-gray-950 text-white' : 'bg-gray-50' }} border-b border-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-4xl md:text-5xl font-black leading-tight {{ $theme === 'bakimevleri' ? 'text-white' : 'text-gray-950' }}">{{ $title }}</h1>
    <p class="mt-4 text-lg leading-relaxed max-w-3xl {{ $theme === 'bakimevleri' ? 'text-white/70' : 'text-gray-600' }}">{{ $tani['intro'] }}</p>
    {{-- 13 Ekim 2026: kullanicinin acik talebi - "hicbir kurumun bu taniya
         baktigini UYDURMA" - burada durustce belirtiliyor, listedeki
         kurumlar genel rehabilitasyon/ozel egitim kurumlari, hangisinin bu
         spesifik taniya baktigini ogrenmek icin kuruma dogrudan sorulmasi
         onerilir. --}}
    <p class="mt-3 text-sm {{ $theme === 'bakimevleri' ? 'text-white/50' : 'text-gray-400' }}">Aşağıdaki liste, {{ $placeTitle }} bölgesindeki genel özel eğitim ve rehabilitasyon kurumlarını kapsar. Hangi kurumun {{ $tani['title'] }} alanında hizmet verdiğini netleştirmek için doğrudan kuruma ulaşmanızı öneririz.</p>
  </div>
</section>

<section class="max-w-6xl mx-auto px-4 py-10">
  <div class="grid lg:grid-cols-[0.85fr_1.15fr] gap-6">
    <aside class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm h-fit">
      <div class="font-black text-gray-950 mb-3">Yakın ilçe bağlantıları</div>
      <div class="flex flex-wrap gap-2">
        @foreach($nearDistricts as $district)
          <a href="{{ brand_route('tani-rehberi.show', ['taniSlug' => $taniSlug, 'citySlug' => $city->slug, 'districtSlug' => \Illuminate\Support\Str::slug($district)]) }}" class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:shadow-sm">{{ $district }}</a>
        @endforeach
      </div>
      <div class="font-black text-gray-950 mb-3 mt-6">Diğer tanılar</div>
      <div class="flex flex-wrap gap-2">
        @foreach(\App\Http\Controllers\Public\TaniRehberiController::TANILAR as $slug => $t)
          @if($slug !== $taniSlug)
            <a href="{{ brand_route('tani-rehberi.show', ['taniSlug' => $slug, 'citySlug' => $city->slug]) }}" class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:shadow-sm">{{ $t['title'] }}</a>
          @endif
        @endforeach
      </div>
    </aside>
    <div>
      {{-- 13 Ekim 2026: kullanicinin talebi - "kopya icerik olmamali" - bu
           blok GERCEK veriden (o il/ilce icin gercek fiyat araligi/kurum
           sayisi) hesaplanir, hicbir iki il/ilce/tani kombinasyonu ayni
           metni gostermez (bkz. TaniRehberiController ayni tarihli yorum). --}}
      @if($priceRange)
        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mb-6">
          <h2 class="text-lg font-black text-gray-950 mb-2">{{ $districtName ? $districtName.', '.$city->name : $city->name }} fiyat aralığı</h2>
          <p class="text-gray-700 leading-relaxed">
            @if($priceRange['min'] === $priceRange['max'])
              Bu bölgedeki kurumlarda aylık ücret <strong>{{ number_format($priceRange['min'], 0, ',', '.') }} TL</strong> civarındadır.
            @else
              Bu bölgede fiyat bilgisi paylaşan {{ $priceRange['priced_count'] }} kurumda aylık ücretler <strong>{{ number_format($priceRange['min'], 0, ',', '.') }} TL</strong> ile <strong>{{ number_format($priceRange['max'], 0, ',', '.') }} TL</strong> arasında değişiyor.
            @endif
            Kesin fiyat için kuruma doğrudan ulaşmanızı öneririz.
          </p>
        </div>
      @endif

      @if(!empty($faq))
        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mb-6">
          <h2 class="text-lg font-black text-gray-950 mb-4">Sıkça sorulan sorular</h2>
          <div class="space-y-4">
            @foreach($faq as [$question, $answer])
              <div>
                <div class="font-bold text-gray-900">{{ $question }}</div>
                <p class="text-gray-600 text-sm leading-relaxed mt-1">{{ $answer }}</p>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="grid md:grid-cols-2 gap-4">
        @forelse($facilities as $facility)
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm hover:shadow-md transition">
            <div class="text-xs font-black text-primary mb-2">{{ $facility->category->name }}</div>
            <h3 class="font-black text-gray-950">{{ $facility->name }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ $facility->city->name }} · {{ $facility->district }}</p>
            <div class="mt-3 flex items-center justify-between text-sm">
              @if($facility->rating > 0 && ! $facility->hidesPatientReviews())<span class="text-amber-700 font-black">★ {{ number_format($facility->rating, 1) }}</span>@else<span></span>@endif
            </div>
          </a>
        @empty
          <div class="md:col-span-2 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölge için kayıtlı kurum bulunamadı. Yakın ilçelere bakabilirsiniz.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>
@endsection
