@extends('admin.layout')
@section('title','Aileler')
@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Aileler</h1>
    <p class="text-sm text-gray-500 mt-1">Kayıt olan tüm aile hesaplarını arayın, filtreleyin ve gerekirse askıya alın.</p>
  </div>
</div>

<form method="GET" data-instant-filter="1" data-results-target="js-families-results" class="js-instant-filter bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid md:grid-cols-4 gap-3 mb-6">
  {{-- 14 Agustos 2026: kullanicinin bildirdigi hata - bkz. admin/facilities/
       index.blade.php'deki ayni tarihli yorum: inline oninput reload,
       ayni forma bagli AJAX instant-filter ile cakisip HER TUS VURUSUNDA
       tam sayfa yenilemesine (ve imlecin basa donmesine) sebep oluyordu. --}}
  <input type="text" name="q" value="{{ request('q') }}" autofocus placeholder="İsim, e-posta veya telefon ara" class="border rounded-lg px-3 py-2 text-sm md:col-span-2">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm siteler</option>@foreach($brands as $slug => $brand)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $brand['name'] }}</option>@endforeach</select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm durumlar</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="suspended" @selected(request('status')==='suspended')>Askıya Alınmış</option></select>
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
