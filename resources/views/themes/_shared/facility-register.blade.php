@extends('layouts.brand')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Kurumunuzu Kaydedin</h1>
  <p class="text-gray-500 text-sm mb-6">Kurumunuzun bilgilerini girin, başvurunuz admin onayından sonra yayına alınır ve size giriş bilgileri e-posta ile gönderilir.</p>

  <form method="POST" action="{{ brand_route('facility-registration.store') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <div>
      <label class="text-sm font-medium block mb-1">Kurum Türü</label>
      <select name="facility_category_id" required class="border rounded-lg px-3 py-2 w-full">
        <option value="">Kurum türü seçin</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}" @selected(old('facility_category_id') == $category->id)>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>

    <input type="text" name="name" value="{{ old('name') }}" placeholder="Kurum Adı" required class="border rounded-lg px-3 py-2 w-full">

    <div>
      <label class="text-sm font-medium block mb-1">İl</label>
      <select name="city_id" required class="border rounded-lg px-3 py-2 w-full">
        <option value="">İl seçin</option>
        @foreach($cities as $city)
          <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
        @endforeach
      </select>
    </div>

    <input type="text" name="district" value="{{ old('district') }}" placeholder="İlçe" class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="address" value="{{ old('address') }}" placeholder="Açık Adres" class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Kurum Telefonu" class="border rounded-lg px-3 py-2 w-full">
    <textarea name="description" placeholder="Kurum hakkında kısa açıklama" rows="4" class="border rounded-lg px-3 py-2 w-full">{{ old('description') }}</textarea>

    <div class="grid grid-cols-2 gap-3">
      <input type="number" name="capacity" value="{{ old('capacity') }}" placeholder="Kapasite" min="0" class="border rounded-lg px-3 py-2 w-full">
      <div class="grid grid-cols-2 gap-2">
        <input type="number" name="price_min" value="{{ old('price_min') }}" placeholder="Min. Fiyat" min="0" class="border rounded-lg px-3 py-2 w-full">
        <input type="number" name="price_max" value="{{ old('price_max') }}" placeholder="Maks. Fiyat" min="0" class="border rounded-lg px-3 py-2 w-full">
      </div>
    </div>

    <hr class="my-2">
    <p class="text-sm font-medium text-gray-700">Yetkili Bilgileri</p>
    <input type="text" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2 w-full">
    <input type="email" name="applicant_email" value="{{ old('applicant_email') }}" placeholder="E-posta (giriş bilgileri buraya gönderilecek)" required class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">

    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Kaydı Gönder</button>
  </form>
</div>
@endsection
