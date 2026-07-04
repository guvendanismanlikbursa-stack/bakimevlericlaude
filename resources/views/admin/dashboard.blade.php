@extends('admin.layout')
@section('title', 'Genel Bakış')

@section('content')
<h1 class="text-2xl font-bold mb-6">Genel Bakış</h1>

@if($pendingClaims > 0 || $pendingTopups > 0)
<div class="grid md:grid-cols-2 gap-4 mb-6">
  @if($pendingClaims > 0)
    <a href="{{ route('admin.claims.index') }}" class="block bg-orange-50 border border-orange-200 text-orange-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingClaims }}</strong> sahiplenme başvurusu onay bekliyor →
    </a>
  @endif
  @if($pendingTopups > 0)
    <a href="{{ route('admin.topups.index') }}" class="block bg-blue-50 border border-blue-200 text-blue-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingTopups }}</strong> bakiye yükleme talebi onay bekliyor →
    </a>
  @endif
</div>
@endif

<div class="grid md:grid-cols-3 gap-6 mb-10">
  @foreach($stats as $slug => $s)
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-bold mb-3">{{ $s['name'] }}</h2>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div><div class="text-gray-500">Kurum</div><div class="font-bold text-lg">{{ $s['facilities'] }}</div></div>
        <div><div class="text-gray-500">Sahiplenilmiş</div><div class="font-bold text-lg text-green-700">{{ $s['claimed'] }}</div></div>
        <div><div class="text-gray-500">Teklif Talebi</div><div class="font-bold text-lg">{{ $s['offer_requests'] }}</div></div>
        <div><div class="text-gray-500">Yeni Talep</div><div class="font-bold text-lg text-orange-600">{{ $s['new_offer_requests'] }}</div></div>
      </div>
      <a href="{{ route('admin.facilities.index', ['brand' => $slug]) }}" class="text-xs text-blue-600 mt-3 inline-block">Kurumları görüntüle →</a>
    </div>
  @endforeach
</div>

<h2 class="text-lg font-bold mb-4">Son Teklif Talepleri</h2>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad Soyad</th><th class="p-3">Telefon</th><th class="p-3">Kurum</th><th class="p-3">Durum</th><th class="p-3">Tarih</th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($latestOffers as $offer)
        <tr>
          <td class="p-3">{{ $offer->full_name }}</td>
          <td class="p-3">{{ $offer->phone }}</td>
          <td class="p-3">{{ $offer->facility?->name ?? '-' }}</td>
          <td class="p-3">{{ $offer->status }}</td>
          <td class="p-3">{{ $offer->created_at->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="5">Henüz talep yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
