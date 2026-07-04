@extends('admin.layout')
@section('title', 'Veri Çekici Satır İncele')

@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold">Veri Çekici Satır İncele</h1>
    <p class="text-sm text-gray-500 mt-1">Çekilen satırın ayrıntılarını gözden geçirin, düzenleyin veya ön kayıt olarak kuruma çevirin.</p>
  </div>
  <a href="{{ route('admin.data-extractor.index') }}" class="text-sm font-semibold text-gray-700 underline">Geri dön</a>
</div>

<div class="grid lg:grid-cols-[1fr_320px] gap-6">
  <div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="font-bold text-lg">Satır #{{ $row->id }} - {{ $row->name ?: ($prefill['name'] ?? '-') }}</h2>
          <p class="text-xs text-gray-500 mt-1">Durum: <strong>{{ $row->status }}</strong> · Kaynak şehir: {{ $row->batch->city->name }} · Kategori: {{ $row->batch->category->name }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">Ad</label>
          <p class="mt-1 text-gray-800">{{ $prefill['name'] ?? '-' }}</p>
        </div>
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">Telefon</label>
          <p class="mt-1 text-gray-800">{{ $prefill['phone'] ?? '-' }}</p>
        </div>
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">E-posta</label>
          <p class="mt-1 text-gray-800">{{ $prefill['email'] ?? '-' }}</p>
        </div>
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">İlçe</label>
          <p class="mt-1 text-gray-800">{{ $prefill['district'] ?? '-' }}</p>
        </div>
        <div class="md:col-span-2">
          <label class="text-xs uppercase tracking-wider text-gray-500">Adres</label>
          <p class="mt-1 text-gray-800">{{ $prefill['address'] ?? '-' }}</p>
        </div>
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">Fiyat aralığı</label>
          <p class="mt-1 text-gray-800">{{ $prefill['price_min'] ?? '-' }} - {{ $prefill['price_max'] ?? '-' }}</p>
        </div>
        <div>
          <label class="text-xs uppercase tracking-wider text-gray-500">Puan</label>
          <p class="mt-1 text-gray-800">{{ $prefill['rating'] ?? '-' }}</p>
        </div>
      </div>

      <div class="mt-4">
        <label class="text-xs uppercase tracking-wider text-gray-500">Açıklama</label>
        <p class="mt-1 whitespace-pre-line text-gray-800">{{ $prefill['description'] ?? '-' }}</p>
      </div>

      @if($row->message)
        <div class="mt-4 rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-700">
          <strong>Mesaj:</strong> {{ $row->message }}
        </div>
      @endif

      <div class="mt-6 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.data-extractor.rows.autofill', $row) }}">
          @csrf
          <button type="submit" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold">Otomatik Doldur</button>
        </form>

        <form method="POST" action="{{ route('admin.data-extractor.rows.approve', $row) }}">
          @csrf
          <input type="hidden" name="is_published" value="1">
          <button type="submit" class="rounded-lg bg-green-600 text-white px-4 py-2 text-sm font-semibold">Onayla</button>
        </form>

        <form method="POST" action="{{ route('admin.data-extractor.rows.approve', $row) }}">
          @csrf
          <input type="hidden" name="is_published" value="1">
          <input type="hidden" name="edit" value="1">
          <button type="submit" class="rounded-lg bg-gray-900 text-white px-4 py-2 text-sm font-semibold">Onayla ve Düzenle</button>
        </form>

        <a href="{{ route('admin.facilities.create', array_filter($prefill)) }}" class="rounded-lg border border-gray-300 bg-white text-gray-700 px-4 py-2 text-sm font-semibold">Ön Kayıt Oluştur</a>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
      <h2 class="font-bold mb-3">Ham Payload</h2>
      <pre class="rounded-lg bg-gray-50 p-4 overflow-auto text-xs text-gray-700">{{ json_encode($row->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
  </div>

  <aside class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
      <h2 class="font-bold mb-3">İşlem Adımları</h2>
      <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
        <li>Veri satırı kontrol edin ve gerekli ise önce düzenleyin.</li>
        <li>Otomatik doldurma ile ek alanları tamamlayın.</li>
        <li>Onayla veya Onayla ve Düzenle ile kurum oluşturun.</li>
        <li>Ön kayıt oluşturmak için doğrudan yeni kurum formunu kullanabilirsiniz.</li>
      </ol>
    </div>
  </aside>
</div>
@endsection
