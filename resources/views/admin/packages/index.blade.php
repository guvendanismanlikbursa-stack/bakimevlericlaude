@extends('admin.layout')
@section('title', 'Paketler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Paket / Üyelik Kataloğu</h1>

@if($errors->any())
  <div class="bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4">{{ implode(' ', $errors->all()) }}</div>
@endif

{{-- 18 Ekim 2026: kullanıcının talebi - İyilik Hareketi üyelik programı.
     Yayına alınana kadar sayfa, rozetler, panel üyelik paketleri ve ücretsiz
     teklif kuralı ailelere/kurumlara görünmez; admin önizleyebilir. --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-8 border-2" style="border-color:{{ $membership['live'] ? '#86efac' : '#fde047' }};">
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div>
      <div class="font-black text-lg">💛 İyilik Hareketi Üyelik Programı</div>
      <div class="text-sm mt-1">
        Durum:
        @if($membership['live'])
          <span class="font-bold text-green-700">Yayında</span>
        @else
          <span class="font-bold text-yellow-700">Yayında değil (yalnızca admin önizlemesi)</span>
        @endif
        · Aktif üye kurum: <strong>{{ $membership['active_members'] }}</strong>
      </div>
      <div class="text-sm mt-2 flex gap-4 flex-wrap">
        <a href="{{ brand_route('pages.membership') }}" target="_blank" class="text-primary font-semibold underline">Bilgilendirme sayfasını önizle</a>
        <span class="text-gray-500">Kuruma özel teklif sayfası: Kurum Davetleri ekranında her kurumun yanındaki "Teklif" bağlantısı.</span>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.membership.toggle') }}" onsubmit="return confirm('{{ $membership['live'] ? 'İyilik Hareketi yayından kaldırılsın mı? Sayfa, rozetler, üyelik paketleri ve ücretsiz teklif kuralı gizlenecek.' : 'İyilik Hareketi 3 sitede birden yayına alınsın mı? Sayfa, rozetler, kurum panelindeki üyelik paketleri ve üyelere ücretsiz teklif kuralı hemen aktif olacak.' }}');">
      @csrf
      <input type="hidden" name="live" value="{{ $membership['live'] ? 0 : 1 }}">
      <button class="{{ $membership['live'] ? 'bg-gray-700' : 'bg-green-600' }} text-white px-4 py-2 rounded-lg text-sm font-bold">{{ $membership['live'] ? 'Yayından Kaldır' : 'Yayına Al' }}</button>
    </form>
  </div>

  <form method="POST" action="{{ route('admin.membership.impact') }}" enctype="multipart/form-data" class="mt-5 pt-5 border-t grid md:grid-cols-4 gap-3 items-end">
    @csrf
    <div class="md:col-span-4 text-sm font-bold">Şeffaflık paneli ve hastane ofisi görseli <span class="font-normal text-gray-500">(boş bırakılan sayaç sayfada görünmez)</span></div>
    <div>
      <label class="text-xs text-gray-500 block">Dönem yazısı</label>
      <input type="text" name="impact_period" value="{{ $membership['impact_period'] }}" placeholder="Bu ay / Ekim 2026" class="border rounded-lg px-3 py-2 w-full text-sm">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Bakımı üstlenilen hasta sayısı</label>
      <input type="number" min="0" name="impact_patients" value="{{ $membership['impact_patients'] }}" class="border rounded-lg px-3 py-2 w-full text-sm">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Toplam bakım saati</label>
      <input type="number" min="0" name="impact_hours" value="{{ $membership['impact_hours'] }}" class="border rounded-lg px-3 py-2 w-full text-sm">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Hastane ofisi görseli (jpg/png/webp)</label>
      <input type="file" name="office_image" accept=".jpg,.jpeg,.png,.webp" class="border rounded-lg px-3 py-1.5 w-full text-sm">
    </div>
    @if($membership['office_image'])
      <div class="md:col-span-4 flex items-center gap-3 text-sm">
        <img src="{{ facility_asset($membership['office_image']) }}" alt="Ofis görseli" class="h-16 rounded-lg">
        <label class="flex items-center gap-2"><input type="checkbox" name="remove_office_image" value="1"> Görseli kaldır</label>
      </div>
    @endif
    <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-4">Şeffaflık Bilgilerini Kaydet</button>
  </form>
</div>

{{-- 1 Eylul 2026: kullanicinin talebi - backend update() zaten calisiyordu
     ama ekranda "Duzenle" hic yoktu, ContentPageController'daki ?edit=
     deseni buraya da uygulandi. --}}
@if($editingPackage)
  <p class="text-sm font-semibold text-amber-700 mb-2">"{{ $editingPackage->name }}" düzenleniyor. <a href="{{ route('admin.packages.index') }}" class="underline">Vazgeç, yeni paket ekle</a></p>
@endif
@php($editingKind = old('kind', $editingPackage?->kind ?? 'credit'))
<form method="POST" action="{{ $editingPackage ? route('admin.packages.update', $editingPackage) : route('admin.packages.store') }}" class="bg-white rounded-xl shadow-sm p-5 grid md:grid-cols-2 gap-3 mb-8">
  @csrf
  @if($editingPackage) @method('PUT') @endif
  <div class="md:col-span-2">
    <label class="text-xs text-gray-500 block">Paket türü</label>
    <select name="kind" class="border rounded-lg px-3 py-2 text-sm" onchange="document.querySelectorAll('.js-membership-field').forEach(el => el.style.display = this.value === 'membership' ? '' : 'none')">
      <option value="credit" @selected($editingKind === 'credit')>Bakiye paketi (tutar bakiyeye eklenir)</option>
      <option value="membership" @selected($editingKind === 'membership')>İyilik Hareketi üyeliği (süre boyunca ücretsiz teklif + rozet)</option>
    </select>
  </div>
  <input type="text" name="name" value="{{ old('name', $editingPackage?->name) }}" placeholder="Paket adı" required class="border rounded-lg px-3 py-2">
  <input type="number" step="0.01" name="price" value="{{ old('price', $editingPackage?->price) }}" placeholder="Fiyat (₺)" required class="border rounded-lg px-3 py-2">

  <div class="js-membership-field md:col-span-2 grid md:grid-cols-2 gap-3" style="{{ $editingKind === 'membership' ? '' : 'display:none' }}">
    <div>
      <label class="text-xs text-gray-500 block">Süre (ay)</label>
      <input type="number" min="1" max="60" name="months" value="{{ old('months', $editingPackage?->months) }}" placeholder="1 / 6 / 12" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Ücretsiz ay yazısı (ör. "3 ay ücretsiz")</label>
      <input type="text" name="free_months_label" value="{{ old('free_months_label', $editingPackage?->free_months_label) }}" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Rozet adı</label>
      <input type="text" name="badge_label" value="{{ old('badge_label', $editingPackage?->badge_label) }}" placeholder="Altın Kalpli Kurum" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Rozet simgesi</label>
      <input type="text" name="badge_emoji" value="{{ old('badge_emoji', $editingPackage?->badge_emoji) }}" placeholder="💛" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <label class="text-xs text-gray-500 block">Kısa tanıtım mesajı</label>
      <input type="text" name="tagline" value="{{ old('tagline', $editingPackage?->tagline) }}" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <label class="text-xs text-gray-500 block">Pakete özel ek haklar (her satıra bir madde; tüm üyeliklerde ortak olan ücretsiz teklif ve panel özellikleri otomatik yazılır)</label>
      <textarea name="features" rows="3" class="border rounded-lg px-3 py-2 w-full">{{ old('features', $editingPackage?->features) }}</textarea>
    </div>
    <div>
      <label class="text-xs text-gray-500 block">Sadece bu ilde satılsın (boş = tüm iller)</label>
      <select name="only_city_slug" class="border rounded-lg px-3 py-2 w-full text-sm">
        <option value="">Tüm iller</option>
        @foreach($cities as $city)
          <option value="{{ $city->slug }}" @selected(old('only_city_slug', $editingPackage?->only_city_slug) === $city->slug)>{{ $city->name }}</option>
        @endforeach
      </select>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_premium" value="1" @checked(old('is_premium', $editingPackage?->is_premium))> Premium (çerçeveli gösterilir)</label>
  </div>

  <input type="number" name="bonus_quote_credits" value="{{ old('bonus_quote_credits', $editingPackage?->bonus_quote_credits) }}" placeholder="Bonus teklif hakkı (bakiye paketi)" class="border rounded-lg px-3 py-2">
  <input type="number" name="duration_days" value="{{ old('duration_days', $editingPackage?->duration_days) }}" placeholder="Süre (gün, bakiye paketi, opsiyonel)" class="border rounded-lg px-3 py-2">
  <input type="number" name="sort_order" value="{{ old('sort_order', $editingPackage?->sort_order) }}" placeholder="Sıra" class="border rounded-lg px-3 py-2">
  <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingPackage?->is_active ?? true))> Yayında</label>
  <textarea name="description" placeholder="Açıklama (iç not / bakiye paketi açıklaması)" rows="2" class="border rounded-lg px-3 py-2 md:col-span-2">{{ old('description', $editingPackage?->description) }}</textarea>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-2">{{ $editingPackage ? 'Güncelle' : 'Ekle' }}</button>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @foreach($packages as $package)
    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">
          @if($package->isMembership())<span class="text-xs font-black px-2 py-0.5 rounded-full mr-1" style="background:#fae8ff;color:#a21caf;">{{ $package->badge_emoji }} {{ $package->badge_label }}</span>@endif
          {{ $package->name }}
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
          @if($package->isMembership())
            Üyelik · {{ number_format($package->price,2,',','.') }}₺ · {{ $package->months }} ay{{ $package->free_months_label ? ' · '.$package->free_months_label : '' }}{{ $package->only_city_slug ? ' · sadece '.$package->only_city_slug : '' }} · {{ $package->is_active ? 'Aktif' : 'Pasif' }}
          @else
            Bakiye paketi · {{ number_format($package->price,2,',','.') }}₺ · Bonus hak: {{ $package->bonus_quote_credits }} · {{ $package->duration_days ? $package->duration_days.' gün' : 'süresiz' }} · {{ $package->is_active ? 'Yayında' : 'Pasif' }}
          @endif
        </div>
      </div>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        <a href="{{ route('admin.packages.index', ['edit' => $package->id]) }}" class="text-primary text-sm font-semibold whitespace-nowrap">Düzenle</a>
        <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Silinsin mi?');" class="inline">@csrf @method('DELETE')<button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button></form>
      </div>
    </div>
  @endforeach
</div>
<p class="text-xs text-gray-500 mt-3">Fiyat değişikliği yalnızca yeni talepleri etkiler; mevcut üyelerin ödediği tutar ve bitiş tarihi korunur.</p>
@endsection
