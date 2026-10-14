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

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($requests as $r)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900">{{ $r->full_name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">
            📞 {{ $r->phone }} · {{ $r->facility?->name ?? '- (yayın talebi)' }} · {{ $brands[$r->brand]['name'] ?? $r->brand }} · {{ $r->created_at->format('d.m.Y H:i') }}
            @if($r->batch_id)<span class="font-semibold text-purple-600">· Toplu talep ({{ substr($r->batch_id, 0, 8) }})</span>@endif
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          <form method="POST" action="{{ route('admin.offer-requests.update', $r) }}">
            @csrf @method('PUT')
            <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
              @foreach(['new'=>'Yeni','contacted'=>'İletişime Geçildi','closed'=>'Kapandı'] as $k=>$v)
                <option value="{{ $k }}" @selected($r->status===$k)>{{ $v }}</option>
              @endforeach
            </select>
          </form>
          <a href="{{ route('admin.offer-requests.messages', $r) }}" class="text-xs font-semibold text-red-600 hover:underline whitespace-nowrap">Şikayet / Mesajları İncele</a>
        </div>
      </div>
      <div class="text-xs text-gray-600 flex flex-wrap gap-x-3 gap-y-1">
        @if($r->care_for)<span><strong>Kimin için:</strong> {{ $r->care_for }}</span>@endif
        @if($r->patient_name)<span><strong>Hasta/çocuk:</strong> {{ $r->patient_name }}</span>@endif
        @if($r->patient_age)<span><strong>Yaş:</strong> {{ $r->patient_age }}</span>@endif
        @if($r->needed_from)<span><strong>İhtiyaç tarihi:</strong> {{ $r->needed_from->format('d.m.Y') }}</span>@endif
        @if($r->budget_min || $r->budget_max)
          <span><strong>Bütçe:</strong>
            {{ $r->budget_min ? number_format($r->budget_min, 0, ',', '.').' TL' : '?' }}
            –
            {{ $r->budget_max ? number_format($r->budget_max, 0, ',', '.').' TL' : '?' }}
          </span>
        @endif
      </div>
      @if($r->message)<div class="text-xs text-gray-600" title="{{ $r->message }}">{{ $r->message }}</div>@endif
      <div class="text-xs">
        @forelse($r->quotes as $quote)
          <div class="{{ ! $loop->first ? 'mt-1 pt-1 border-t' : '' }}">
            <span class="font-black text-gray-900">{{ number_format($quote->price, 0, ',', '.') }} TL</span>
            <span class="text-gray-400">/{{ $quote->price_period === 'monthly' ? 'ay' : 'tek sefer' }}</span>
            <span class="text-gray-500">— {{ $quote->facility?->name }} — {{ ['pending' => 'Beklemede', 'accepted' => 'Kabul edildi', 'declined' => 'Reddedildi'][$quote->status] ?? $quote->status }}</span>
          </div>
        @empty
          <span class="text-gray-300">Henüz teklif yok</span>
        @endforelse
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
