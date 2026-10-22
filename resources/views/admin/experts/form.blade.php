@extends('admin.layout')
@section('title', $expert->exists ? 'Doktor Düzenle' : 'Doktor Ekle')

@section('content')
<h1 class="text-2xl font-bold mb-6">{{ $expert->exists ? 'Doktor Düzenle' : 'Yeni Doktor Ekle' }}</h1>

<form method="POST" action="{{ $expert->exists ? route('admin.experts.update', $expert) : route('admin.experts.store') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 max-w-3xl">
  @csrf
  @if($expert->exists) @method('PUT') @endif

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Ünvan</label>
      <input type="text" name="title" value="{{ old('title', $expert->title) }}" placeholder="Uzm. Dr. / Prof. Dr. / Op. Dr." class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Ad Soyad *</label>
      <input type="text" name="name" value="{{ old('name', $expert->name) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Branş (teknik ad) *</label>
      <input type="text" name="branch" id="branch-input" value="{{ old('branch', $expert->branch) }}" required list="branch-list" class="w-full border rounded-lg px-3 py-2 text-sm">
      <datalist id="branch-list">
        @foreach($branches as $title => $label)
          <option value="{{ $title }}" data-label="{{ $label }}">{{ $title }} ({{ $label }})</option>
        @endforeach
      </datalist>
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Branş (günlük dilde) *</label>
      <input type="text" name="branch_label" id="branch-label-input" value="{{ old('branch_label', $expert->branch_label) }}" required placeholder="Örn: Çocuk Doktoru" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">İl *</label>
      <select name="city_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">Seçin</option>
        @foreach($cities as $city)
          <option value="{{ $city->id }}" @selected((string) old('city_id', $expert->city_id) === (string) $city->id)>{{ $city->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">İlçe</label>
      <input type="text" name="district" value="{{ old('district', $expert->district) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">Adres</label>
    <input type="text" name="address" value="{{ old('address', $expert->address) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Klinik/Hastane Adı</label>
      <input type="text" name="clinic_name" value="{{ old('clinic_name', $expert->clinic_name) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Telefon</label>
      <input type="text" name="phone" value="{{ old('phone', $expert->phone) }}" placeholder="05xx xxx xx xx" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">Açıklama</label>
    <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('description', $expert->description) }}</textarea>
  </div>

  <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">Google Haritalar Linki</label>
    <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $expert->google_maps_url) }}" placeholder="https://www.google.com/maps/..." class="w-full border rounded-lg px-3 py-2 text-sm">
  </div>

  <div class="grid sm:grid-cols-2 gap-4 mb-5">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Google Puanı</label>
      <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $expert->rating) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Yorum Sayısı</label>
      <input type="number" min="0" name="review_count" value="{{ old('review_count', $expert->review_count) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
  </div>

  @if($expert->exists && $expert->reviews)
    <div class="mb-5 bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
      Bu doktorun {{ count($expert->reviews) }} yorumu kayıtlı (veri çekici ile eklendi). Yorumlar bu formdan düzenlenemiyor.
    </div>
  @endif

  {{-- 22 Ekim 2026: doktorlara sembolik yıllık İyilik Hareketi üyeliği; yalnızca etiket, ayrıcalık yok. --}}
  <div class="mb-5 rounded-lg p-4" style="background:#fdf2f8;border:1px solid #fbcfe8;">
    <label class="text-sm font-semibold block mb-1">💗 İyilik Hareketi Destekçisi — bitiş tarihi</label>
    <input type="date" name="supporter_until" value="{{ old('supporter_until', $expert->supporter_until?->format('Y-m-d')) }}" class="border rounded-lg px-3 py-2 text-sm">
    <p class="text-xs text-gray-500 mt-1">Doktor yıllık destekçi ücretini ödediyse bitiş tarihini girin (ör. bugünden 1 yıl sonrası). Bu tarihe kadar doktorun kartında "İyilik Hareketi Destekçisi" etiketi görünür; sıralamayı veya başka hiçbir şeyi etkilemez. Boş bırakılırsa etiket görünmez.</p>
  </div>

  <label class="flex items-center gap-2 mb-6">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $expert->is_published)) class="rounded">
    <span class="text-sm font-semibold text-gray-700">Yayında (sitede görünür)</span>
  </label>

  <div class="flex gap-3">
    <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2.5 text-sm font-bold">{{ $expert->exists ? 'Kaydet' : 'Ekle' }}</button>
    <a href="{{ route('admin.experts.index') }}" class="text-sm font-semibold text-gray-500 self-center">Vazgeç</a>
  </div>
</form>

<script>
document.getElementById('branch-input').addEventListener('input', function () {
  var opt = document.querySelector('#branch-list option[value="' + CSS.escape(this.value) + '"]');
  var labelInput = document.getElementById('branch-label-input');
  if (opt && !labelInput.value) { labelInput.value = opt.dataset.label; }
});
</script>
@endsection
