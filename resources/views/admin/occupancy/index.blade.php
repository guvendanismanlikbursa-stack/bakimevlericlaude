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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3">Kurum</th>
        <th class="p-3">Şehir</th>
        <th class="p-3">Kapasite</th>
        <th class="p-3">Bay için boş yer</th>
        <th class="p-3">Bayan için boş yer</th>
        <th class="p-3">Son güncelleme</th>
        <th class="p-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y align-top">
      @forelse($facilities as $facility)
        <form id="occ-{{ $facility->id }}" method="POST" action="{{ route('admin.occupancy.update', $facility) }}">@csrf</form>
        <tr>
          <td class="p-3 font-medium">{{ $facility->name }}</td>
          <td class="p-3 text-gray-500">{{ $facility->city->name ?? '-' }}</td>
          <td class="p-3 text-gray-500">{{ $facility->capacity ?: '-' }}</td>
          <td class="p-3">
            <input type="number" min="0" name="vacant_beds_male" form="occ-{{ $facility->id }}" value="{{ $facility->vacant_beds_male }}" placeholder="-" class="border rounded-lg px-2 py-1 text-xs w-20">
          </td>
          <td class="p-3">
            <input type="number" min="0" name="vacant_beds_female" form="occ-{{ $facility->id }}" value="{{ $facility->vacant_beds_female }}" placeholder="-" class="border rounded-lg px-2 py-1 text-xs w-20">
          </td>
          <td class="p-3 text-gray-400 text-xs">{{ $facility->vacant_beds_updated_at?->diffForHumans() ?: 'hiç girilmedi' }}</td>
          <td class="p-3">
            <button type="submit" form="occ-{{ $facility->id }}" class="bg-gray-900 text-white rounded-lg px-3 py-1.5 text-xs font-bold whitespace-nowrap">Kaydet</button>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-6 text-center text-gray-400">Kurum bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $facilities->links() }}</div>
@endsection
