@extends('admin.layout')
@section('title', $organization->exists ? 'Firma Düzenle' : 'Firma Ekle')

@section('content')
<h1 class="text-2xl font-bold mb-6">{{ $organization->exists ? 'Firma Düzenle' : 'Yeni Firma Ekle' }}</h1>

<form method="POST" action="{{ $organization->exists ? route('admin.organizations.update', $organization) : route('admin.organizations.store') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 max-w-3xl">
  @csrf
  @if($organization->exists) @method('PUT') @endif

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Firma Unvanı (opsiyonel)</label>
      <input type="text" name="title" value="{{ old('title', $organization->title) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Firma Adı *</label>
      <input type="text" name="name" value="{{ old('name', $organization->name) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Hizmet Türü (teknik ad) *</label>
      <input type="text" name="service_type" id="service-type-input" value="{{ old('service_type', $organization->service_type) }}" required list="service-type-list" class="w-full border rounded-lg px-3 py-2 text-sm">
      <datalist id="service-type-list">
        @foreach($serviceTypes as $title => $label)
          <option value="{{ $title }}" data-label="{{ $label }}">{{ $title }} ({{ $label }})</option>
        @endforeach
      </datalist>
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Hizmet Türü (günlük dilde) *</label>
      <input type="text" name="service_type_label" id="service-type-label-input" value="{{ old('service_type_label', $organization->service_type_label) }}" required placeholder="Örn: Doğum Günü" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">İl *</label>
      <select name="city_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">Seçin</option>
        @foreach($cities as $city)
          <option value="{{ $city->id }}" @selected((string) old('city_id', $organization->city_id) === (string) $city->id)>{{ $city->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">İlçe</label>
      <input type="text" name="district" value="{{ old('district', $organization->district) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">Adres</label>
    <input type="text" name="address" value="{{ old('address', $organization->address) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Resmi Unvan / Ltd. Şti. Adı</label>
      <input type="text" name="clinic_name" value="{{ old('clinic_name', $organization->clinic_name) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Telefon</label>
      <input type="text" name="phone" value="{{ old('phone', $organization->phone) }}" placeholder="05xx xxx xx xx" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">Google Haritalar Linki</label>
    <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $organization->google_maps_url) }}" placeholder="https://www.google.com/maps/..." class="w-full border rounded-lg px-3 py-2 text-sm">
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-5">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Google Puanı</label>
      <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $organization->rating) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Yorum Sayısı</label>
      <input type="number" min="0" name="review_count" value="{{ old('review_count', $organization->review_count) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  @if($organization->exists && $organization->reviews)
    <div class="mb-5 bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
      Bu firmanın {{ count($organization->reviews) }} yorumu kayıtlı (veri çekici ile eklendi). Yorumlar bu formdan düzenlenemiyor.
    </div>
  @endif

  <label class="flex items-center gap-2 mb-6">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $organization->is_published)) class="rounded">
    <span class="text-sm font-semibold text-gray-700">Yayında (sitede görünür)</span>
  </label>

  <div class="flex gap-3">
    <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2.5 text-sm font-bold">{{ $organization->exists ? 'Kaydet' : 'Ekle' }}</button>
    <a href="{{ route('admin.organizations.index') }}" class="text-sm font-semibold text-gray-500 self-center">Vazgeç</a>
  </div>
</form>

<script>
document.getElementById('service-type-input').addEventListener('input', function () {
  var opt = document.querySelector('#service-type-list option[value="' + CSS.escape(this.value) + '"]');
  var labelInput = document.getElementById('service-type-label-input');
  if (opt && !labelInput.value) { labelInput.value = opt.dataset.label; }
});
</script>
@endsection
