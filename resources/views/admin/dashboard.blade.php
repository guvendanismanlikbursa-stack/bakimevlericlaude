@extends('admin.layout')
@section('title', 'Genel Bakış')

@section('content')
@php
  $offerStatusLabels = ['new' => 'Yeni talep', 'contacted' => 'İletişime geçildi', 'closed' => 'Kapandı'];
  $extraClaimsCount = max(0, $pendingClaims - $latestClaims->count());
@endphp
<h1 class="text-2xl font-bold mb-6">Genel Bakış</h1>

@if($pendingClaims > 0 || $pendingTopups > 0 || $pendingRegistrations > 0 || $newVisitServiceRequests > 0)
<div class="grid md:grid-cols-3 gap-4 mb-6">
  @if($pendingClaims > 0)
    <a href="{{ route('admin.claims.index') }}" class="block bg-orange-50 border border-orange-200 text-orange-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingClaims }}</strong> sahiplenme başvurusu onay bekliyor →
      @if($latestClaims->isNotEmpty())
        <div class="mt-2 text-xs font-normal text-orange-700">{{ $latestClaims->pluck('facility.name')->filter()->implode(', ') }}{{ $extraClaimsCount > 0 ? ' ve '.$extraClaimsCount.' diğeri' : '' }}</div>
      @endif
    </a>
  @endif
  @if($pendingTopups > 0)
    <a href="{{ route('admin.topups.index') }}" class="block bg-blue-50 border border-blue-200 text-blue-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingTopups }}</strong> bakiye yükleme talebi onay bekliyor
      <div class="mt-1 text-xs font-normal text-blue-700">Toplam {{ number_format($pendingTopupsAmount, 0, ',', '.') }} ₺ →</div>
    </a>
  @endif
  @if($pendingRegistrations > 0)
    <a href="{{ route('admin.registrations.index') }}" class="block bg-purple-50 border border-purple-200 text-purple-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingRegistrations }}</strong> yeni kurum kaydı onay bekliyor →
    </a>
  @endif
  {{-- 27 Agustos 2026: kullanicinin bildirdigi gercek eksiklik - bkz.
       DashboardController ayni tarihli yorum. --}}
  @if($newVisitServiceRequests > 0)
    <a href="{{ route('admin.visit-service.index', ['status' => 'yeni']) }}" class="block bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl">
      <strong>{{ $newVisitServiceRequests }}</strong> yeni "Yakınımı Ziyaret Et" talebi var →
    </a>
  @endif
</div>
@endif

{{-- 14 Agustos 2026: kullanicinin talebi - "isletme sagligi ozeti", her
     seferinde ayri sayfalara girmeden gunluk trendi (ziyaret, yeni
     basvuru, yeni talep, yeni hata) tek ekranda gormek icin (bkz.
     Admin\DashboardController::healthSummary()). --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-10">
  <div class="flex items-center justify-between mb-1">
    <h2 class="font-bold text-lg">İşletme Sağlığı — Son 14 Gün</h2>
    <a href="{{ route('admin.platform-errors.index') }}" class="text-xs font-semibold {{ $health['unresolved_errors'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $health['unresolved_errors'] }} çözülmemiş hata →</a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    @php
      $healthCards = [
        ['key' => 'visits', 'label' => 'Site Ziyareti (3 marka)', 'color' => '#0b5d8c'],
        ['key' => 'claims', 'label' => 'Yeni Sahiplenme Başvurusu', 'color' => '#1e6f5c'],
        ['key' => 'offers', 'label' => 'Yeni Teklif Talebi', 'color' => '#5b3a8e'],
        ['key' => 'errors', 'label' => 'Yeni Hata', 'color' => '#dc2626'],
      ];
    @endphp
    @foreach($healthCards as $card)
      @php
        $d = $health[$card['key']];
        $diff = $d['this_week'] - $d['last_week'];
        $pct = $d['last_week'] > 0 ? round($diff / $d['last_week'] * 100) : ($d['this_week'] > 0 ? 100 : 0);
        $maxVal = max(1, max($d['daily']));
        $isErrorCard = $card['key'] === 'errors';
      @endphp
      <div class="rounded-lg bg-gray-50 p-3">
        <div class="text-xs text-gray-500">{{ $card['label'] }}</div>
        <div class="text-xl font-black text-gray-950 mt-1">{{ number_format($d['this_week'], 0, ',', '.') }}</div>
        <div class="text-xs font-bold mt-0.5 {{ ($diff >= 0) === $isErrorCard ? 'text-red-600' : 'text-green-600' }}">
          {{ $diff >= 0 ? '▲' : '▼' }} %{{ abs($pct) }} geçen haftaya göre
        </div>
        <div class="flex items-end gap-0.5 h-10 mt-2">
          @foreach($d['daily'] as $i => $val)
            <div class="flex-1 rounded-t" style="height: {{ max(2, round($val / $maxVal * 40)) }}px; background: {{ $card['color'] }}{{ $val > 0 ? '' : '33' }};" title="{{ $health['dates'][$i] }}: {{ $val }}"></div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
</div>

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
          <td class="p-3">{{ $offerStatusLabels[$offer->status] ?? $offer->status }}</td>
          <td class="p-3">{{ $offer->created_at->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="5">Henüz talep yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
