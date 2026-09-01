@extends('admin.layout')
@section('title', 'Paketler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Paket / Abonelik Kataloğu</h1>
<p class="text-sm text-gray-500 mb-6">Kurumlar bu paketleri "Bakiyem &gt; Paketler" ekranından talep eder; dekont onayı sonrası tutar + bonus hak kurum hesabına işlenir.</p>

{{-- 1 Eylul 2026: kullanicinin talebi - backend update() zaten calisiyordu
     ama ekranda "Duzenle" hic yoktu, ContentPageController'daki ?edit=
     deseni buraya da uygulandi. --}}
@if($editingPackage)
  <p class="text-sm font-semibold text-amber-700 mb-2">"{{ $editingPackage->name }}" düzenleniyor. <a href="{{ route('admin.packages.index') }}" class="underline">Vazgeç, yeni paket ekle</a></p>
@endif
<form method="POST" action="{{ $editingPackage ? route('admin.packages.update', $editingPackage) : route('admin.packages.store') }}" class="bg-white rounded-xl shadow-sm p-5 grid md:grid-cols-2 gap-3 mb-8">
  @csrf
  @if($editingPackage) @method('PUT') @endif
  <input type="text" name="name" value="{{ old('name', $editingPackage?->name) }}" placeholder="Paket adı" required class="border rounded-lg px-3 py-2">
  <input type="number" step="0.01" name="price" value="{{ old('price', $editingPackage?->price) }}" placeholder="Fiyat (₺)" required class="border rounded-lg px-3 py-2">
  <input type="number" name="bonus_quote_credits" value="{{ old('bonus_quote_credits', $editingPackage?->bonus_quote_credits) }}" placeholder="Bonus teklif hakkı" class="border rounded-lg px-3 py-2">
  <input type="number" name="duration_days" value="{{ old('duration_days', $editingPackage?->duration_days) }}" placeholder="Süre (gün, opsiyonel)" class="border rounded-lg px-3 py-2">
  <input type="number" name="sort_order" value="{{ old('sort_order', $editingPackage?->sort_order) }}" placeholder="Sıra" class="border rounded-lg px-3 py-2">
  <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingPackage?->is_active ?? true))> Yayında</label>
  <textarea name="description" placeholder="Açıklama" rows="2" class="border rounded-lg px-3 py-2 md:col-span-2">{{ old('description', $editingPackage?->description) }}</textarea>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-2">{{ $editingPackage ? 'Güncelle' : 'Ekle' }}</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Fiyat</th><th class="p-3">Bonus Hak</th><th class="p-3">Süre</th><th class="p-3">Durum</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($packages as $package)
        <tr>
          <td class="p-3">{{ $package->name }}</td>
          <td class="p-3">{{ number_format($package->price,2,',','.') }}₺</td>
          <td class="p-3">{{ $package->bonus_quote_credits }}</td>
          <td class="p-3 text-gray-400">{{ $package->duration_days ? $package->duration_days.' gün' : '—' }}</td>
          <td class="p-3">{{ $package->is_active ? 'Yayında' : 'Pasif' }}</td>
          <td class="p-3 text-right whitespace-nowrap">
            <a href="{{ route('admin.packages.index', ['edit' => $package->id]) }}" class="text-primary font-semibold mr-3">Düzenle</a>
            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Silinsin mi?');" class="inline">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
