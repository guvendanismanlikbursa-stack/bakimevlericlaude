@extends('admin.layout')
@section('title', 'Kategoriler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kategoriler</h1>
<p class="text-sm text-gray-500 mb-4">Kategoriler üç ana hizmet bölümüne bağlanır. Her site bu bölümlerin tamamını gösterebilir; admin buradan kategori ekler veya siler.</p>

<form method="POST" action="{{ route('admin.categories.store') }}" class="bg-white rounded-xl shadow-sm p-4 grid md:grid-cols-[1fr_260px_auto] gap-2 mb-6">
  @csrf
  <input type="text" name="name" placeholder="Kategori adı" required class="border rounded-lg px-3 py-2">
  <select name="brand_scope" required class="border rounded-lg px-3 py-2">
    @foreach(service_sections() as $section)
      @foreach($section['scopes'] as $scope)
        <option value="{{ $scope }}">{{ $section['title'] }} · {{ $scope }}</option>
      @endforeach
    @endforeach
  </select>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Ekle</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Ana Bölüm</th><th class="p-3">Kapsam</th><th class="p-3">Kurum Sayısı</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($categories as $category)
        @php $section = service_section_for_scope($category->brand_scope); @endphp
        <tr>
          <td class="p-3">{{ $category->name }}</td>
          <td class="p-3">{{ $section['title'] ?? '-' }}</td>
          <td class="p-3">{{ $category->brand_scope }}</td>
          <td class="p-3">{{ $category->facilities_count }}</td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<h2 class="text-lg font-bold mb-1">Fiyat Segmenti Aralıkları</h2>
<p class="text-sm text-gray-500 mb-4">Her kurum türü için Ekonomik/Standart/Premium/Ultra Premium'un TAM olarak hangi ₺ aralığı olduğunu belirleyin. Kurumun aylık başlangıç fiyatı (price_min) bu aralıklara göre kurum kartlarında, inceleme sayfasında ve kurum panelindeki segment bilgi kutucuklarına anında yansır.</p>

<div class="space-y-4">
  @foreach($categories as $category)
    <div class="bg-white rounded-xl shadow-sm p-5">
      <div class="font-bold text-gray-900 mb-3">{{ $category->name }}</div>
      <form method="POST" action="{{ route('admin.categories.price-tiers.update', $category) }}" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        @csrf @method('PUT')

        <div class="rounded-lg bg-green-50 border border-green-100 p-3">
          <div class="text-xs font-bold text-green-800 mb-1">🟢 Ekonomik</div>
          <div class="text-xs text-gray-600">0 ₺ — <input type="number" step="1" min="1" name="price_tier_standart_min" value="{{ old('price_tier_standart_min', $category->price_tier_standart_min) }}" required class="border rounded px-2 py-1 w-24 inline-block"> ₺ arası</div>
        </div>

        <div class="rounded-lg bg-blue-50 border border-blue-100 p-3">
          <div class="text-xs font-bold text-blue-800 mb-1">🔵 Standart</div>
          <div class="text-xs text-gray-600">{{ number_format($category->price_tier_standart_min, 0, ',', '.') }} ₺ — <input type="number" step="1" min="1" name="price_tier_premium_min" value="{{ old('price_tier_premium_min', $category->price_tier_premium_min) }}" required class="border rounded px-2 py-1 w-24 inline-block"> ₺ arası</div>
        </div>

        <div class="rounded-lg bg-purple-50 border border-purple-100 p-3">
          <div class="text-xs font-bold text-purple-800 mb-1">🟣 Premium</div>
          <div class="text-xs text-gray-600">{{ number_format($category->price_tier_premium_min, 0, ',', '.') }} ₺ — <input type="number" step="1" min="1" name="price_tier_ultra_min" value="{{ old('price_tier_ultra_min', $category->price_tier_ultra_min) }}" required class="border rounded px-2 py-1 w-24 inline-block"> ₺ arası</div>
        </div>

        <div class="flex items-center justify-between gap-2 rounded-lg bg-amber-50 border border-amber-100 p-3">
          <div>
            <div class="text-xs font-bold text-amber-800 mb-1">🟡 Ultra Premium</div>
            <div class="text-xs text-gray-600">{{ number_format($category->price_tier_ultra_min, 0, ',', '.') }} ₺ ve üzeri</div>
          </div>
          <button class="bg-gray-900 text-white px-3 py-2 rounded-lg text-xs font-semibold shrink-0">Kaydet</button>
        </div>
      </form>
    </div>
  @endforeach
</div>
@endsection