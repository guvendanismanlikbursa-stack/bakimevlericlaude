@extends('layouts.brand')

@section('content')
@php
  $quoteStatus = [
    'pending' => 'Beklemede',
    'accepted' => 'Kabul edildi',
    'declined' => 'Reddedildi',
  ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
    <div>
      <p class="text-sm text-gray-500">Kurum Paneli</p>
      <h1 class="text-2xl font-bold">{{ $facility->name }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ $user->name }} · {{ $facility->category->name ? 'Kategori yok' }} · {{ $facility->city->name ? 'Şehir yok' }}</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ brand_route('facility.profile.edit') }}" class="border border-primary text-primary px-4 py-2 rounded-lg text-sm font-semibold">Profili Düzenle</a>
      <form method="POST" action="{{ brand_route('facility.logout') }}">@csrf<button class="text-sm text-red-600">Çıkış Yap</button></form>
    </div>
  </div>

  @if(isset($facilityInBrandScope) && ! $facilityInBrandScope)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Bu kurum hesabı bu sitede açılabilir, ancak kurum kategorisi aktif sitenin hizmet kapsamına girmediği için bu siteden yeni talep alamaz veya teklif veremez.
    </div>
  @endif

  <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Ücretsiz Hak</div>
      <div class="text-2xl font-bold mt-1">{{ $facility->free_quote_credits }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Bakiye</div>
      <div class="text-2xl font-bold mt-1">{{ number_format($facility->balance,2,',','.') }}₺</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Doğrudan Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['direct_requests'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Uygun Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['broadcast_leads'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Tekliflerim</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['sent_quotes'] }}</div>
    </div>
    <a href="{{ brand_route('facility.wallet.index') }}" class="bg-primary text-white rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold">Bakiye Yükle →</a>
    <a href="{{ brand_route('facility.packages.index') }}" class="border border-primary text-primary rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold">Paketler →</a>
    <a href="{{ brand_route('facility.questions.index') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold">Aile Soruları →</a>
  </div>

  <div class="grid lg:grid-cols-[1fr_320px] gap-6">
    <div>
      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-bold text-lg">Size Doğrudan Gelen Talepler</h2>
          <span class="text-xs text-gray-400">{{ $directRequests->count() }} kayıt</span>
        </div>
        <div class="space-y-3">
          @forelse($directRequests as $req)
            @include('themes._shared.facility._request-card', ['req' => $req, 'facility' => $facility])
          @empty
            <div class="bg-white rounded-lg border border-dashed border-gray-300 p-5 text-sm text-gray-500">Henüz doğrudan talep yok.</div>
          @endforelse
        </div>
      </section>

      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-bold text-lg">Şehir/Kategorinize Uygun Yeni Talepler</h2>
          <span class="text-xs text-gray-400">{{ $broadcastLeads->count() }} kayıt</span>
        </div>
        <div class="space-y-3">
          @forelse($broadcastLeads as $req)
            @include('themes._shared.facility._request-card', ['req' => $req, 'facility' => $facility])
          @empty
            <div class="bg-white rounded-lg border border-dashed border-gray-300 p-5 text-sm text-gray-500">Şu anda uygun yeni talep yok.</div>
          @endforelse
        </div>
      </section>
    </div>

    <aside class="space-y-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <h2 class="font-bold mb-3">Kurum Durumu</h2>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between gap-4"><span class="text-gray-500">Yayın</span><span class="font-semibold">{{ $facility->is_published ? 'Yayında' : 'Pasif' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Sahiplenme</span><span class="font-semibold">{{ $facility->is_claimed ? 'Onaylı' : 'Onaysız' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Kapasite</span><span class="font-semibold">{{ $facility->capacity ?: '-' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Fiyat Aralığı</span><span class="font-semibold text-right">@if($facility->price_min || $facility->price_max) {{ number_format($facility->price_min,0,',','.') }}₺ - {{ number_format($facility->price_max,0,',','.') }}₺ @else - @endif</span></div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <h2 class="font-bold mb-3">Teklif Özeti</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg bg-gray-50 p-3"><div class="text-gray-500 text-xs">Bekleyen</div><div class="font-bold text-lg">{{ $stats['pending_quotes'] }}</div></div>
          <div class="rounded-lg bg-green-50 p-3"><div class="text-gray-500 text-xs">Kabul</div><div class="font-bold text-lg">{{ $stats['accepted_quotes'] }}</div></div>
        </div>
      </div>
    </aside>
  </div>

  <section class="mt-2">
    <h2 class="font-bold text-lg mb-3">Gönderdiğim Teklifler</h2>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Aile</th><th class="p-3">Talep</th><th class="p-3">Fiyat</th><th class="p-3">Durum</th><th class="p-3"></th></tr></thead>
        <tbody class="divide-y">
          @forelse($sentQuotes as $q)
            <tr>
              <td class="p-3">{{ $q->offerRequest->familyUser->name ?? $q->offerRequest->full_name ?? '-' }}</td>
              <td class="p-3 text-gray-500">{{ $q->offerRequest->category->name ?? '-' }} · {{ $q->offerRequest->city->name ?? '-' }}</td>
              <td class="p-3">{{ number_format($q->price,0,',','.') }}₺</td>
              <td class="p-3">{{ $quoteStatus[$q->status] ?? $q->status }}</td>
              <td class="p-3"><a href="{{ brand_route('facility.thread', $q->offerRequest) }}" class="text-primary font-semibold">Mesajlar</a></td>
            </tr>
          @empty
            <tr><td class="p-3 text-gray-400" colspan="5">Henüz teklif göndermediniz.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection