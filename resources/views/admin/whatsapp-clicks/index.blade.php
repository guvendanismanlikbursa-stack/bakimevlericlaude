@extends('admin.layout')
@section('title', 'WhatsApp Tıklamaları')

@section('content')
<h1 class="text-2xl font-bold mb-2">WhatsApp Tıklamaları</h1>
<p class="text-sm text-gray-500 mb-6">Sitelerdeki yüzen WhatsApp butonuna kimler ne zaman tıkladı. WhatsApp'ın kendisine otomatik bildirim gitmiyor (bu, ücretli WhatsApp Business API gerektirir) — ziyaretçi kendi WhatsApp uygulamasında sohbeti açıyor, bu ekran sadece tıklama kaydıdır.</p>

<form method="GET" class="mb-4">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($clicks as $click)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $brands[$click->brand]['name'] ?? $click->brand }}</div>
        <div class="text-xs text-gray-500 mt-0.5 break-words" title="{{ $click->page_url }}">{{ $click->page_url ?: '-' }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
          @if($click->city_name)
            📍 {{ $click->city_name }}
            @if($click->lat && $click->lng)
              <a href="https://www.google.com/maps?q={{ $click->lat }},{{ $click->lng }}" target="_blank" class="text-blue-600">(harita)</a>
            @endif
          @else
            <span class="text-gray-300">Konum izni verilmedi</span>
          @endif
          · {{ $click->created_at->format('d.m.Y H:i') }}
        </div>
      </div>
      <form method="POST" action="{{ route('admin.whatsapp-clicks.destroy', $click) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-xs font-semibold whitespace-nowrap">Sil</button></form>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $clicks->links() }}</div>
@endsection
