@extends('admin.layout')
@section('title','Aileler')
@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Aileler</h1>
    <p class="text-sm text-gray-500 mt-1">Kayıt olan tüm aile hesaplarını arayın, filtreleyin ve gerekirse askıya alın.</p>
  </div>
</div>

{{-- 18 Kasım 2026: kullanıcının talebi - aile sayıları ve şehir dağılımı (eskiden Site İstatistikleri'ndeydi). --}}
@php $kutu = 'background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(0,0,0,.05);'; @endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
  <div style="{{ $kutu }}"><div class="text-xs text-gray-500">Toplam Aile (tüm siteler)</div><div class="text-2xl font-black mt-1">{{ number_format($aileOzet['toplam'], 0, ',', '.') }}</div></div>
  <div style="{{ $kutu }}"><div class="text-xs text-gray-500">Konum Paylaşan</div><div class="text-2xl font-black mt-1">{{ number_format($aileOzet['konumlu'], 0, ',', '.') }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($aileOzet['toplam'], 0, ',', '.') }}</span></div></div>
  <div style="{{ $kutu }}"><div class="text-xs text-gray-500">E-posta Doğrulayan</div><div class="text-2xl font-black mt-1">{{ number_format($aileOzet['dogrulanmis'], 0, ',', '.') }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($aileOzet['toplam'], 0, ',', '.') }}</span></div></div>
  <div style="{{ $kutu }}"><div class="text-xs text-gray-500">Farklı Şehir</div><div class="text-2xl font-black mt-1">{{ $aileOzet['sehirler']->count() }}</div></div>
</div>
@if($aileOzet['toplam'] > 0)
  @php $enCok = max(1, (int) $aileOzet['sehirler']->max('total'), $aileOzet['sehirsiz']); @endphp
  <div style="{{ $kutu }}margin-bottom:20px;">
    <h3 class="font-bold mb-1">Şehre Göre Dağılım</h3>
    <p class="text-xs text-gray-500 mb-3">Şehir, ailenin kayıt olurken paylaştığı konumdan alınır. Konum paylaşmayanların şehri bilinmez.</p>
    @foreach($aileOzet['sehirler'] as $row)
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <span style="width:110px;flex-shrink:0;" class="text-sm font-semibold text-gray-700 truncate">{{ $row->signup_city_name }}</span>
        <span style="flex:1;background:#f3f4f6;border-radius:999px;height:12px;overflow:hidden;"><span style="display:block;height:100%;border-radius:999px;background:#0b5d8c;width:{{ max(4, round($row->total / $enCok * 100)) }}%;"></span></span>
        <span style="width:36px;text-align:right;" class="text-sm font-black">{{ $row->total }}</span>
      </div>
    @endforeach
    @if($aileOzet['sehirsiz'] > 0)
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <span style="width:110px;flex-shrink:0;" class="text-sm font-semibold text-gray-500">Şehri bilinmiyor</span>
        <span style="flex:1;background:#f3f4f6;border-radius:999px;height:12px;overflow:hidden;"><span style="display:block;height:100%;border-radius:999px;background:#9ca3af;width:{{ max(4, round($aileOzet['sehirsiz'] / $enCok * 100)) }}%;"></span></span>
        <span style="width:36px;text-align:right;" class="text-sm font-black text-gray-500">{{ $aileOzet['sehirsiz'] }}</span>
      </div>
    @endif
  </div>
@endif

<form method="GET" data-instant-filter="1" data-results-target="js-families-results" class="js-instant-filter bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid md:grid-cols-4 gap-3 mb-6">
  {{-- 14 Agustos 2026: kullanicinin bildirdigi hata - bkz. admin/facilities/
       index.blade.php'deki ayni tarihli yorum: inline oninput reload,
       ayni forma bagli AJAX instant-filter ile cakisip HER TUS VURUSUNDA
       tam sayfa yenilemesine (ve imlecin basa donmesine) sebep oluyordu. --}}
  <input type="text" name="q" value="{{ request('q') }}" autofocus placeholder="İsim, e-posta veya telefon ara" class="border rounded-lg px-3 py-2 text-sm">
  <select name="brand" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm siteler</option>@foreach($brands as $slug => $brand)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $brand['name'] }}</option>@endforeach</select>
  <select name="status" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm durumlar</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="suspended" @selected(request('status')==='suspended')>Askıya Alınmış</option></select>
  <select name="has_location" class="border rounded-lg px-3 py-2 text-sm"><option value="">Konum farketmez</option><option value="1" @selected(request('has_location')==='1')>Konum paylaşanlar</option><option value="0" @selected(request('has_location')==='0')>Konum paylaşmayanlar</option></select>
  <div class="md:col-span-4 flex gap-2">
    <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Filtrele</button>
    <a href="{{ route('admin.users.families') }}" class="border rounded-lg px-4 py-2 text-sm font-bold text-center">Temizle</a>
  </div>
</form>

<div id="js-families-results">
  @include('admin.users._families-results', ['families' => $families, 'brands' => $brands])
</div>

@include('themes._shared.partials.location-filter-script')
@endsection
