@extends('admin.layout')
@section('title', 'İşlem Günlüğü')

@section('content')
<h1 class="text-2xl font-bold mb-6">Admin İşlem Günlüğü (Audit Log)</h1>

<form method="GET" class="mb-4 flex gap-3">
  <select name="event_type" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İşlem Tipleri</option>
    @foreach($eventTypes as $type)<option value="{{ $type }}" @selected(request('event_type')===$type)>{{ $type }}</option>@endforeach
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Tarih</th><th class="p-3">Admin</th><th class="p-3">İşlem</th><th class="p-3">Kayıt</th><th class="p-3">Detay</th></tr></thead>
    <tbody class="divide-y">
      @forelse($events as $event)
        <tr>
          <td class="p-3 text-gray-400 whitespace-nowrap">{{ $event->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3">{{ $event->admin?->name ?? '—' }}</td>
          <td class="p-3 font-mono text-xs">{{ $event->event_type }}</td>
          <td class="p-3 text-gray-500">{{ $event->entity_type }} @if($event->entity_id) #{{ $event->entity_id }} @endif</td>
          <td class="p-3 text-gray-400 text-xs">{{ $event->detail_json ? json_encode($event->detail_json, JSON_UNESCAPED_UNICODE) : '—' }}</td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="5">Henüz kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $events->links() }}</div>
@endsection
