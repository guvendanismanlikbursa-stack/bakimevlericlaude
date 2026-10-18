@extends('admin.layout')
@section('title', 'Pazarlamacılar')

@section('content')
<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-bold">Pazarlamacılar</h1>
  <div class="flex gap-4">
    <a href="{{ route('admin.marketers.funnel') }}" class="text-sm font-semibold text-gray-700 underline">Satış hunisi →</a>
    <a href="{{ route('admin.marketers.conversations') }}" class="text-sm font-semibold text-gray-700 underline">Tüm görüşme kayıtlarını gör →</a>
  </div>
</div>
<p class="text-sm text-gray-500 mb-6">CRM paneline (telefon + şifre ile) sadece buradan oluşturduğunuz hesaplar girebilir. Şifre sadece oluşturulduğu/sıfırlandığı an gösterilir, sonra tekrar görüntülenemez.</p>

@if(session('success'))
  <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.marketers.store') }}" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-end gap-3">
  @csrf
  <div>
    <label class="block text-xs font-semibold text-gray-500 mb-1">Ad Soyad</label>
    <input type="text" name="name" value="{{ old('name') }}" required class="border rounded-lg px-3 py-2 text-sm">
    @error('name')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="block text-xs font-semibold text-gray-500 mb-1">Telefon</label>
    <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="05xx xxx xx xx" class="border rounded-lg px-3 py-2 text-sm">
    @error('phone')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
  <button class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">Pazarlamacı Ekle</button>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($marketers as $marketer)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $marketer->name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">📞 {{ $marketer->phone }} · {{ $marketer->notes_count }} not · son giriş: {{ $marketer->last_login_at?->format('d.m.Y H:i') ?? '—' }}</div>
        <div class="flex items-center gap-2 mt-1 text-xs">
          @if($marketer->is_active)<span class="text-green-700 font-semibold">Aktif</span>@else<span class="text-red-700 font-semibold">Devre Dışı</span>@endif
          @if($marketer->is_logged_in)<span class="text-green-700 font-semibold">● Oturum Aktif</span>@else<span class="text-gray-400 font-semibold">● Oturum Pasif</span>@endif
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap justify-end">
        <form method="POST" action="{{ route('admin.marketers.reset-password', $marketer) }}" class="inline" onsubmit="return confirm('Bu pazarlamacı için yeni bir şifre üretilsin mi? Eski şifre geçersiz olur.');">
          @csrf
          <button class="text-blue-600 text-sm font-semibold whitespace-nowrap">Şifre Sıfırla</button>
        </form>
        <form method="POST" action="{{ route('admin.marketers.toggle-active', $marketer) }}" class="inline">
          @csrf
          <button class="text-sm font-semibold whitespace-nowrap {{ $marketer->is_active ? 'text-red-600' : 'text-green-700' }}">{{ $marketer->is_active ? 'Devre Dışı Bırak' : 'Aktif Et' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.marketers.destroy', $marketer) }}" class="inline" onsubmit="return confirm('{{ $marketer->name }} silinsin mi? Bir daha asla giriş yapamaz. Geçmiş görüşme notları (Tüm Görüşmeler ekranında) korunur.');">
          @csrf
          @method('DELETE')
          <button class="text-sm font-semibold whitespace-nowrap text-red-800">Sil</button>
        </form>
      </div>
    </div>

    {{-- 15 Ekim 2026: kullanicinin talebi - pazarlamaciya il/ilce/kategori
         bazinda bolge/kapsam atamasi. Hicbir kutu secilmezse kisitlama
         kaldirilir, pazarlamaci her seyi gorur (bkz. Marketer::
         hasScopeRestriction()). --}}
    <details class="px-4 pb-4 -mt-1">
      <summary class="text-sm font-semibold text-gray-600 cursor-pointer select-none">
        Kapsam
        @if($marketer->hasScopeRestriction())
          <span class="text-amber-700 font-normal">(sınırlı)</span>
        @else
          <span class="text-gray-400 font-normal">(sınırsız - tümünü görüyor)</span>
        @endif
        @if($marketer->can_view_offer_page)<span class="font-normal" style="color:#a21caf;">· 💛 teklif sayfası açık</span>@endif
      </summary>
      <form method="POST" action="{{ route('admin.marketers.update-scope', $marketer) }}" class="js-scope-form mt-3 bg-gray-50 rounded-lg p-3 space-y-3" data-district-map='@json($districtMap)'>
        @csrf
        <div>
          <div class="text-xs font-semibold text-gray-500 mb-1">İller (seçili yoksa tüm iller)</div>
          <div class="flex flex-wrap gap-2 max-h-28 overflow-y-auto border rounded-lg p-2 bg-white">
            @foreach($cities as $city)
              <label class="inline-flex items-center gap-1 text-xs bg-gray-100 rounded px-2 py-1">
                <input type="checkbox" class="js-city-check" name="city_ids[]" value="{{ $city->id }}" data-city-id="{{ $city->id }}"
                  @checked(in_array($city->id, $marketer->assigned_city_ids ?? []))>
                {{ $city->name }}
              </label>
            @endforeach
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold text-gray-500 mb-1">İlçeler (seçili yoksa seçilen illerin tümü)</div>
          <div class="js-district-groups flex flex-wrap gap-2 max-h-28 overflow-y-auto border rounded-lg p-2 bg-white">
            @foreach($cities as $city)
              <div class="js-district-group" data-city-id="{{ $city->id }}" style="display:none">
                @foreach($districtMap[$city->id] ?? [] as $district)
                  <label class="inline-flex items-center gap-1 text-xs bg-gray-100 rounded px-2 py-1 mb-1">
                    <input type="checkbox" name="districts[]" value="{{ $district }}" @checked(in_array($district, $marketer->assigned_districts ?? []))>
                    {{ $district }}
                  </label>
                @endforeach
              </div>
            @endforeach
            <div class="js-district-empty-hint text-xs text-gray-400">Önce yukarıdan il seçin.</div>
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold text-gray-500 mb-1">Kategoriler (seçili yoksa tüm kategoriler)</div>
          <div class="flex flex-wrap gap-2 max-h-28 overflow-y-auto border rounded-lg p-2 bg-white">
            @foreach($categories as $category)
              <label class="inline-flex items-center gap-1 text-xs bg-gray-100 rounded px-2 py-1">
                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $marketer->assigned_category_ids ?? []))>
                {{ $category->name }}
              </label>
            @endforeach
          </div>
        </div>
        {{-- 18 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'inde kuruma özel
             İyilik Hareketi teklif sayfasını görebilsin mi (varsayılan kapalı). --}}
        <label class="flex items-start gap-2 text-sm bg-white border rounded-lg p-2">
          <input type="checkbox" name="can_view_offer_page" value="1" class="mt-0.5" @checked($marketer->can_view_offer_page)>
          <span><strong>"💛 Teklif Sayfasını Gör" düğmesi</strong> — işaretliyse pazarlamacı, kapsamındaki kurumların kuruma özel üyelik teklif sayfasını (fiyatlar ve kurumun son 30 günlük rakamları) açabilir.</span>
        </label>
        <button class="btn-primary px-4 py-2 rounded-lg text-xs font-semibold">Kapsamı Kaydet</button>
      </form>
    </details>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz pazarlamacı eklenmedi.</div>
  @endforelse
</div>

<script>
document.querySelectorAll('.js-scope-form').forEach(function (form) {
  function syncDistrictGroups() {
    var anyChecked = false;
    form.querySelectorAll('.js-district-group').forEach(function (group) {
      var cityId = group.getAttribute('data-city-id');
      var cityCheck = form.querySelector('.js-city-check[data-city-id="' + cityId + '"]');
      var visible = cityCheck && cityCheck.checked;
      group.style.display = visible ? '' : 'none';
      if (visible) anyChecked = true;
      if (!visible) {
        group.querySelectorAll('input[type=checkbox]').forEach(function (cb) { cb.checked = false; });
      }
    });
    var hint = form.querySelector('.js-district-empty-hint');
    if (hint) hint.style.display = anyChecked ? 'none' : '';
  }
  form.querySelectorAll('.js-city-check').forEach(function (cb) {
    cb.addEventListener('change', syncDistrictGroups);
  });
  syncDistrictGroups();
});
</script>
@endsection
