@extends('admin.layout')
@section('title', 'Kurum Davetleri')

@section('content')
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Kurum Davetleri</h1>
    <p class="text-sm text-gray-500 mt-1">Sahiplenilmemiş özel/vakıf kurumlara WhatsApp ile sahiplenme daveti gönderme takibi. Gönderim insan tarafından WhatsApp üzerinden yapılır — bu ekran sadece durumu izler.</p>
  </div>
  <a href="{{ route('admin.invitations.quick-send') }}" class="bg-green-600 text-white rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap">⚡ Hızlı Gönderim</a>
</div>

@php
  $filterParams = ['city' => request('city'), 'district' => request('district'), 'category' => request('category'), 'has_mobile' => request('has_mobile')];
@endphp

<div class="flex flex-wrap gap-2 mb-3">
  @foreach($groups as $key => $def)
    <a href="{{ route('admin.invitations.index', array_filter(array_merge($filterParams, ['group' => $key]))) }}"
       class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $group === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
      <span>{{ $def['title'] }}</span>
      <span class="text-xs {{ $group === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $groupCounts[$key] ?? 0 }}</span>
    </a>
  @endforeach
</div>

<div class="flex flex-wrap gap-2 mb-5">
  <a href="{{ route('admin.invitations.index', array_filter(array_merge($filterParams, ['group' => $group, 'has_mobile' => request('has_mobile') ? null : 1]))) }}"
     class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ request('has_mobile') ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-200' }}">
    <span>📱 Cep telefonu olanlar</span>
    <span class="text-xs {{ request('has_mobile') ? 'text-green-100' : 'text-gray-400' }}">{{ $mobileCount }}</span>
  </a>
</div>

<form method="GET" data-district-map='@json($districtMap)' class="js-location-filter mb-4 flex gap-2 flex-wrap">
  <input type="hidden" name="group" value="{{ $group }}">
  @if(request('has_mobile'))<input type="hidden" name="has_mobile" value="1">@endif
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
    <option value="">Tüm Bölümler / Kategoriler</option>
    @foreach($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  @if(request('city') || request('district') || request('category'))
    <a href="{{ route('admin.invitations.index', array_filter(['group' => $group, 'has_mobile' => request('has_mobile')])) }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
  @endif
</form>

@include('themes._shared.partials.location-filter-script')

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($facilities as $facility)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900">{{ $facility->name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $facility->city->name ?? '—' }}@if($facility->district) / {{ $facility->district }}@endif · {{ $facility->category->name ?? '—' }} ·
            📞 {{ $facility->phone ?: '—' }}
            @if($facility->phone_type === 'landline')<span class="text-amber-600 font-semibold">(sabit hat)</span>@endif
          </div>
          <span class="inline-block mt-1 rounded-full px-2.5 py-0.5 text-xs font-semibold
            {{ match(true) {
                 in_array($facility->invitation_status, ['claimed','approved']) => 'bg-green-100 text-green-700',
                 in_array($facility->invitation_status, ['sent','opened']) => 'bg-blue-100 text-blue-700',
                 in_array($facility->invitation_status, ['do_not_contact','unreachable','wrong_number']) => 'bg-red-100 text-red-700',
                 default => 'bg-gray-100 text-gray-600',
               } }}">
            {{ $statusLabels[$facility->invitation_status] ?? $facility->invitation_status }}
          </span>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          @if($facility->phone_type === 'mobile')
            <a href="{{ route('admin.invitations.whatsapp', $facility) }}" target="_blank" rel="noopener"
               class="inline-block rounded-lg bg-green-600 text-white px-3 py-1.5 text-xs font-bold hover:bg-green-700 whitespace-nowrap">WhatsApp Aç</a>
            @if(in_array($facility->invitation_status, ['not_started', 'opened', 'sent']))
              <form method="POST" action="{{ route('admin.invitations.update-status', $facility) }}" class="inline">
                @csrf
                <input type="hidden" name="status" value="sent">
                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 whitespace-nowrap">Gönderildi</button>
              </form>
            @endif
          @endif
          <form method="POST" action="{{ route('admin.invitations.update-status', $facility) }}" class="inline-flex items-center gap-1">
            @csrf
            <select name="status" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 text-xs">
              <option value="">Durum değiştir…</option>
              @foreach($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected($facility->invitation_status === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </form>
        </div>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Bu grupta kurum yok.</div>
  @endforelse
</div>
<div class="mt-6">
  @include('partials.pagination-info', ['paginator' => $facilities])
  {{ $facilities->links() }}
</div>
@endsection
