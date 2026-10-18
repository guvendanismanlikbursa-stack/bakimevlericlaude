@extends('admin.layout')
@section('title', 'Abone Kurumlar')

@section('content')
<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">Abone Kurumlar</h1>
    <span class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1">{{ number_format($subscriptions->total(), 0, ',', '.') }} abone</span>
  </div>
  <a href="{{ route('admin.facilities.index') }}" class="text-sm font-semibold text-gray-700 underline">Tüm kurumlara dön</a>
</div>

<p class="text-sm text-gray-500 mb-6">Aylık/yıllık ücret karşılığında telefon/WhatsApp butonları aktif olan ve içerikleri admin tarafından güncellenen kurumlar. Ödeme süresi dolan kurumların butonları otomatik olarak (bir sonraki sayfa yüklemesinde) gizlenir.</p>

<form method="GET" data-district-map='@json($districtMap)' class="js-location-filter mb-4 flex gap-2 flex-wrap">
  <select name="city" class="js-city border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="district" data-selected="{{ request('district') }}" class="js-district border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()" @if(! request('city')) disabled @endif>
    <option value="">{{ request('city') ? 'Tüm İlçeler' : 'Önce il seçin' }}</option>
    @if(request('city'))
      @foreach(districts_for_city(optional($cities->firstWhere('slug', request('city')))->name ?? '') as $districtName)
        <option value="{{ $districtName }}" @selected(request('district') === $districtName)>{{ $districtName }}</option>
      @endforeach
    @endif
  </select>
  <select name="category" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Tüm Bölümler</option>
    @foreach($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  <select name="plan" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Plan: Tümü</option>
    <option value="monthly" @selected(request('plan')==='monthly')>Aylık</option>
    <option value="quarterly" @selected(request('plan')==='quarterly')>3 Aylık</option>
    <option value="semiannual" @selected(request('plan')==='semiannual')>6 Aylık</option>
    <option value="yearly" @selected(request('plan')==='yearly')>Yıllık</option>
  </select>
  <select name="durum" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">Durum: Tümü</option>
    <option value="aktif" @selected(request('durum')==='aktif')>Aktif</option>
    <option value="odeme_bekliyor" @selected(request('durum')==='odeme_bekliyor')>Ödeme Bekliyor</option>
    <option value="suresi_gecti" @selected(request('durum')==='suresi_gecti')>Süresi Geçti</option>
  </select>
  @if(request('city') || request('district') || request('category') || request('plan') || request('durum'))
    <a href="{{ route('admin.facilities.subscriptions.index') }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreleri temizle</a>
  @endif
</form>

@include('themes._shared.partials.location-filter-script')

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> (daha once yatay
     kaydirmali) yerine flex-wrap kart (bkz. admin/facilities/_results.blade.php
     ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($subscriptions as $f)
    @php
      $planDuration = ['monthly' => '1 ay', 'quarterly' => '3 ay', 'semiannual' => '6 ay', 'yearly' => '1 yıl'][$f->subscription_plan] ?? '1 ay';
    @endphp
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <a href="{{ route('admin.facilities.edit', $f) }}" class="text-blue-600 font-bold">{{ $f->name }}</a>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $f->city->name }} · {{ ['monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannual'=>'6 Aylık','yearly'=>'Yıllık'][$f->subscription_plan] ?? $f->subscription_plan }} ·
            @if($f->subscriptionPackage)<span class="font-bold" style="color:#a21caf;">{{ $f->subscriptionPackage->badge_emoji }} {{ $f->subscriptionPackage->badge_label }}</span> ·@endif
            {{ $f->subscription_amount ? number_format($f->subscription_amount, 0, ',', '.').' TL' : '—' }} ·
            Başlangıç: {{ $f->subscription_started_at?->format('d.m.Y') ?? '—' }} · Bitiş: {{ $f->subscription_paid_until?->format('d.m.Y') ?? '—' }}
          </div>
          <div class="mt-1">
            @if($f->isActiveSubscriber())
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#15803d;">Aktif</span>
            @elseif($f->subscription_paid_until)
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Süresi Geçti</span>
            @else
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">Ödeme Bekliyor</span>
            @endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          <form method="POST" action="{{ route('admin.facilities.subscription.extend', $f) }}" class="inline-flex items-center gap-1 flex-wrap" onsubmit="return confirm('{{ $f->name }} için ödeme alındı mı? {{ $f->subscription_paid_until ? 'Bitiş tarihi '.$planDuration.' uzatılacak.' : 'Abonelik süresi bu ödemeyle başlayıp '.$planDuration.' geçerli olacak.' }}');">
            @csrf
            <input type="number" name="amount" step="0.01" min="0" value="{{ $f->subscription_amount }}" placeholder="TL" class="text-xs border rounded w-20">
            <button class="text-green-700 text-sm font-semibold whitespace-nowrap">Ödeme Aldım</button>
          </form>
          <form method="POST" action="{{ route('admin.facilities.subscription.cancel', $f) }}" class="inline" onsubmit="return confirm('{{ $f->name }} aboneliği iptal edilsin mi?');">
            @csrf
            <button class="text-sm font-semibold whitespace-nowrap" style="color:#a21caf;">İptal Et</button>
          </form>
        </div>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz abone kurum yok.</div>
  @endforelse
</div>
<div class="mt-6">
  @include('partials.pagination-info', ['paginator' => $subscriptions])
  {{ $subscriptions->links() }}
</div>
@endsection
