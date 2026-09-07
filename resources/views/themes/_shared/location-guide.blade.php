@extends('layouts.brand')
@section('content')
@php
  $theme = $brand['theme'];
  $colors = $section['theme'];
  $placeTitle = $districtName ? $city->name . ' / ' . $districtName : $city->name;
  // 24 Agustos 2026: kullanicinin bildirdigi gercek hata - kategori
  // secilmemisse baslik $section['title']'a ("Yasli Bakim" gibi) duserdu,
  // ama kimse bu sekilde aramiyor - gercek arama terimi icin bkz.
  // config/brands.php seo_title alani ("Bakimevi", "Kres" vb.).
  $topicTitle = $category->name ?? $section['seo_title'] ?? $section['title'];
  $title = $placeTitle . ' ' . $topicTitle . ' kurumları';
  $breadcrumbItems = [
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => $section['title'], 'url' => brand_route('location-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug])],
  ];
  if ($category) {
      $breadcrumbItems[] = ['name' => $category->name, 'url' => brand_route('location-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $category->slug])];
  }
  if ($districtName) {
      $breadcrumbItems[] = ['name' => $districtName, 'url' => url()->current()];
  }
@endphp
@section('title', $title)
@section('og_title', $title)
@section('meta_description', $brand['name'].' ile '.$placeTitle.' bölgesindeki '.$topicTitle.' kurumlarını karşılaştırın, ücretsiz teklif alın.')
@section('og_image', seo_og_image($section))
@if($section['slug'] !== ($brand['default_section'] ?? null))
  {{-- bkz. layouts/brand.blade.php - 3 marka ayni envanteri paylastigi icin
       bir markanin KENDI bolumu disindaki rehber sayfalari kopya icerik
       riski tasir, bu yuzden Google'a bildirilmez (site icinde yine gezilebilir). --}}
  @section('robots_meta', 'noindex,follow')
@endif
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => $breadcrumbItems])
  @include('themes._shared.partials.itemlist-jsonld', ['facilities' => $facilities])
  @include('themes._shared.partials.faqpage-jsonld', ['questions' => $content['faq_preview'] ?? []])
@endsection

<section class="{{ $theme === 'bakimevleri' ? 'bg-gray-950 text-white' : ($theme === 'bakimeviara' ? 'bg-white' : 'bg-gray-50') }} border-b border-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-10 grid lg:grid-cols-[1fr_340px] gap-8 items-start">
    <div>
      <a href="{{ brand_route('home', ['bolum' => $section['slug']]) }}" class="text-sm font-black {{ $theme === 'bakimevleri' ? 'text-white/70' : 'text-primary' }}">← Bölüm ana sayfası</a>
      <div class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-black mt-5 mb-4" style="background: {{ $theme === 'bakimevleri' ? 'rgba(255,255,255,.10)' : $colors['soft'] }}; color: {{ $theme === 'bakimevleri' ? '#fff' : $colors['primary'] }};">
        @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
        <span>İl / ilçe rehberi</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black leading-tight {{ $theme === 'bakimevleri' ? 'text-white' : 'text-gray-950' }}">{{ $title }}</h1>
      <p class="mt-4 text-lg leading-relaxed {{ $theme === 'bakimevleri' ? 'text-white/70' : 'text-gray-600' }}">{{ $placeTitle }} bölgesinde {{ $topicTitle }} arayan ziyaretçiler için kurum seçme kriterleri, filtre önerileri ve öne çıkan seçenekler.</p>
    </div>
    <div class="{{ $theme === 'bakimevleri' ? 'bg-white/10 border-white/15 text-white' : 'bg-white border-gray-100 text-gray-800' }} rounded-xl border p-5 shadow-sm">
      <div class="text-sm font-black mb-3" style="color: {{ $theme === 'bakimevleri' ? '#fff' : $colors['primary'] }};">Hızlı aksiyon</div>
      <div class="grid gap-2">
        <a href="{{ brand_route('facilities.index', array_filter(['bolum' => $section['slug'], 'city' => $city->slug, 'district' => $districtName])) }}" class="rounded-lg px-4 py-3 text-sm font-black text-white text-center" style="background: {{ $colors['primary'] }};">Bu bölgedeki kurumları listele</a>
        {{-- 26 Agustos 2026: kullanicinin talebi - aileler once fiyat
             arastiriyor, bu yuzden fiyat-odakli kardes sayfaya (bkz.
             PriceGuideController) buradan da guclu bir ic link verilir. --}}
        <a href="{{ $category ? brand_route('price-guide.category', array_filter(['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $category->slug, 'districtSlug' => $districtName ? Str::slug($districtName) : null])) : brand_route('price-guide.show', array_filter(['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'districtSlug' => $districtName ? Str::slug($districtName) : null])) }}" class="rounded-lg border px-4 py-3 text-sm font-black text-center {{ $theme === 'bakimevleri' ? 'border-white/20 text-white' : 'border-gray-200 text-gray-700' }}">{{ $placeTitle }} fiyatlarını gör</a>
        <a href="{{ brand_route('engagement.wizard', ['bolum' => $section['slug']]) }}" class="rounded-lg border px-4 py-3 text-sm font-black text-center {{ $theme === 'bakimevleri' ? 'border-white/20 text-white' : 'border-gray-200 text-gray-700' }}">Karar sihirbazına git</a>
      </div>
    </div>
  </div>
</section>

<section class="max-w-6xl mx-auto px-4 py-10">
  <div class="grid lg:grid-cols-[0.85fr_1.15fr] gap-6">
    <aside class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm h-fit">
      <div class="font-black text-gray-950 mb-3">Yakın ilçe bağlantıları</div>
      <div class="flex flex-wrap gap-2">
        @foreach($nearDistricts as $district)
          <a href="{{ $category ? brand_route('location-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $category->slug, 'districtSlug' => Str::slug($district)]) : brand_route('location-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'districtSlug' => Str::slug($district)]) }}" class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:shadow-sm">{{ $district }}</a>
        @endforeach
      </div>
      @if($sectionCategories->isNotEmpty())
        <div class="font-black text-gray-950 mb-3 mt-6">Kategoriye göre keşfet</div>
        <div class="flex flex-wrap gap-2">
          <a href="{{ brand_route('location-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold hover:shadow-sm {{ ! $category ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">Tümü</a>
          @foreach($sectionCategories as $sectionCategory)
            <a href="{{ brand_route('location-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $city->slug, 'categorySlug' => $sectionCategory->slug]) }}" class="rounded-lg px-3 py-2 text-xs font-bold hover:shadow-sm {{ $category?->id === $sectionCategory->id ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">{{ $sectionCategory->name }}</a>
          @endforeach
        </div>
      @endif
    </aside>
    <div>
      <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mb-6">
        <h2 class="text-2xl font-black text-gray-950 mb-3">{{ $placeTitle }} için seçim kontrolü</h2>
        @if(!empty($guideContent['intro']))
          <p class="text-gray-700 leading-relaxed font-semibold">{{ $guideContent['intro'] }}</p>
        @endif
        @if($category && !empty($category->seo_description))
          <p class="text-gray-600 leading-relaxed mt-3">{{ $category->seo_description }}</p>
        @endif
        <p class="text-gray-600 leading-relaxed mt-3">{{ $content['intro'] ?? '' }}</p>
        <div class="grid sm:grid-cols-2 gap-3 mt-5">
          @foreach(($content['checks'] ?? []) as $check)
            <div class="rounded-lg px-3 py-3 text-sm font-semibold" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">{{ $check }}</div>
          @endforeach
        </div>
      </div>

      {{-- 26 Agustos 2026: kullanicinin talebi - "il+kategori" aramalarinda
           gorunmemenin bir nedeni govde metninin binlerce sayfada birebir
           ayni olmasiydi. Bu kutu GERCEK veriden (bkz. LocationGuideController::
           priceRangeFor()) hesaplanir, hicbir iki il/ilce/kategori sayfasi
           ayni degeri gostermez - fiyat girilmis kurum yoksa hic gorunmez. --}}
      @if($priceRange)
        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mb-6">
          <h2 class="text-lg font-black text-gray-950 mb-2">{{ $placeTitle }} {{ $topicTitle }} fiyat aralığı</h2>
          <p class="text-gray-700 leading-relaxed">
            @if($priceRange['min'] === $priceRange['max'])
              Bu bölgede fiyat bilgisi paylaşan kurumlarda aylık ücret <strong>{{ number_format($priceRange['min'], 0, ',', '.') }} TL</strong> civarındadır.
            @else
              Bu bölgede fiyat bilgisi paylaşan {{ $priceRange['priced_count'] }} kurumda aylık ücretler <strong>{{ number_format($priceRange['min'], 0, ',', '.') }} TL</strong> ile <strong>{{ number_format($priceRange['max'], 0, ',', '.') }} TL</strong> arasında değişiyor.
            @endif
            Kesin fiyat oda tipi, bakım yoğunluğu ve dahil hizmetlere göre değişebilir; net teklif için kuruma doğrudan ulaşmanızı öneririz.
          </p>
        </div>
      @endif

      @if(!empty($content['faq_preview']))
        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mb-6">
          <h2 class="text-lg font-black text-gray-950 mb-4">{{ $placeTitle }} hakkında sıkça sorulan sorular</h2>
          <div class="space-y-4">
            @foreach($content['faq_preview'] as [$question, $answer])
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
              @if($facility->rating > 0)<span class="text-amber-700 font-black">★ {{ number_format($facility->rating, 1) }}</span>@else<span></span>@endif
              @if(session('family_user_id'))
                <span class="font-black text-gray-700">{{ $facility->price_min ? number_format($facility->price_min,0,',','.') . ' TL' : 'Fiyat iste' }}</span>
              @else
                @include('themes._shared.partials.price-locked')
              @endif
            </div>
          </a>
        @empty
          <div class="md:col-span-2 bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Bu bölge için kayıtlı kurum bulunamadı. Yakın ilçelere veya genel listeye bakabilirsiniz.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>
@endsection