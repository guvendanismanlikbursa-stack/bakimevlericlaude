@extends('admin.layout')
@section('title', 'İşlem Günlüğü')

@section('content')
<h1 class="text-2xl font-bold mb-6">Admin İşlem Günlüğü (Audit Log)</h1>

@if(request('entity_id'))
  <p class="mb-4 text-sm text-gray-600">{{ request('entity_type') === 'Expert' ? 'Uzman' : 'Kurum' }} #{{ (int) request('entity_id') }} için kayıtlar gösteriliyor. <a href="{{ route('admin.audit-log.index') }}" class="text-blue-600" style="text-decoration:underline">Tümünü göster</a></p>
@endif

<form method="GET" class="mb-4 flex gap-3">
  @if(request('entity_type'))<input type="hidden" name="entity_type" value="{{ request('entity_type') }}">@endif
  @if(request('entity_id'))<input type="hidden" name="entity_id" value="{{ request('entity_id') }}">@endif
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
        <span>· {{ $event->admin?->name ?? (($event->detail_json['kim'] ?? null) === 'kurum_paneli' ? 'Kurum paneli: '.($event->detail_json['facility_user_name'] ?? '#'.($event->detail_json['facility_user_id'] ?? '?')) : '—') }}</span>
        <span class="font-mono">· {{ $event->event_type }}</span>
        <span>· {{ $event->entity_type }}@if($event->entity_id) #{{ $event->entity_id }}@endif</span>
      </div>
      @if(is_array($event->detail_json['degisiklikler'] ?? null))
        {{-- 26 Kasım 2026: alan değişiklikleri okunur biçimde (eski → yeni). --}}
        <div class="mt-2 space-y-1 text-sm">
          @foreach($event->detail_json['degisiklikler'] as $alan => $ikili)
            @php
              $evetHayir = str_starts_with($alan, 'is_') || str_starts_with($alan, 'allows_');
              $goster = fn ($v) => $evetHayir && ($v === 0 || $v === 1 || $v === '0' || $v === '1') ? ((int) $v ? 'Evet' : 'Hayır') : ($v === null || $v === '' ? '(boş)' : (is_bool($v) ? ($v ? 'Evet' : 'Hayır') : (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v)));
            @endphp
            <div class="break-words">
              <span class="font-medium">{{ \App\Observers\AlanDegisiklikGozlemcisi::ALANLAR[$alan] ?? $alan }}:</span>
              <span class="text-red-600" style="text-decoration:line-through">{{ $goster($ikili[0] ?? null) }}</span>
              → <span class="text-green-700">{{ $goster($ikili[1] ?? null) }}</span>
            </div>
          @endforeach
        </div>
      @elseif($event->detail_json)
        <div class="text-xs text-gray-400 mt-1 break-words">{{ json_encode($event->detail_json, JSON_UNESCAPED_UNICODE) }}</div>
      @endif
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz kayıt yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $events->links() }}</div>
@endsection
