@extends('layouts.brand')

@section('content')
@php
  $selectedServices = collect($facility->services ?? []);
  $imageCount = $facility->images->count();
  $remainingImages = max(0, 10 - $imageCount);
@endphp
<div class="max-w-4xl mx-auto px-4 py-10">
  <a href="{{ brand_route('facility.dashboard') }}" class="text-sm text-gray-500">&larr; Panele d&ouml;n</a>
  <h1 class="text-2xl font-bold mt-2 mb-2">Kurum Bilgilerimi D&uuml;zenle</h1>
  @if($serviceSection)
    <p class="text-sm text-gray-500 mb-6">Bu kurum <strong>{{ $serviceSection['title'] }}</strong> b&ouml;l&uuml;m&uuml;nde hizmet veriyor. Ana sayfa filtreleri ve kuruma &ouml;zel detay alanlar&#305; bu b&ouml;l&uuml;me g&ouml;re haz&#305;rlan&#305;r.</p>
  @endif

  @if(session('success'))
    <div class="mb-5 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
      <div class="font-black mb-1">L&uuml;tfen alanlar&#305; kontrol edin.</div>
      <ul class="list-disc pl-5 space-y-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <div class="text-sm font-semibold text-gray-500">Profil kalite puan&#305;<span class="sr-only">Profil kalite puani</span></div>
        <div class="text-3xl font-black text-gray-950 mt-1">{{ $profileQuality['score'] }}/100</div>
        <p class="text-sm text-gray-500 mt-1">Tam profil daha fazla ziyaret&ccedil;i g&uuml;veni, daha iyi teklif d&ouml;n&uuml;&scedil;&uuml; ve daha g&uuml;&ccedil;l&uuml; SEO sinyali verir.</p>
      </div>
      <div class="w-full md:w-56">
        <div class="h-3 rounded-full bg-gray-100 overflow-hidden"><div class="h-full bg-primary" style="width: {{ $profileQuality['score'] }}%"></div></div>
        <div class="text-xs text-gray-400 mt-2">{{ $profileQuality['completed'] }}/{{ $profileQuality['total'] }} alan tamamland&#305;<span class="sr-only">alan tamamlandi</span></div>
      </div>
    </div>
    @if($profileQuality['missing'])
      <div class="mt-4 flex flex-wrap gap-2">
        @foreach(array_slice($profileQuality['missing'], 0, 6) as $missing)
          <span class="rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-semibold">{{ $missing }}</span>
        @endforeach
      </div>
    @endif
  </div>

  <form method="POST" action="{{ brand_route('facility.profile.update') }}" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 mb-8">
    @csrf @method('PUT')
    <div class="md:col-span-2">
      <label class="text-sm font-medium">Kurum Ad&#305;</label>
      <input type="text" name="name" value="{{ old('name', $facility->name) }}" required class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label class="text-sm font-medium">&#350;ehir</label>
      <select name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
        @foreach($cities as $city)<option value="{{ $city->id }}" @selected(old('city_id', $facility->city_id) == $city->id)>{{ $city->name }}</option>@endforeach
      </select>
    </div>
    <div>
      <label class="text-sm font-medium">&#304;l&ccedil;e</label>
      <input type="text" name="district" value="{{ old('district', $facility->district) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label class="text-sm font-medium">Telefon</label>
      <input type="text" name="phone" value="{{ old('phone', $facility->phone) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label class="text-sm font-medium">Kapasite</label>
      <input type="number" name="capacity" value="{{ old('capacity', $facility->capacity) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium">Adres</label>
      <input type="text" name="address" value="{{ old('address', $facility->address) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium">A&ccedil;&#305;klama</label>
      <textarea name="description" rows="4" class="border rounded-lg px-3 py-2 w-full mt-1">{{ old('description', $facility->description) }}</textarea>
    </div>
    <div>
      <label class="text-sm font-medium">Min Fiyat</label>
      <input type="number" step="0.01" name="price_min" value="{{ old('price_min', $facility->price_min) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div>
      <label class="text-sm font-medium">Maks Fiyat</label>
      <input type="number" step="0.01" name="price_max" value="{{ old('price_max', $facility->price_max) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>

    @if($serviceSection)
      <div class="md:col-span-2 rounded-xl border border-gray-100 bg-gray-50 p-4">
        <div class="flex items-center gap-2 mb-2">
          @include('themes._shared.partials.section-icon', ['section' => $serviceSection, 'class' => 'w-5 h-5'])
          <label class="text-sm font-black">Ana sayfa filtrelerinde g&ouml;r&uuml;nen {{ $serviceSection['title'] }} &ouml;zellikleri</label>
        </div>
        <p class="text-xs text-gray-500 mb-3">Burada se&ccedil;ilen &ouml;zellikler ziyaret&ccedil;inin ana sayfa ve kurum listesi filtresinde kurumunuzu bulmas&#305;n&#305; sa&#287;lar.</p>
        <div class="grid sm:grid-cols-2 gap-2">
          @foreach($serviceSection['features'] as $feature)
            <label class="flex items-center gap-2 text-sm bg-white border rounded-lg px-3 py-2">
              <input type="checkbox" name="services[]" value="{{ $feature }}" @checked($selectedServices->contains($feature))>
              <span>{{ $feature }}</span>
            </label>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($sectionDetailFields))
      <div class="md:col-span-2 rounded-xl border border-gray-100 bg-white p-4">
        <div class="text-sm font-black text-gray-950 mb-1">Kuruma &ouml;zel {{ $serviceSection['title'] }} detaylar&#305;</div>
        <p class="text-xs text-gray-500 mb-4">Bu alanlar kurum detay sayfas&#305;nda ziyaret&ccedil;iye daha net bilgi vermek ve admin taraf&#305;ndan denetlenebilir profil olu&#351;turmak i&ccedil;indir.</p>
        <div class="grid md:grid-cols-2 gap-3">
          @foreach($sectionDetailFields as $field)
            <label class="block">
              <span class="text-sm font-medium">{{ $field['label'] }}</span>
              <input type="text" name="section_details[{{ $field['key'] }}]" value="{{ old('section_details.'.$field['key'], $sectionDetails[$field['key']] ?? '') }}" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Kurumunuza uygun bilgi girin">
            </label>
          @endforeach
        </div>
      </div>
    @endif

    <div class="md:col-span-2">
      <label class="text-sm font-medium">Ek hizmetler (virg&uuml;lle ay&#305;r&#305;n)</label>
      <input type="text" name="services_raw" value="{{ old('services_raw', $selectedServices->diff($serviceSection['features'] ?? [])->implode(', ')) }}" class="border rounded-lg px-3 py-2 w-full mt-1">
    </div>
    <div class="md:col-span-2">
      <button class="bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold">Kaydet</button>
    </div>
  </form>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
      <div>
        <h2 class="font-bold">Kurum Galerisi</h2>
        <p class="text-sm text-gray-500">En fazla 10 g&ouml;rsel eklenebilir. &#350;u an {{ $imageCount }}/10 g&ouml;rsel y&uuml;kl&uuml;.</p>
      </div>
      <span class="text-xs font-semibold rounded-full px-3 py-1 {{ $remainingImages > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">Kalan hak: {{ $remainingImages }}</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
      @foreach($facility->images->take(10) as $img)
        <div class="relative group">
          <img src="{{ asset('storage/'.$img->path) }}" class="rounded-lg h-28 w-full object-cover border border-gray-100" alt="{{ $facility->name }} g&ouml;rseli">
          <form method="POST" action="{{ brand_route('facility.profile.image.destroy', $img) }}" class="absolute top-1 right-1">
            @csrf @method('DELETE')
            <button class="bg-white/90 text-red-600 text-xs px-2 py-0.5 rounded">Sil</button>
          </form>
        </div>
      @endforeach
      @for($i = $imageCount; $i < 10; $i++)
        <div class="h-28 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-center px-2 text-xs text-gray-400">G&ouml;rsel alan&#305;<br>{{ $i + 1 }}/10</div>
      @endfor
    </div>

    @if($remainingImages > 0)
      <form method="POST" action="{{ brand_route('facility.profile.image.store') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
        @csrf
        <input type="file" name="images[]" multiple accept="image/*" required class="border rounded-lg px-3 py-2 text-sm flex-1">
        <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">G&ouml;rsel Ekle</button>
      </form>
      <p class="text-xs text-gray-400 mt-2">Tek seferde kalan hak kadar JPG, PNG veya WEBP g&ouml;rsel y&uuml;kleyebilirsiniz.</p>
    @else
      <div class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-sm text-gray-500">10 g&ouml;rsel limiti doldu. Yeni g&ouml;rsel eklemek i&ccedil;in &ouml;nce mevcut g&ouml;rsellerden birini silin.</div>
    @endif
  </div>
</div>
@endsection
