@extends('admin.layout')
@section('title', 'Yakın Arama Kayıtları')

@section('content')
<h1 class="text-2xl font-bold mb-2">Yakın Arama Kayıtları</h1>
<p class="text-sm text-gray-500 mb-6">"Yakınımda Ara" özelliğini kullanan HERKESİN (kayıtlı olsun olmasın) konumu burada listelenir.</p>

<form method="GET" class="mb-4 flex gap-2">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)
      <option value="{{ $slug }}" @selected(request('brand') === $slug)>{{ $b['name'] }}</option>
    @endforeach
  </select>
</form>

@if($cityCounts->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
  <div class="font-black text-gray-950 mb-3">En Çok Aranan Şehirler</div>
  <p class="text-sm text-gray-600">
    @foreach($cityCounts as $row)
      <span class="font-semibold text-gray-800">{{ $row->city_name }}</span>: {{ number_format($row->total) }}@if(! $loop->last) &middot; @endif
    @endforeach
  </p>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Marka</th><th class="p-3">Şehir (yaklaşık)</th><th class="p-3">Kayıtlı Aile</th><th class="p-3">IP</th><th class="p-3">Tarih</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($searches as $search)
        <tr>
          <td class="p-3 text-gray-500">{{ $brands[$search->brand]['name'] ?? $search->brand }}</td>
          <td class="p-3">{{ $search->city_name ?? '—' }}</td>
          <td class="p-3">
            @if($search->familyUser)
              <a href="{{ route('admin.family-users.show', $search->familyUser) }}" class="text-primary font-semibold">{{ $search->familyUser->name }}</a>
            @else
              <span class="text-gray-400">Misafir</span>
            @endif
          </td>
          <td class="p-3 font-mono text-gray-500">{{ $search->ip ?? '—' }}</td>
          <td class="p-3 text-gray-400">{{ $search->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right">
            <a href="https://www.google.com/maps?q={{ $search->lat }},{{ $search->lng }}" target="_blank" class="text-primary text-xs font-semibold">Haritada gör →</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="p-8 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $searches->links() }}</div>
@endsection
