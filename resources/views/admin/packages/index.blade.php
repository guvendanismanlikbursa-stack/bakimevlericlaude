@extends('admin.layout')
@section('title', 'Paketler')

@section('content')
{{-- 18 Ekim 2026: kullanıcının talebi ("çok karışık, hiçbir şey anlamadım") -
     ekran 4 ayrı, sade bölüme ayrıldı: yayın durumu / üyelik paketleri /
     bakiye paketleri / şeffaflık sayacı. Düzenleme formu yalnızca "Düzenle"
     veya "Yeni paket" tıklanınca ve yalnızca o paket türünün alanlarıyla açılır. --}}
@php
  $membershipPackages = $packages->filter(fn ($p) => $p->isMembership())->values();
  $creditPackages = $packages->reject(fn ($p) => $p->isMembership())->values();
  $formKind = $editingPackage ? ($editingPackage->isMembership() ? 'membership' : 'credit') : (in_array(request('yeni'), ['membership', 'credit'], true) ? request('yeni') : null);
@endphp

<h1 class="text-2xl font-bold mb-6">Paketler</h1>

@if($errors->any())
  <div class="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4">{{ implode(' ', $errors->all()) }}</div>
@endif

{{-- 1) YAYIN DURUMU --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6 border-2" style="border-color:{{ $membership['live'] ? '#86efac' : '#fde047' }};">
  <div class="flex items-center justify-between gap-4 flex-wrap">
    <div>
      <div class="font-black text-lg">💛 İyilik Hareketi</div>
      @if($membership['live'])
        <div class="text-sm font-bold text-green-700 mt-1">● Yayında — aileler ve kurumlar görüyor</div>
      @else
        <div class="text-sm font-bold mt-1" style="color:#a16207;">● Yayında değil — sadece siz önizleyebilirsiniz</div>
      @endif
      <div class="text-sm text-gray-500 mt-1">Aktif üye kurum sayısı: <strong class="text-gray-900">{{ $membership['active_members'] }}</strong></div>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
      <a href="{{ membership_signed_preview_url('pages.membership') }}" target="_blank" class="border rounded-lg px-4 py-2 text-sm font-bold text-gray-700">👁 Sayfayı Önizle</a>
      <form method="POST" action="{{ route('admin.membership.toggle') }}" onsubmit="return confirm('{{ $membership['live'] ? 'İyilik Hareketi yayından kaldırılsın mı?' : 'İyilik Hareketi 3 sitede birden yayına alınsın mı?' }}');">
        @csrf
        <input type="hidden" name="live" value="{{ $membership['live'] ? 0 : 1 }}">
        <button class="{{ $membership['live'] ? 'bg-gray-700' : 'bg-green-600' }} text-white px-5 py-2 rounded-lg text-sm font-bold">{{ $membership['live'] ? 'Yayından Kaldır' : 'Yayına Al' }}</button>
      </form>
    </div>
  </div>
</div>

{{-- DÜZENLEME / YENİ PAKET FORMU (yalnızca istenince) --}}
@if($formKind)
  <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border-2 border-gray-900">
    <div class="flex items-center justify-between gap-3 mb-4">
      <div class="font-black">
        {{ $editingPackage ? '"'.$editingPackage->name.'" düzenleniyor' : ($formKind === 'membership' ? 'Yeni üyelik paketi' : 'Yeni bakiye paketi') }}
      </div>
      <a href="{{ route('admin.packages.index') }}" class="text-sm font-semibold text-gray-500 underline">Vazgeç</a>
    </div>
    <form method="POST" action="{{ $editingPackage ? route('admin.packages.update', $editingPackage) : route('admin.packages.store') }}" class="grid md:grid-cols-2 gap-4">
      @csrf
      @if($editingPackage) @method('PUT') @endif
      <input type="hidden" name="kind" value="{{ $formKind }}">

      <div>
        <label class="text-sm font-semibold block mb-1">Paket adı</label>
        <input type="text" name="name" value="{{ old('name', $editingPackage?->name) }}" required class="border rounded-lg px-3 py-2 w-full">
      </div>
      <div>
        <label class="text-sm font-semibold block mb-1">Fiyat (TL)</label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $editingPackage?->price) }}" required class="border rounded-lg px-3 py-2 w-full">
      </div>

      @if($formKind === 'membership')
        <div>
          <label class="text-sm font-semibold block mb-1">Süre (ay)</label>
          <input type="number" min="1" max="60" name="months" value="{{ old('months', $editingPackage?->months) }}" required class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div>
          <label class="text-sm font-semibold block mb-1">Kampanya yazısı <span class="font-normal text-gray-500">(ör. "3 ay ücretsiz", boş bırakılabilir)</span></label>
          <input type="text" name="free_months_label" value="{{ old('free_months_label', $editingPackage?->free_months_label) }}" class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div>
          <label class="text-sm font-semibold block mb-1">Rozet adı</label>
          <input type="text" name="badge_label" value="{{ old('badge_label', $editingPackage?->badge_label) }}" required class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div>
          <label class="text-sm font-semibold block mb-1">Rozet simgesi</label>
          <input type="text" name="badge_emoji" value="{{ old('badge_emoji', $editingPackage?->badge_emoji) }}" placeholder="💛" class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-semibold block mb-1">Kısa açıklama <span class="font-normal text-gray-500">(kartın altında görünür)</span></label>
          <input type="text" name="tagline" value="{{ old('tagline', $editingPackage?->tagline) }}" class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-semibold block mb-1">Bu pakete özel ek haklar <span class="font-normal text-gray-500">(her satıra bir madde, boş bırakılabilir)</span></label>
          <textarea name="features" rows="3" class="border rounded-lg px-3 py-2 w-full">{{ old('features', $editingPackage?->features) }}</textarea>
        </div>
        <div>
          <label class="text-sm font-semibold block mb-1">Hangi ilde satılsın?</label>
          <select name="only_city_slug" class="border rounded-lg px-3 py-2 w-full">
            <option value="">Tüm iller</option>
            @foreach($cities as $city)
              <option value="{{ $city->slug }}" @selected(old('only_city_slug', $editingPackage?->only_city_slug) === $city->slug)>Sadece {{ $city->name }}</option>
            @endforeach
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm pb-2" style="align-self:end;"><input type="checkbox" name="is_premium" value="1" @checked(old('is_premium', $editingPackage?->is_premium))> Premium paket (altın çerçeveli gösterilir)</label>
      @else
        <div>
          <label class="text-sm font-semibold block mb-1">Hediye teklif hakkı <span class="font-normal text-gray-500">(adet)</span></label>
          <input type="number" min="0" name="bonus_quote_credits" value="{{ old('bonus_quote_credits', $editingPackage?->bonus_quote_credits) }}" class="border rounded-lg px-3 py-2 w-full">
        </div>
        <div>
          <label class="text-sm font-semibold block mb-1">Açıklama</label>
          <input type="text" name="description" value="{{ old('description', $editingPackage?->description) }}" class="border rounded-lg px-3 py-2 w-full">
        </div>
      @endif

      <div>
        <label class="text-sm font-semibold block mb-1">Sıra <span class="font-normal text-gray-500">(küçük olan önce görünür)</span></label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $editingPackage?->sort_order) }}" class="border rounded-lg px-3 py-2 w-full">
      </div>
      <label class="flex items-center gap-2 text-sm pb-2" style="align-self:end;"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingPackage?->is_active ?? true))> Aktif (kurumlar seçebilir)</label>

      <button class="bg-gray-900 text-white px-4 py-3 rounded-lg text-sm font-bold md:col-span-2">Kaydet</button>
    </form>
  </div>
@endif

{{-- 2) ÜYELİK PAKETLERİ --}}
<div class="mb-8">
  <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
    <div>
      <h2 class="text-lg font-black">Üyelik Paketleri</h2>
      <p class="text-sm text-gray-500">Üye kurum, süre boyunca ücretsiz teklif verir ve kartında rozeti görünür.</p>
    </div>
    <a href="{{ route('admin.packages.index', ['yeni' => 'membership']) }}" class="text-sm font-bold underline" style="color:#a21caf;">+ Yeni üyelik paketi</a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @forelse($membershipPackages as $package)
      <div class="bg-white rounded-xl shadow-sm p-4 flex flex-col {{ $package->is_active ? '' : 'opacity-60' }}">
        <div class="text-xs font-black px-2 py-1 rounded-full self-start" style="background:#fae8ff;color:#a21caf;">{{ $package->badge_emoji }} {{ $package->badge_label }}</div>
        <div class="font-bold mt-3">{{ $package->name }}</div>
        <div class="text-2xl font-black my-1">{{ number_format($package->price, 0, ',', '.') }} TL</div>
        <div class="text-sm text-gray-600">{{ $package->months }} ay{{ $package->free_months_label ? ' · '.$package->free_months_label : '' }}</div>
        @if($package->only_city_slug)<div class="text-xs font-bold text-amber-700 mt-1">Sadece {{ mb_convert_case($package->only_city_slug, MB_CASE_TITLE, 'UTF-8') }}</div>@endif
        @unless($package->is_active)<div class="text-xs font-bold text-gray-500 mt-1">Pasif</div>@endunless
        <div class="mt-auto pt-3 flex items-center justify-between">
          <a href="{{ route('admin.packages.index', ['edit' => $package->id]) }}" class="bg-gray-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Düzenle</a>
          <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('{{ $package->name }} silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-xs font-semibold">Sil</button></form>
        </div>
      </div>
    @empty
      <p class="text-sm text-gray-400">Üyelik paketi yok.</p>
    @endforelse
  </div>
  <p class="text-xs text-gray-500 mt-2">Fiyat değişikliği sadece yeni üyelikleri etkiler; mevcut üyelerin tutarı ve bitiş tarihi değişmez.</p>
</div>

{{-- 3) BAKİYE PAKETLERİ --}}
<div class="mb-8">
  <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
    <div>
      <h2 class="text-lg font-black">Bakiye Paketleri</h2>
      <p class="text-sm text-gray-500">Üye olmayan sahipli kurumlar için: ödenen tutar kurumun bakiyesine eklenir, teklif başına bakiyeden düşer.</p>
    </div>
    <a href="{{ route('admin.packages.index', ['yeni' => 'credit']) }}" class="text-sm font-bold underline text-gray-700">+ Yeni bakiye paketi</a>
  </div>
  <div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
    @forelse($creditPackages as $package)
      <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
        <div>
          <div class="font-bold">{{ $package->name }} — {{ number_format($package->price, 0, ',', '.') }} TL</div>
          <div class="text-xs text-gray-500 mt-0.5">Hediye teklif hakkı: {{ $package->bonus_quote_credits }} · {{ $package->is_active ? 'Aktif' : 'Pasif' }}</div>
        </div>
        <div class="flex items-center gap-3">
          <a href="{{ route('admin.packages.index', ['edit' => $package->id]) }}" class="bg-gray-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Düzenle</a>
          <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('{{ $package->name }} silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-xs font-semibold">Sil</button></form>
        </div>
      </div>
    @empty
      <p class="p-4 text-sm text-gray-400">Bakiye paketi yok.</p>
    @endforelse
  </div>
</div>

{{-- 4) ŞEFFAFLIK SAYACI VE OFİS FOTOĞRAFI --}}
<details class="bg-white rounded-xl shadow-sm p-5" @if($errors->has('office_image')) open @endif>
  <summary class="font-black cursor-pointer select-none">Şeffaflık sayacı ve hastane ofisi fotoğrafı <span class="font-normal text-sm text-gray-500">(tıklayıp açın)</span></summary>
  <form method="POST" action="{{ route('admin.membership.impact') }}" enctype="multipart/form-data" class="mt-4 grid md:grid-cols-2 gap-4">
    @csrf
    <p class="md:col-span-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-3">Buraya yazdığınız sayı, İyilik Hareketi sayfasında ve kurumlara özel teklif sayfalarında <strong>herkese aynı</strong> görünür: "Ekim 2026'da üyelerimiz sayesinde 14 hastamızın bakımı üstlenildi" gibi. Gerçekte bakımı yapılan hasta sayısını siz girersiniz; boş bırakırsanız hiç görünmez.</p>
    <div>
      <label class="text-sm font-semibold block mb-1">Dönem <span class="font-normal text-gray-500">(ör. "Ekim 2026")</span></label>
      <input type="text" name="impact_period" value="{{ $membership['impact_period'] }}" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label class="text-sm font-semibold block mb-1">Bakımı üstlenilen hasta sayısı</label>
      <input type="number" min="0" name="impact_patients" value="{{ $membership['impact_patients'] }}" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-semibold block mb-1">Hastane ofisi fotoğrafı <span class="font-normal text-gray-500">(İyilik Hareketi sayfasında görünür)</span></label>
      <input type="file" name="office_image" accept=".jpg,.jpeg,.png,.webp" class="border rounded-lg px-3 py-1.5 w-full text-sm">
    </div>
    @if($membership['office_image'])
      <div class="md:col-span-2 flex items-center gap-3 text-sm">
        <img src="{{ facility_asset($membership['office_image']) }}" alt="Ofis fotoğrafı" class="h-16 rounded-lg">
        <label class="flex items-center gap-2"><input type="checkbox" name="remove_office_image" value="1"> Fotoğrafı kaldır</label>
      </div>
    @endif
    <button class="bg-gray-900 text-white px-4 py-3 rounded-lg text-sm font-bold md:col-span-2">Kaydet</button>
  </form>
</details>
@endsection
