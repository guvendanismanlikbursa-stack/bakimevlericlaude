@extends('admin.layout')
@section('title', 'Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">{{ request('claim_status') === 'unclaimed' ? 'Ön Kayıtlı Kurumlar' : 'Kurumlar' }}</h1>
    <span class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1">{{ number_format($facilities->total(), 0, ',', '.') }} kurum bulundu</span>
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

<form method="GET" data-district-map='@json($districtMap)' class="js-location-filter mb-4 flex gap-2 flex-wrap">
  @if(request('claim_status'))<input type="hidden" name="claim_status" value="{{ request('claim_status') }}">@endif
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
  <select name="district" data-selected="{{ request('district') }}" onchange="this.form.submit()" class="js-district border rounded-lg px-3 py-2 text-sm" @if(! request('city')) disabled @endif>
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
  @if(request('city') || request('district') || request('category') || request('brand') || request('ownership_type'))
    <a href="{{ route('admin.facilities.index', array_filter(['claim_status' => request('claim_status')])) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreleri temizle</a>
  @endif
</form>

@include('themes._shared.partials.location-filter-script')

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad</th><th class="p-3">Şehir</th><th class="p-3">Kategori</th><th class="p-3">Profil Kalitesi</th><th class="p-3">Sahiplenme</th><th class="p-3">Durum</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($facilities as $f)
        <tr>
          <td class="p-3 font-medium">{{ $f->name }}</td>
          <td class="p-3">{{ $f->city->name }}</td>
          <td class="p-3">
            {{ $f->category->name }}
            @if($f->ownership_type)
              <div class="text-[11px] text-gray-400 mt-0.5">{{ $ownershipTypes[$f->ownership_type] ?? $f->ownership_type }}</div>
            @endif
          </td>
          <td class="p-3">
            @php($quality = $f->profileQuality())
            <div class="flex items-center gap-2">
              <div class="h-2 w-20 rounded-full bg-gray-100 overflow-hidden"><div class="h-full {{ $quality['score'] >= 80 ? 'bg-green-500' : ($quality['score'] >= 55 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $quality['score'] }}%"></div></div>
              <span class="text-xs font-semibold text-gray-700">{{ $quality['score'] }}/100</span>
            </div>
            @if($quality['missing'])<div class="text-[11px] text-gray-400 mt-1">Eksik: {{ implode(', ', array_slice($quality['missing'], 0, 2)) }}</div>@endif
          </td>
          <td class="p-3">
            @if($f->is_claimed)
              <span class="text-green-700 text-xs font-semibold">✓ Sahiplenilmiş</span>
            @else
              <span class="text-gray-400 text-xs">Ön Kayıtlı</span>
            @endif
            @php($mv = $f->ministryVerificationBadge())
            @if($mv)
              <div class="mt-1"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $mv['classes'] }}">{{ $mv['label'] }}</span></div>
            @endif
          </td>
          <td class="p-3">{{ $f->is_published ? 'Yayında' : 'Taslak' }}</td>
          <td class="p-3 text-right space-x-2">
            <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600">Revize</a>
            @if($f->is_claimed)
              <form method="POST" action="{{ route('admin.facilities.revert', $f) }}" class="inline" onsubmit="return confirm('Bu kurumu ön kayıtlı hale getirmek istediğinize emin misiniz?');">
                @csrf
                <button class="text-orange-600">Ön Kayıt</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.facilities.destroy', $f) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600">Sil</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $facilities->links() }}</div>
@endsection

