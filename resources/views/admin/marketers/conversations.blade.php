@extends('admin.layout')
@section('title', 'Pazarlamacı Görüşmeleri')

@php
  $statusLabels = ['called'=>'Arandı','thinking'=>'Düşünecek','interested'=>'İlgileniyor','subscribed'=>'Üye Oldu','not_interested'=>'İlgilenmiyor','receipt_sent'=>'Dekont Geldi'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-bold">Pazarlamacı Görüşmeleri</h1>
  <a href="{{ route('admin.marketers.index') }}" class="text-sm font-semibold text-gray-700 underline">Pazarlamacılara dön</a>
</div>
<p class="text-sm text-gray-500 mb-6">Tüm pazarlamacıların hangi kurumla ne zaman görüştüğü, sonucu ve notu — en yeniden eskiye.</p>

<form method="GET" class="mb-4">
  <select name="marketer" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm pazarlamacılar</option>
    @foreach($marketers as $m)
      <option value="{{ $m->id }}" @selected(request('marketer') == $m->id)>{{ $m->name }}</option>
    @endforeach
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Tarih</th><th class="p-3">Pazarlamacı</th><th class="p-3">Kurum</th><th class="p-3">Durum</th><th class="p-3">Not</th><th class="p-3">Broşür</th><th class="p-3">Randevu</th></tr></thead>
    <tbody class="divide-y">
      @forelse($notes as $note)
        <tr>
          <td class="p-3 text-gray-400 whitespace-nowrap">{{ $note->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3">{{ $note->marketer?->name ?? '—' }}</td>
          <td class="p-3 font-medium">
            @if($note->facility)
              <a href="{{ route('admin.facilities.edit', $note->facility) }}" class="text-blue-600">{{ $note->facility->name }}</a>
              <div class="text-xs text-gray-400">{{ $note->facility->district }} / {{ $note->facility->city?->name }}</div>
            @else
              <span class="text-gray-300">(silinmiş kurum)</span>
            @endif
          </td>
          <td class="p-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $statusLabels[$note->status] ?? $note->status }}</span></td>
          <td class="p-3 text-gray-600 max-w-xs break-words">{{ $note->note ?: '—' }}</td>
          <td class="p-3">
            @if($note->brochure_requested === null) <span class="text-gray-300">—</span>
            @elseif($note->brochure_requested) <span class="text-green-700 text-xs font-semibold">İstiyor</span>
            @else <span class="text-gray-500 text-xs">İstemiyor</span>
            @endif
          </td>
          <td class="p-3 text-gray-500 whitespace-nowrap">{{ $note->callback_at?->format('d.m.Y H:i') ?? '—' }}</td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Henüz görüşme kaydı yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $notes->links() }}</div>
@endsection
