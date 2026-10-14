@extends('admin.layout')
@section('title', 'Yeni Aile Anketi')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">📋 Yeni Aile Anketi</h1>
  <a href="{{ route('admin.family-need-surveys.index') }}" class="text-sm font-semibold text-gray-700 underline">Anketlere dön</a>
</div>
<p class="text-sm text-gray-500 mb-6">Kağıt anketten elle girin. Kaydedince konuma ve ihtiyaca uygun en fazla 10 kurum otomatik listelenecek.</p>

<form method="POST" action="{{ route('admin.family-need-surveys.store') }}" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 max-w-3xl">
  @csrf
  <div>
    <label class="text-sm font-medium">Ad Soyad</label>
    <input type="text" name="name" value="{{ old('name') }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>
  <div>
    <label class="text-sm font-medium">Telefon</label>
    <input type="text" name="phone" value="{{ old('phone') }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">İl</label>
    <select name="city_id" required class="js-fns-city border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <option value="">İl seçin</option>
      @foreach($cities as $city)
        <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="text-sm font-medium">İlçe</label>
    <select name="district" class="js-fns-district border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <option value="">Önce il seçin</option>
    </select>
  </div>
  <div class="md:col-span-2">
    <label class="text-sm font-medium">Mahalle <span class="text-xs font-normal text-gray-400">(ankette yazdığı gibi)</span></label>
    <input type="text" name="neighborhood" value="{{ old('neighborhood') }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Çocuğun yaş grubu</label>
    <select name="age_group" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <option value="">Belirtilmedi</option>
      @foreach(\App\Models\FamilyNeedSurvey::AGE_GROUPS as $key => $label)
        <option value="{{ $key }}" @selected(old('age_group') === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="text-sm font-medium">İhtiyaç duyulan hizmet</label>
    <select name="service_type" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      @foreach(\App\Models\FamilyNeedSurvey::SERVICE_TYPES as $key => $label)
        <option value="{{ $key }}" @selected(old('service_type') === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="text-sm font-medium">Ne zaman araştırma yapabilir</label>
    <select name="urgency" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <option value="">Belirtilmedi</option>
      @foreach(\App\Models\FamilyNeedSurvey::URGENCIES as $key => $label)
        <option value="{{ $key }}" @selected(old('urgency') === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="text-sm font-medium">Özel mi, kamu/belediye mi</label>
    <select name="ownership_preference" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <option value="">Belirtilmedi</option>
      @foreach(\App\Models\FamilyNeedSurvey::OWNERSHIP_PREFERENCES as $key => $label)
        <option value="{{ $key }}" @selected(old('ownership_preference') === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>

  <div class="md:col-span-2">
    <button class="bg-gray-900 text-white px-5 py-2 rounded-lg text-sm font-semibold">Kayıt Et ve Uygun Kurumları Bul</button>
  </div>
</form>

<script>
(function () {
  var districtMap = @json($districtMap);
  var citySel = document.querySelector('.js-fns-city');
  var districtSel = document.querySelector('.js-fns-district');

  function fillDistricts() {
    var list = districtMap[citySel.value] || [];
    districtSel.innerHTML = '<option value="">İlçe seçin</option>';
    list.forEach(function (d) {
      var o = document.createElement('option');
      o.value = d; o.textContent = d;
      districtSel.appendChild(o);
    });
  }

  citySel.addEventListener('change', fillDistricts);
  if (citySel.value) fillDistricts();
})();
</script>
@endsection
