@extends('admin.layout')
@section('title', 'Yakın Arama Kayıtları')

@section('content')
<h1 class="text-2xl font-bold mb-2">Yakın Arama Kayıtları</h1>
<p class="text-sm text-gray-500 mb-6">"Yakınımda Ara" özelliğini kullanan HERKESİN (kayıtlı olsun olmasın) konumu burada listelenir.</p>

<form method="GET" class="mb-4 flex gap-2">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)
      <option value="{{ $slug }}" @selected(request('brand') === $slug)>{{ $b['name'] }}</option>
    @endforeach
  </select>
</form>

@if($cityCounts->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
  <div class="font-black text-gray-950 mb-3">En Çok Aranan Şehirler</div>
  <p class="text-sm text-gray-600">
    @foreach($cityCounts as $row)
      <span class="font-semibold text-gray-800">{{ $row->city_name }}</span>: {{ number_format($row->total, 0, ',', '.') }}@if(! $loop->last) &middot; @endif
    @endforeach
  </p>
</div>
@endif

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($searches as $search)
    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $brands[$search->brand]['name'] ?? $search->brand }} · {{ $search->city_name ?? '—' }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
          @if($search->familyUser)
            <a href="{{ route('admin.family-users.show', $search->familyUser) }}" class="text-primary font-semibold">{{ $search->familyUser->name }}</a>
          @else
            Misafir
          @endif
          · IP: {{ $search->ip ?? '—' }} · {{ $search->created_at->format('d.m.Y H:i') }}
        </div>
      </div>
      <a href="https://www.google.com/maps?q={{ $search->lat }},{{ $search->lng }}" target="_blank" class="text-primary text-sm font-semibold whitespace-nowrap">Haritada gör →</a>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Kayıt bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $searches->links() }}</div>
@endsection
