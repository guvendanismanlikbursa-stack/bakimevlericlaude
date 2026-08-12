@extends('admin.layout')
@section('title','Kurum Yetkilileri')
@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">Kurum Yetkilileri</h1>
    <p class="text-sm text-gray-500 mt-1">Kurum sahiplenme/kayıt onayıyla oluşan tüm yetkili hesaplarını arayın, filtreleyin ve gerekirse askıya alın.</p>
  </div>
</div>

<form method="GET" data-instant-filter="1" data-results-target="js-facility-users-results" class="js-instant-filter bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid md:grid-cols-4 gap-3 mb-6">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="İsim, e-posta, telefon veya kurum ara" class="border rounded-lg px-3 py-2 text-sm md:col-span-2">
  <select name="status" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm hesap durumları</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="suspended" @selected(request('status')==='suspended')>Askıya Alınmış</option></select>
  <select name="email_status" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm e-posta durumları</option><option value="verified" @selected(request('email_status')==='verified')>Doğrulandı</option><option value="pending" @selected(request('email_status')==='pending')>Bekliyor</option></select>
  <select name="city" class="border rounded-lg px-3 py-2 text-sm"><option value="">Tüm şehirler</option>@foreach($cities as $city)<option value="{{ $city->id }}" @selected((string) request('city')===(string) $city->id)>{{ $city->name }}</option>@endforeach</select>
  <select name="category" class="border rounded-lg px-3 py-2 text-sm md:col-span-2"><option value="">Tüm kategoriler</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category')===(string) $category->id)>{{ $category->name }}</option>@endforeach</select>
  <div class="md:col-span-2 flex gap-2">
    <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Filtrele</button>
    <a href="{{ route('admin.users.facility-users') }}" class="border rounded-lg px-4 py-2 text-sm font-bold text-center">Temizle</a>
  </div>
</form>

<div id="js-facility-users-results">
  @include('admin.users._facility-users-results', ['facilityUsers' => $facilityUsers])
</div>

@include('themes._shared.partials.location-filter-script')
@endsection
