@extends('admin.layout')
@section('title', 'Veri Çekici')

@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold">Veri Çekici<span class="sr-only">Veri Cekici</span></h1>
    <p class="text-sm text-gray-500 mt-1">Google Maps verilerini önce inceleme listesine alır; admin onaylayınca ön kayıtlı kurum oluşturur.</p>
  </div>
</div>

<div class="grid lg:grid-cols-[1fr_360px] gap-6">
  <div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="flex items-start justify-between gap-4 mb-4">
        <div>
          <h2 class="font-bold">Otomatik Veri Çek</h2>
          <p class="text-xs text-gray-500 mt-1">Tek seferde en fazla 1000 kurum çekilir. Web adresi kayda alınmaz.</p>
        </div>
        <span class="rounded-full bg-gray-900 text-white px-3 py-1 text-xs font-bold">Max 1000</span>
      </div>
      <form method="POST" action="{{ route('admin.data-extractor.run') }}" class="grid md:grid-cols-2 gap-4">
        @csrf
        <div class="md:col-span-2">
          <label class="text-sm font-medium">Arama cümlesi</label>
          <input type="text" name="query" required maxlength="255" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Örn: huzurevi nilufer bursa">
        </div>
        <div>
          <label class="text-sm font-medium">Çekilecek kurum sayısı</label>
          <input type="number" name="limit" min="1" max="1000" value="50" required class="border rounded-lg px-3 py-2 w-full mt-1">
        </div>
        <div>
          <label class="text-sm font-medium">Şehir</label>
          <select name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            @foreach($cities as $city)
              <option value="{{ $city->id }}">{{ $city->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-medium">İlçe</label>
          <input type="text" name="district" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Örn: Nilüfer">
        </div>
        <div>
          <label class="text-sm font-medium">Kurum Kategorisi</label>
          <select name="facility_category_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            @foreach($categories as $category)
              @php($section = service_section_for_scope($category->brand_scope))
              <option value="{{ $category->id }}">{{ $section['title'] ?? 'Bölüm yok' }} - {{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="md:col-span-2">
          <button class="bg-gray-900 text-white px-5 py-2 rounded-lg text-sm font-semibold">Otomatik Çalıştır ve Listele</button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-bold mb-4">Excel Import</h2>
      <form method="POST" action="{{ route('admin.data-extractor.import') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
        @csrf
        <div class="md:col-span-2">
          <label class="text-sm font-medium">Veri Çekici Excel dosyası (.xlsx)</label>
          <input type="file" name="file" accept=".xlsx" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
          <p class="text-xs text-gray-400 mt-1">Excel import eski hızlı akıştır; satırları doğrudan ön kayıtlı kuruma çevirir.</p>
        </div>
        <div>
          <label class="text-sm font-medium">Şehir</label>
          <select name="city_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            @foreach($cities as $city)
              <option value="{{ $city->id }}">{{ $city->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-medium">İlçe</label>
          <input type="text" name="district" class="border rounded-lg px-3 py-2 w-full mt-1" placeholder="Örn: Nilüfer">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium">Kurum Kategorisi</label>
          <select name="facility_category_id" required class="border rounded-lg px-3 py-2 w-full mt-1 bg-white">
            @foreach($categories as $category)
              @php($section = service_section_for_scope($category->brand_scope))
              <option value="{{ $category->id }}">{{ $section['title'] ?? 'Bölüm yok' }} - {{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <label class="md:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" name="is_published" value="1" checked>
          <span>Aktarılan kurumları yayında oluştur</span>
        </label>
        <div class="md:col-span-2">
          <button class="bg-white border border-gray-300 text-gray-900 px-5 py-2 rounded-lg text-sm font-semibold">Excel'i Iceri Aktar</button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="font-bold">Çekilen Liste / Admin Onayı</h2>
          <p class="text-xs text-gray-500 mt-1">Düzenle, otomatik doldur, onayla veya sil. Onaylanan satır kurum kaydına dönüşür.</p>
        </div>
        <span class="text-xs text-gray-500">Son 100 satir</span>
      </div>

      <div class="space-y-4">
        @forelse($reviewRows as $row)
          @php($payload = $row->payload ?? [])
          <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 mb-3">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-black text-gray-950">#{{ $row->id }} {{ $row->name ?: ($payload['name'] ?? '-') }}</span>
                  <span class="rounded-full bg-white border px-2 py-0.5 text-xs font-bold text-gray-600">{{ $row->status }}</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ $row->batch->city->name }}  {{ $payload['district'] ?? '-' }}  {{ $row->batch->category->name }}</div>
                @if($row->message)<div class="text-xs text-gray-500 mt-1">{{ $row->message }}</div>@endif
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.data-extractor.rows.show', $row) }}" class="rounded-lg border border-gray-300 bg-white text-gray-700 px-3 py-2 text-xs font-bold">İncele</a>
                <form method="POST" action="{{ route('admin.data-extractor.rows.autofill', $row) }}">@csrf<button class="rounded-lg bg-blue-600 text-white px-3 py-2 text-xs font-bold">Otomatik Doldur</button></form>
                <form method="POST" action="{{ route('admin.data-extractor.rows.approve', $row) }}">@csrf<input type="hidden" name="is_published" value="1"><button class="rounded-lg bg-green-600 text-white px-3 py-2 text-xs font-bold">Onayla</button></form>
                <form method="POST" action="{{ route('admin.data-extractor.rows.approve', $row) }}">@csrf<input type="hidden" name="is_published" value="1"><input type="hidden" name="edit" value="1"><button class="rounded-lg bg-gray-900 text-white px-3 py-2 text-xs font-bold">Onaylı Düzenle</button></form>
                <form method="POST" action="{{ route('admin.data-extractor.rows.destroy', $row) }}" onsubmit="return confirm('Bu çekilen satır listeden silinsin mi?');">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 bg-white text-red-600 px-3 py-2 text-xs font-bold">Sil</button></form>
              </div>
            </div>

            <form method="POST" action="{{ route('admin.data-extractor.rows.update', $row) }}" class="grid md:grid-cols-2 lg:grid-cols-4 gap-3">
              @csrf @method('PUT')
              <input name="name" value="{{ $payload['name'] ?? $row->name }}" required class="border rounded-lg px-3 py-2 text-sm" placeholder="Kurum adı">
              <input name="category" value="{{ $payload['category'] ?? '' }}" class="border rounded-lg px-3 py-2 text-sm" placeholder="Çekilen kategori">
              <input name="district" value="{{ $payload['district'] ?? '' }}" class="border rounded-lg px-3 py-2 text-sm" placeholder="İlçe">
              <input name="phone" value="{{ $payload['phone'] ?? $row->phone }}" class="border rounded-lg px-3 py-2 text-sm" placeholder="Telefon">
              <input name="email" value="{{ $payload['email'] ?? '' }}" class="border rounded-lg px-3 py-2 text-sm" placeholder="E-posta">
              <input name="rating" value="{{ $payload['rating'] ?? '' }}" class="border rounded-lg px-3 py-2 text-sm" placeholder="Puan">
              <input name="address" value="{{ $payload['address'] ?? '' }}" class="border rounded-lg px-3 py-2 text-sm lg:col-span-2" placeholder="Adres">
              <textarea name="description" rows="2" class="border rounded-lg px-3 py-2 text-sm md:col-span-2 lg:col-span-3" placeholder="Kısa açıklama">{{ $payload['description'] ?? '' }}</textarea>
              <button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold">Satırı Kaydet</button>
            </form>
          </div>
        @empty
          <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-gray-500">Henuz onay bekleyen cekilmis veri yok.</div>
        @endforelse
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-bold mb-3">Son Importlar</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-left text-gray-500 bg-gray-50"><tr><th class="p-2">Kaynak</th><th class="p-2">Durum</th><th class="p-2">Satir</th><th class="p-2">Eklenen</th><th class="p-2">Tarih</th></tr></thead>
          <tbody class="divide-y">
            @forelse($recentImports as $batch)
              <tr><td class="p-2">{{ $batch->file_name ?: '-' }}</td><td class="p-2">{{ $batch->status }}</td><td class="p-2">{{ $batch->total_rows }}</td><td class="p-2">{{ $batch->created_count }}</td><td class="p-2">{{ $batch->created_at->format('d.m.Y H:i') }}</td></tr>
            @empty
              <tr><td class="p-2 text-gray-400" colspan="5">Henüz import yok.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-bold mb-3">Yerel Arac Durumu</h2>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between"><span>Klasör</span><strong class="{{ $tool['exists'] ? 'text-green-700' : 'text-red-600' }}">{{ $tool['exists'] ? 'Hazır' : 'Yok' }}</strong></div>
        <div class="flex justify-between"><span>Scraper</span><strong class="{{ $tool['scraper'] ? 'text-green-700' : 'text-red-600' }}">{{ $tool['scraper'] ? 'Hazır' : 'Yok' }}</strong></div>
        <div class="flex justify-between"><span>Canli API</span><strong class="text-green-700">Devre disi</strong></div>
      </div>
      <div class="mt-4 rounded-lg bg-gray-50 p-3 text-xs text-gray-500 break-all">{{ $tool['path'] }}</div>
    </div>

    <div class="bg-amber-50 rounded-xl border border-amber-100 p-5 text-sm text-amber-900">
      <strong>Akış</strong>
      <p class="mt-2">Otomatik çekim liste oluşturur. Onayla butonu kurum kaydı açar. Düzenle butonu kurum kaydını açıp admin kurum paneline geçirir.</p>
    </div>
  </aside>
</div>
@endsection
