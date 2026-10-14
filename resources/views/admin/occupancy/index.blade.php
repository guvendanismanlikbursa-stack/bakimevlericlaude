@extends('admin.layout')
@section('title', 'Doluluk Durumu')

@section('content')
<div class="mb-6">
  <h1 class="text-2xl font-bold">Doluluk Durumu</h1>
  <p class="text-sm text-gray-500 mt-1">Kurumlarda bay/bayan için kaç boş yer olduğunu buradan takip edin. <span class="font-bold text-gray-700">Bu bilgi tamamen gizlidir</span> — ne kurumlar ne aileler görebilir, sadece siz görürsünüz.</p>
</div>

<form method="GET" class="mb-4 flex gap-2 flex-wrap">
  <input type="text" name="q" value="{{ $q }}" placeholder="Kurum adında ara..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[200px]">
  <select name="city" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->id }}" @selected((int) $cityId === $city->id)>{{ $city->name }}</option>
    @endforeach
  </select>
  <label class="flex items-center gap-1.5 text-sm border rounded-lg px-3 py-2 bg-white">
    <input type="checkbox" name="only_tracked" value="1" onchange="this.form.submit()" @checked(request('only_tracked'))>
    Sadece bilgi girilenler
  </label>
  <button type="submit" class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Ara</button>
  @if($q !== '' || $cityId || request('only_tracked'))
    <a href="{{ route('admin.occupancy.index') }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
  @endif
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). form="occ-id"
     ozniteligi HTML5'te kendi disindaki input/button'lari da forma baglar,
     kart yapisinda da calismaya devam eder. --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($facilities as $facility)
    <form id="occ-{{ $facility->id }}" method="POST" action="{{ route('admin.occupancy.update', $facility) }}">@csrf</form>
    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $facility->name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $facility->city->name ?? '-' }} · Kapasite: {{ $facility->capacity ?: '-' }} · Son güncelleme: {{ $facility->vacant_beds_updated_at?->diffForHumans() ?: 'hiç girilmedi' }}</div>
      </div>
      <div class="flex items-center gap-3 flex-wrap">
        <label class="text-xs text-gray-500">Bay boş yer <input type="number" min="0" name="vacant_beds_male" form="occ-{{ $facility->id }}" value="{{ $facility->vacant_beds_male }}" placeholder="-" class="border rounded-lg px-2 py-1 text-xs w-20 ml-1"></label>
        <label class="text-xs text-gray-500">Bayan boş yer <input type="number" min="0" name="vacant_beds_female" form="occ-{{ $facility->id }}" value="{{ $facility->vacant_beds_female }}" placeholder="-" class="border rounded-lg px-2 py-1 text-xs w-20 ml-1"></label>
        <button type="submit" form="occ-{{ $facility->id }}" class="bg-gray-900 text-white rounded-lg px-3 py-1.5 text-xs font-bold whitespace-nowrap">Kaydet</button>
      </div>
    </div>
  @empty
    <div class="p-6 text-center text-gray-400">Kurum bulunamadı.</div>
  @endforelse
</div>

<div class="mt-4">{{ $facilities->links() }}</div>
@endsection
