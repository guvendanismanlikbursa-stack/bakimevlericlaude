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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Marka</th><th class="p-3">Sayfa</th><th class="p-3">Konum</th><th class="p-3">Tarih</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($clicks as $click)
        <tr>
          <td class="p-3 font-medium">{{ $brands[$click->brand]['name'] ?? $click->brand }}</td>
          <td class="p-3 text-gray-500 truncate max-w-xs" title="{{ $click->page_url }}">{{ $click->page_url ?: '-' }}</td>
          <td class="p-3">
            @if($click->city_name)
              {{ $click->city_name }}
              @if($click->lat && $click->lng)
                <a href="https://www.google.com/maps?q={{ $click->lat }},{{ $click->lng }}" target="_blank" class="text-blue-600 text-xs ml-1">(harita)</a>
              @endif
            @else
              <span class="text-gray-300">İzin verilmedi</span>
            @endif
          </td>
          <td class="p-3 text-gray-400">{{ $click->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.whatsapp-clicks.destroy', $click) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600 text-xs">Sil</button></form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="5">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $clicks->links() }}</div>
@endsection
