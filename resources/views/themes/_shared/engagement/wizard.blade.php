@extends('layouts.brand')
@section('content')
@php
  $variant = $engagementStyle['variant'] ?? 'classic';
  $colors = $activeSection['theme'];
  $homeHref = brand_route('home', ['bolum' => $activeSection['slug']]);
  $resultHref = brand_route('facilities.index');
@endphp

@if($variant === 'warm')
<section class="relative overflow-hidden bg-white">
  <div class="absolute inset-x-0 top-0 h-72" style="background: {{ $colors['soft'] }};"></div>
  <div class="relative max-w-6xl mx-auto px-4 py-10">
    <div class="text-center max-w-3xl mx-auto mb-8">
      <div class="inline-flex items-center gap-2 rounded-full bg-white border border-gray-100 shadow-sm px-4 py-2 text-sm font-black" style="color: {{ $colors['primary'] }};">
        @include('themes._shared.partials.section-icon', ['section' => $activeSection, 'class' => 'w-4 h-4'])
        <span>Aile karar sihirbazı</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black text-gray-950 mt-5 mb-4">Size uygun kurumu birlikte daraltalım</h1>
      <p class="text-gray-600 text-lg">Birkaç seçim yapın; seçtiğiniz bölüme göre kurum listesi, rehberler ve teklif adımı aynı akışta açılsın.</p>
    </div>
@elseif($variant === 'dark')
<section class="bg-gray-950 text-white">
  <div class="max-w-6xl mx-auto px-4 py-12 grid lg:grid-cols-[0.9fr_1.1fr] gap-8 items-start">
    <div>
      <div class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-4 py-2 text-sm font-black mb-5">
        @include('themes._shared.partials.section-icon', ['section' => $activeSection, 'class' => 'w-4 h-4'])
        <span>Kurumsal seçim asistanı</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black leading-tight mb-4">Kriterleri seçin, seçenekleri sistemli karşılaştırın</h1>
      <p class="text-white/72 text-lg leading-relaxed">Bölüm, lokasyon, kurum türü, hizmet ve bütçe seçimleriyle daha isabetli listeye geçin.</p>
      <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
        <a href="{{ brand_route('engagement.compare') }}" class="rounded-lg bg-white text-gray-950 px-4 py-3 font-black">Karşılaştırma</a>
        <a href="{{ brand_route('engagement.favorites') }}" class="rounded-lg border border-white/20 px-4 py-3 font-black text-white">Favoriler</a>
      </div>
    </div>
@else
<section class="bg-white border-b border-emerald-100">
  <div class="max-w-6xl mx-auto px-4 py-10 grid lg:grid-cols-[0.85fr_1.15fr] gap-8 items-start">
    <div>
      <div class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-extrabold mb-5" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">
        @include('themes._shared.partials.section-icon', ['section' => $activeSection, 'class' => 'w-4 h-4'])
        <span>Hızlı karar sihirbazı</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-extrabold text-gray-950 leading-tight mb-4">Aradığınız kurumu daha hızlı bulun</h1>
      <p class="text-gray-600 text-lg leading-relaxed">Seçimleri yapın; sonuç sayfası aynı bölüm ve filtrelerle açılır. Sonra kurumları favoriye alıp karşılaştırabilirsiniz.</p>
    </div>
@endif

    <div class="{{ $variant === 'dark' ? 'bg-white text-gray-900 rounded-xl' : ($variant === 'warm' ? 'max-w-5xl mx-auto bg-white rounded-3xl' : 'bg-gray-50 rounded-xl') }} border border-gray-100 shadow-xl p-5 md:p-7">
      <form method="GET" action="{{ $resultHref }}" data-district-map='@json($districtMap)' class="js-location-filter grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <div class="text-sm font-black mb-2" style="color: {{ $colors['primary'] }};">Bölüm</div>
          <div class="grid sm:grid-cols-3 gap-2">
            @foreach($sections as $slug => $section)
              @php $active = $activeSection['slug'] === $slug; @endphp
              <a href="{{ brand_route('engagement.wizard', ['bolum' => $slug]) }}" class="rounded-xl border px-4 py-3 text-sm font-black {{ $active ? 'text-white' : 'bg-white text-gray-700 border-gray-200' }}" style="{{ $active ? 'background: '.$colors['primary'].'; border-color: '.$colors['primary'].';' : '' }}">
                <span class="inline-flex items-center gap-2">@include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4']) {{ $section['title'] }}</span>
              </a>
            @endforeach
          </div>
        </div>
        <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">
        <label class="block"><span class="text-xs font-black text-gray-500">İl</span><select name="city" class="js-city mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">İl seçin</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select></label>
        <label class="block"><span class="text-xs font-black text-gray-500">İlçe</span><select name="district" class="js-district mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white" disabled><option value="">Önce il seçin</option></select></label>
        <label class="block"><span class="text-xs font-black text-gray-500">Kurum türü</span><select name="category" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Hepsi</option>@foreach($categories as $category)<option value="{{ $category->slug }}">{{ $category->name }}</option>@endforeach</select></label>
        <label class="block"><span class="text-xs font-black text-gray-500">Öncelikli ihtiyaç</span><select name="service" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Seçin</option>@foreach($sectionServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach</select></label>
        <label class="block"><span class="text-xs font-black text-gray-500">Bütçe (TL)</span><input type="number" name="budget" min="0" step="100" placeholder="Örn: 25000" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"></label>
        <div class="md:col-span-2 grid sm:grid-cols-3 gap-3 pt-2">
          <button class="sm:col-span-2 rounded-xl text-white font-black px-5 py-3" style="background: {{ $colors['primary'] }};">Uygun kurumları göster</button>
          <a href="{{ $homeHref }}" class="rounded-xl border border-gray-200 bg-white text-center font-black px-5 py-3 text-gray-700">Bölüm ana sayfası</a>
        </div>
      </form>
    </div>

@if($variant === 'warm')
  </div>
</section>
@else
  </div>
</section>
@endif
@include('themes._shared.partials.location-filter-script')
@endsection
