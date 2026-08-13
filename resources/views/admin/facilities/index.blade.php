@extends('admin.layout')
@section('title', 'Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">{{ request('claim_status') === 'unclaimed' ? 'Ön Kayıtlı Kurumlar' : 'Kurumlar' }}</h1>
    <span class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1"><span id="js-result-count">{{ number_format($facilities->total(), 0, ',', '.') }}</span> kurum bulundu</span>
  </div>
  <div class="flex items-center gap-3">
    <a href="{{ route('admin.facilities.create') }}" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">+ Yeni Kurum</a>
    @if(request('claim_status') === 'unclaimed')
      <a href="{{ route('admin.facilities.index') }}" class="text-sm font-semibold text-gray-700 underline">Tüm kurumlara dön</a>
    @endif
  </div>
</div>

@if(! request('category') && count($categoryBreakdown) > 0)
  <p class="text-sm text-gray-500 mb-6">
    @foreach($categoryBreakdown as $name => $count)
      <span class="font-semibold text-gray-700">{{ $name }}</span>: {{ number_format($count, 0, ',', '.') }}@if(! $loop->last) &middot; @endif
    @endforeach
  </p>
@endif

<!-- 13 Agustos 2026: burada "claim_status" adinda gizli bir input DAHA vardi
     (asagidaki select ile ayni isimde) - tarayicida ayni isimli iki alan
     olmasi FormData/URL siralamasini belirsizlestiriyordu, kaldirildi;
     secili deger zaten asagidaki select uzerinde tutuluyor. -->
<form method="GET" data-district-map='@json($districtMap)' data-instant-filter="1" data-results-target="js-admin-facility-results" data-count-target="js-result-count" class="js-location-filter mb-4 flex gap-2 flex-wrap">
  <input type="search" name="q" value="{{ request('q') }}" oninput="this.form.submit()" onfocus="this.value = this.value;" autofocus placeholder="Kurum adında ara..." class="border rounded-lg px-3 py-2 text-sm w-56">
  <button type="submit" class="border rounded-lg px-3 py-2 text-sm font-semibold bg-white">Ara</button>
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)
      <option value="{{ $slug }}" @selected(request('brand') === $slug)>{{ $b['name'] }}</option>
    @endforeach
  </select>
  <select name="city" onchange="this.form.submit()" class="js-city border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="district" onchange="this.form.submit()" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2 text-sm" @if(! request('city')) disabled @endif>
    <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
    @if(request('city'))
      @foreach(districts_for_city(optional($cities->firstWhere('slug', request('city')))->name ?? '') as $districtName)
        <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
      @endforeach
    @endif
  </select>
  <select name="category" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Kategoriler</option>
    @foreach($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  <select name="ownership_type" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Kuruluş Türleri</option>
    @foreach($ownershipTypes as $value => $label)
      <option value="{{ $value }}" @selected(request('ownership_type') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="claim_status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Sahiplenme: Tümü</option>
    <option value="claimed" @selected(request('claim_status')==='claimed')>Onaylı Kurumlar</option>
    <option value="unclaimed" @selected(request('claim_status')==='unclaimed')>Ön Kayıtlı Kurumlar</option>
  </select>
  @if(request('city') || request('district') || request('category') || request('brand') || request('ownership_type') || request('q'))
    <a href="{{ route('admin.facilities.index', array_filter(['claim_status' => request('claim_status')])) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreleri temizle</a>
  @endif
</form>

@include('themes._shared.partials.location-filter-script')

<div id="js-admin-facility-results">
  @include('admin.facilities._results', ['facilities' => $facilities, 'ownershipTypes' => $ownershipTypes])
</div>
@endsection

