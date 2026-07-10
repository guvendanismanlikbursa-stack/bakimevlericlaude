@extends('admin.layout')
@section('title', $facility->exists ? 'Kurumu Düzenle' : 'Yeni Kurum')

@section('content')
@php
  $selectedServices = collect($facility->services ?? []);
  $imageCount = $facility->exists ? $facility->images->count() : 0;
  $remainingImages = max(0, 10 - $imageCount);
@endphp
<h1 class="text-2xl font-bold mb-6">{{ $facility->exists ? 'Kurumu Düzenle' : 'Yeni Kurum' }}</h1>

<form method="POST" action="{{ $facility->exists ? route('admin.facilities.update', $facility) : route('admin.facilities.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 max-w-4xl">
  @csrf
  @if($facility->exists) @method('PUT') @endif

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Kurum Adı</label>
    <input type="text" name="name" value="{{ old('name', $facility->name) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Şehir</label>
    <select name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1">
      @foreach($cities as $city)
        <option value="{{ $city->id }}" @selected(old('city_id', $facility->city_id) == $city->id)>{{ $city->name }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="text-sm font-medium">Kategori / Ana Bölüm</label>
    <select name="facility_category_id" required class="border rounded-lg px-3 py-2 w-full mt-1">
      @foreach($categories as $category)
        @php $section = service_section_for_scope($category->brand_scope); @endphp
        <option value="{{ $category->id }}" @selected(old('facility_category_id', $facility->facility_category_id) == $category->id)>{{ $section['title'] ?? 'Bölüm yok' }} · {{ $category->name }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="text-sm font-medium">İlçe</label>
    <input type="text" name="district" value="{{ old('district', $facility->district) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Telefon</label>
    <input type="text" name="phone" value="{{ old('phone', $facility->phone) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Adres</label>
    <input type="text" name="address" value="{{ old('address', $facility->address) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Enlem (lat) <span class="text-xs text-gray-400">opsiyonel</span></label>
    <input type="text" name="lat" value="{{ old('lat', $facility->lat) }}" placeholder="Örn: 40.1826" class="border rounded-lg px-3 py-2 w-full mt-1">
    <p class="text-xs text-gray-400 mt-1">Dolu ise &quot;Yakınımdaki Kurumlar&quot; gerçek mesafeyle çalışır. Google Maps&#39;te kurumun konumuna sağ tıklayıp koordinatları kopyalayabilirsiniz.</p>
  </div>

  <div>
    <label class="text-sm font-medium">Boylam (lng) <span class="text-xs text-gray-400">opsiyonel</span></label>
    <input type="text" name="lng" value="{{ old('lng', $facility->lng) }}" placeholder="Örn: 29.0670" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Açıklama</label>
    <textarea name="description" rows="4" class="border rounded-lg px-3 py-2 w-full mt-1">{{ old('description', $facility->description) }}</textarea>
  </div>

  <div>
    <label class="text-sm font-medium">Kapasite</label>
    <input type="number" name="capacity" value="{{ old('capacity', $facility->capacity) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Kapak Görseli URL</label>
    <input type="text" name="cover_image" value="{{ old('cover_image', $facility->cover_image) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Min Fiyat</label>
    <input type="number" step="0.01" name="price_min" value="{{ old('price_min', $facility->price_min) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div>
    <label class="text-sm font-medium">Maks Fiyat</label>
    <input type="number" step="0.01" name="price_max" value="{{ old('price_max', $facility->price_max) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="md:col-span-2 -mt-2">
    @php $tier = $facility->priceTier(); @endphp
    <span class="text-xs text-gray-400">Hesaplanan segment:</span>
    @if($tier)
      <span class="{{ $tier['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full ml-1">{{ $tier['emoji'] }} {{ $tier['label'] }}</span>
    @else
      <span class="text-xs text-gray-400 ml-1">Min fiyat girilmeden segment hesaplanamaz.</span>
    @endif
    <a href="{{ route('admin.settings.edit') }}" class="text-xs text-primary underline ml-2">Eşikleri düzenle</a>
  </div>

  <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
    <label class="text-sm font-semibold block mb-3">Bölüme göre özellik önerileri</label>
    <div class="grid md:grid-cols-3 gap-3">
      @foreach($serviceSections as $section)
        <div class="bg-white border rounded-lg p-3">
          <div class="font-semibold text-sm mb-2 flex items-center gap-2">@include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-5 h-5'])<span>{{ $section['title'] }}</span></div>
          <div class="space-y-1">
            @foreach($section['features'] as $feature)
              <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="services[]" value="{{ $feature }}" @checked($selectedServices->contains($feature))>
                <span>{{ $feature }}</span>
              </label>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="md:col-span-2">
    <label class="text-sm font-medium">Ek hizmetler (virgülle ayırın)</label>
    <input type="text" name="services_raw" value="{{ old('services_raw', $selectedServices->diff(collect($serviceSections)->flatMap(fn ($s) => $s['features'])->all())->implode(', ')) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
  </div>

  <div class="flex gap-6 md:col-span-2">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $facility->is_published))> Yayında</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $facility->is_featured))> Öne çıkar</label>
  </div>

  <div class="md:col-span-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
    <div class="flex items-center justify-between gap-3 mb-2">
      <label class="text-sm font-semibold">Demo / Galeri Görselleri</label>
      <span class="text-xs font-semibold rounded-full px-3 py-1 {{ $remainingImages > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">{{ $imageCount }}/10 yüklü</span>
    </div>
    @if($remainingImages > 0)
      <input type="file" name="images[]" multiple accept="image/*" class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
      <p class="text-xs text-gray-500 mt-1">En fazla 10 görsel olabilir. Bu kurum için kalan yükleme hakkı: {{ $remainingImages }}.</p>
    @else
      <div class="rounded-lg bg-white border border-gray-100 p-3 text-sm text-gray-500">10 görsel limiti doldu. Yeni görsel eklemek için önce mevcut görsellerden birini silin.</div>
    @endif
  </div>

  <div class="md:col-span-2">
    <button class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold">Kaydet</button>
  </div>
</form>

@if($facility->exists)
  <div class="max-w-4xl mt-8 space-y-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="font-bold mb-3">Sahiplenme Durumu</h2>
      @if($facility->is_claimed)
        <p class="text-sm text-green-700 font-semibold">Sahiplenilmiş ({{ $facility->claimed_at?->format('d.m.Y') }})</p>
        @foreach($facility->facilityUsers as $fu)
          <p class="text-sm text-gray-600 mt-1">Yetkili: {{ $fu->name }} ({{ $fu->email }}) &middot; {{ $fu->status }}</p>
        @endforeach
      @else
        <p class="text-sm text-gray-500">Bu kurum henüz sahiplenilmedi (ön kayıtlı profil).</p>
      @endif

      @if($facility->claims->isNotEmpty())
        <div class="mt-3">
          <p class="text-xs text-gray-400 mb-1">Başvuru geçmişi:</p>
          @foreach($facility->claims as $claim)
            <a href="{{ route('admin.claims.show', $claim) }}" class="block text-xs text-blue-600">{{ $claim->applicant_name }} &middot; {{ $claim->status }} ({{ $claim->created_at->format('d.m.Y') }})</a>
          @endforeach
        </div>
      @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="font-bold mb-3">Bakiye / Hak (Manuel Düzenleme)</h2>
      <p class="text-sm text-gray-600 mb-3">Ücretsiz Hak: <strong>{{ $facility->free_quote_credits }}</strong> &middot; Bakiye: <strong>{{ number_format($facility->balance,2,',','.') }} TL</strong></p>
      <form method="POST" action="{{ route('admin.facilities.balance.adjust', $facility) }}" class="flex flex-wrap gap-2 items-end">
        @csrf
        <div><label class="text-xs text-gray-500 block">Bakiye Değişimi (TL, +/-)</label><input type="number" step="0.01" name="balance_delta" placeholder="örn: 100 veya -50" class="border rounded-lg px-3 py-1.5 text-sm w-40"></div>
        <div><label class="text-xs text-gray-500 block">Hak Değişimi (+/-)</label><input type="number" name="credits_delta" placeholder="örn: 5 veya -2" class="border rounded-lg px-3 py-1.5 text-sm w-32"></div>
        <div class="flex-1 min-w-[160px]"><label class="text-xs text-gray-500 block">Not</label><input type="text" name="note" placeholder="Sebep" class="border rounded-lg px-3 py-1.5 text-sm w-full"></div>
        <button class="bg-gray-900 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Uygula</button>
      </form>

      <div class="border-t border-gray-100 mt-4 pt-4">
        <p class="text-sm text-gray-600 mb-2">Bu kurum için özel teklif ücreti:
          <strong>{{ $facility->quote_price_override !== null ? number_format($facility->quote_price_override,2,',','.').' TL' : 'Yok (genel ücret geçerli)' }}</strong>
        </p>
        <form method="POST" action="{{ route('admin.facilities.balance.adjust', $facility) }}" class="flex flex-wrap gap-2 items-end">
          @csrf
          <div><label class="text-xs text-gray-500 block">Özel Teklif Ücreti (TL)</label><input type="number" step="0.01" min="0" name="quote_price_override" placeholder="örn: 150" class="border rounded-lg px-3 py-1.5 text-sm w-40"></div>
          <button class="bg-gray-900 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Kaydet</button>
          @if($facility->quote_price_override !== null)
            <button type="submit" name="clear_quote_price_override" value="1" class="bg-white border border-gray-300 text-gray-600 px-4 py-1.5 rounded-lg text-sm font-semibold">Genel ücrete dön</button>
          @endif
        </form>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between gap-3 mb-3">
        <h2 class="font-bold">Mevcut Görseller</h2>
        <span class="text-xs font-semibold rounded-full bg-gray-100 text-gray-600 px-3 py-1">{{ $imageCount }}/10</span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        @foreach($facility->images->take(10) as $img)
          <div class="relative">
            <img src="{{ asset('storage/'.$img->path) }}" class="rounded-lg h-24 w-full object-cover border border-gray-100">
            <form method="POST" action="{{ route('admin.facilities.image.destroy', $img) }}" class="absolute top-1 right-1">
              @csrf @method('DELETE')
              <button class="bg-white/90 text-red-600 text-xs px-2 py-0.5 rounded">Sil</button>
            </form>
          </div>
        @endforeach
        @for($i = $imageCount; $i < 10; $i++)
          <div class="h-24 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-xs text-gray-400 text-center px-2">Görsel alanı<br>{{ $i + 1 }}/10</div>
        @endfor
      </div>
    </div>
  </div>
@endif
@endsection
