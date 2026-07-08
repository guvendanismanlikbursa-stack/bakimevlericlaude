@extends('admin.layout')
@section('title', 'Teklif Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-6">Teklif Talepleri</h1>
<p class="text-sm text-gray-500 mb-4">Aile/kurum arasındaki mesajlaşma burada gösterilmez (mahremiyet) — sadece gönderilen form ve kurumun verdiği fiyat cevabı görünür.</p>

<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','closed'=>'Kapandı'] as $k=>$v)
      <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
    @endforeach
  </select>
  <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
    <input type="checkbox" name="bulk_only" value="1" onchange="this.form.submit()" @checked(request()->boolean('bulk_only'))>
    Sadece toplu talepler
  </label>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad Soyad</th><th class="p-3">Telefon</th><th class="p-3">Kurum</th><th class="p-3">Form Detayı</th><th class="p-3">Verilen Fiyat</th><th class="p-3">Marka</th><th class="p-3">Durum</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($requests as $r)
        <tr>
          <td class="p-3 font-medium">{{ $r->full_name }}</td>
          <td class="p-3">{{ $r->phone }}</td>
          <td class="p-3">
            {{ $r->facility?->name ?? '- (yayın talebi)' }}
            @if($r->batch_id)
              <span class="block text-[11px] font-semibold text-purple-600 mt-1">Toplu talep · {{ substr($r->batch_id, 0, 8) }}</span>
            @endif
          </td>
          <td class="p-3 text-xs text-gray-600 max-w-[220px]">
            @if($r->care_for)<div><strong>Kimin için:</strong> {{ $r->care_for }}</div>@endif
            @if($r->patient_name)<div><strong>Hasta/çocuk:</strong> {{ $r->patient_name }}</div>@endif
            @if($r->message)<div class="mt-1 line-clamp-2" title="{{ $r->message }}">{{ $r->message }}</div>@endif
          </td>
          <td class="p-3">
            @forelse($r->quotes as $quote)
              <div class="{{ ! $loop->first ? 'mt-1 pt-1 border-t' : '' }}">
                <span class="font-black text-gray-900">{{ number_format($quote->price, 0, ',', '.') }} TL</span>
                <span class="text-xs text-gray-400">/{{ $quote->price_period === 'monthly' ? 'ay' : 'tek sefer' }}</span>
                <span class="block text-[11px] text-gray-500">{{ $quote->facility?->name }} — {{ $quote->status }}</span>
              </div>
            @empty
              <span class="text-gray-300 text-xs">Henüz teklif yok</span>
            @endforelse
          </td>
          <td class="p-3">{{ $brands[$r->brand]['name'] ?? $r->brand }}</td>
          <td class="p-3">
            <form method="POST" action="{{ route('admin.offer-requests.update', $r) }}">
              @csrf @method('PUT')
              <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','closed'=>'Kapandı'] as $k=>$v)
                  <option value="{{ $k }}" @selected($r->status===$k)>{{ $v }}</option>
                @endforeach
              </select>
            </form>
          </td>
          <td class="p-3 text-gray-400">
            {{ $r->created_at->format('d.m.Y H:i') }}
            <a href="{{ route('admin.offer-requests.messages', $r) }}" class="block mt-1 text-[11px] font-semibold text-red-600 hover:underline whitespace-nowrap">Şikayet / Mesajları İncele</a>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="8">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
