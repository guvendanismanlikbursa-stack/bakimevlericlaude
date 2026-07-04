<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
  <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="font-semibold">{{ $req->familyUser->name ?? $req->full_name }}</div>
        <span class="text-xs rounded-full px-2 py-1 bg-gray-100 text-gray-700">{{ $req->isBroadcast() ? 'Genel talep' : 'Doğrudan talep' }}</span>
      </div>
      <div class="text-xs text-gray-400 mt-1">{{ $req->patient_name ? $req->patient_name.' · ' : '' }}{{ $req->created_at->format('d.m.Y H:i') }}</div>
      <div class="grid sm:grid-cols-3 gap-3 text-sm text-gray-600 mt-3">
        <div><span class="block text-xs text-gray-400">Kategori</span>{{ $req->category->name ?? '-' }}</div>
        <div><span class="block text-xs text-gray-400">Şehir</span>{{ $req->city->name ?? '-' }}</div>
        <div><span class="block text-xs text-gray-400">Telefon</span>{{ $req->phone ?: '-' }}</div>
      </div>
      @if($req->message)<p class="text-sm text-gray-600 mt-3 bg-gray-50 rounded-lg p-3">{{ $req->message }}</p>@endif
    </div>
    <a href="{{ brand_route('facility.thread', $req) }}" class="text-xs text-primary font-semibold">Mesajlar →</a>
  </div>

  @php $already = $req->quotes->where('facility_id', $facility->id)->first(); @endphp
  @if($already)
    <div class="text-xs text-green-700 mt-3 rounded-lg bg-green-50 border border-green-100 px-3 py-2">
      Teklif gönderildi: {{ number_format($already->price,0,',','.') }}₺ · {{ $already->status === 'accepted' ? 'Kabul edildi' : ($already->status === 'declined' ? 'Reddedildi' : 'Beklemede') }}
    </div>
  @else
    <form method="POST" action="{{ brand_route('facility.quotes.store', $req) }}" class="grid md:grid-cols-[140px_150px_1fr_auto] gap-2 mt-3">
      @csrf
      <input type="number" step="0.01" name="price" placeholder="Fiyat (₺)" required class="border rounded-lg px-3 py-2 text-sm w-full">
      <select name="price_period" class="border rounded-lg px-3 py-2 text-sm w-full">
        <option value="monthly">Aylık</option>
        <option value="one_time">Tek Seferlik</option>
      </select>
      <input type="text" name="message" placeholder="Kısa not (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm w-full">
      <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Teklif Gönder</button>
    </form>
  @endif
</div>