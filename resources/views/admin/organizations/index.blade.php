@extends('admin.layout')
@section('title', 'Organizasyon Şirketleri')

@section('content')
<div class="flex items-center justify-between flex-wrap gap-3 mb-6">
  <div>
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold">Organizasyon Şirketleri</h1>
      {{-- 11 Ekim 2026: Doktorlar sayfasindaki ayni tarihli yorum/duzeltme
           ile AYNI sebep - filtre uygulaninca sonuc sayisi gorunmuyordu. --}}
      <span data-live-count class="inline-flex items-center rounded-full bg-gray-900 text-white text-sm font-semibold px-3 py-1">{{ number_format($organizations->total(), 0, ',', '.') }} firma bulundu</span>
    </div>
    <p class="text-sm text-gray-500 mt-1">"Organizasyon Şirketleri" bölümünde listelenen firma kayıtları. Toplam: {{ $totalCount }} · Yayında: {{ $publishedCount }}</p>
  </div>
  <a href="{{ route('admin.organizations.create') }}" class="bg-blue-600 text-white rounded-lg px-5 py-2.5 text-sm font-bold">+ Firma Ekle</a>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 mb-5 border border-gray-100">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="İsimle ara..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[160px]" data-live-search autocomplete="off">
  <select name="city" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->id }}" @selected((string) request('city') === (string) $city->id)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="service_type" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Hizmet Türleri</option>
    @foreach($serviceTypes as $title => $label)
      <option value="{{ $title }}" @selected(request('service_type') === $title)>{{ $title }}</option>
    @endforeach
  </select>
  <select name="min_rating" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Puanlar</option>
    <option value="4.5" @selected(request('min_rating') === '4.5')>★ 4.5 ve üzeri</option>
    <option value="4" @selected(request('min_rating') === '4')>★ 4.0 ve üzeri</option>
    <option value="3.5" @selected(request('min_rating') === '3.5')>★ 3.5 ve üzeri</option>
    <option value="3" @selected(request('min_rating') === '3')>★ 3.0 ve üzeri</option>
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    <option value="published" @selected(request('status') === 'published')>Yayında</option>
    <option value="draft" @selected(request('status') === 'draft')>Taslak</option>
    <option value="deleted" @selected(request('status') === 'deleted')>Silinenler (çöp kutusu)</option>
  </select>
  {{-- 11 Ekim 2026: kullanicinin talebi - eksik telefonlu firmalari bulup manuel arama yapabilmek icin. --}}
  <select name="phone_status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Telefon: Tümü</option>
    <option value="eksik" @selected(request('phone_status')==='eksik')>📞 Telefonu Eksik</option>
  </select>
  @if(request('q') || request('city') || request('service_type') || request('min_rating') || request('status') || request('phone_status'))
    <a href="{{ route('admin.organizations.index') }}" class="text-sm font-semibold text-gray-500 underline self-center">Filtreyi temizle</a>
  @endif
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - "hangi olcude olursa olsun butun
     islemleri gorebilmeliyim, kaydirmak istemiyorum" - genis <table> yerine
     flex-wrap kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div data-live-results>
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($organizations as $org)
    <div class="p-4 flex flex-col gap-2 {{ $org->trashed() ? 'opacity-50' : '' }}">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900 break-words">{{ $org->name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $org->service_type_label ?: $org->service_type }} · {{ $org->city?->name }}@if($org->district) / {{ $org->district }}@endif
            @if($org->clinic_name) · {{ $org->clinic_name }}@endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          @if($org->trashed())
            <form method="POST" action="{{ route('admin.organizations.restore', $org->id) }}" class="inline">
              @csrf
              <button class="text-green-700 text-sm font-semibold whitespace-nowrap">Geri Yükle</button>
            </form>
          @else
            <a href="{{ route('admin.organizations.edit', $org) }}" class="text-blue-700 text-sm font-semibold whitespace-nowrap">Düzenle</a>
            <form method="POST" action="{{ route('admin.organizations.toggle-publish', $org) }}" class="inline">
              @csrf
              <button class="text-gray-700 text-sm font-semibold whitespace-nowrap">{{ $org->is_published ? 'Yayından Kaldır' : 'Yayınla' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.organizations.destroy', $org) }}" class="inline" onsubmit="return confirm('Bu firma silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button>
            </form>
          @endif
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap text-xs">
        <span class="text-gray-600">📞 {{ $org->phone ?: '—' }}</span>
        @if($org->trashed())
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-red-100 text-red-700">Silinmiş</span>
        @elseif($org->is_published)
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-green-100 text-green-700">Yayında</span>
        @else
          <span class="inline-block rounded-full px-2.5 py-0.5 font-semibold bg-gray-100 text-gray-600">Taslak</span>
        @endif
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $organizations->links() }}</div>
</div>
@include('admin.partials.live-search-script')
@endsection
