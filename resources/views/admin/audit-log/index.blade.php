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

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($events as $event)
    <div class="p-4">
      <div class="flex items-center gap-2 flex-wrap text-xs text-gray-500">
        <span>{{ $event->created_at->format('d.m.Y H:i') }}</span>
        <span>· {{ $event->admin?->name ?? '—' }}</span>
        <span class="font-mono">· {{ $event->event_type }}</span>
        <span>· {{ $event->entity_type }}@if($event->entity_id) #{{ $event->entity_id }}@endif</span>
      </div>
      @if($event->detail_json)
        <div class="text-xs text-gray-400 mt-1 break-words">{{ json_encode($event->detail_json, JSON_UNESCAPED_UNICODE) }}</div>
      @endif
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz kayıt yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $events->links() }}</div>
@endsection
