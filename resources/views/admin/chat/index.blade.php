@extends('admin.layout')
@section('title', 'Canlı Sohbet')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Canlı Sohbet</h1>
  <div class="flex items-center gap-4">
    <a href="{{ route('admin.chat.stats') }}" class="text-sm text-blue-600">İstatistikler →</a>
    <a href="{{ route('admin.chat-settings.edit') }}" class="text-sm text-blue-600">Çalışma saatlerini ayarla →</a>
  </div>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="open" @selected($status==='open')>Açık</option>
    <option value="assigned" @selected($status==='assigned')>Üstlenilmiş</option>
    <option value="closed" @selected($status==='closed')>Kapalı</option>
    <option value="all" @selected($status==='all')>Tümü</option>
  </select>

  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm markalar</option>
    @foreach($brands as $b)
      <option value="{{ $b }}" @selected($brand===$b)>{{ $b }}</option>
    @endforeach
  </select>

  <select name="intent" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm bölümler</option>
    <option value="sohbet" @selected($intent==='sohbet')>💬 Sohbet</option>
    <option value="dertlesme" @selected($intent==='dertlesme')>🤍 Dertleşme</option>
    <option value="fikir" @selected($intent==='fikir')>💡 Fikir</option>
    <option value="temsilci" @selected($intent==='temsilci')>🎧 Temsilci</option>
  </select>

  <select name="city" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm iller</option>
    @foreach($cities as $c)
      <option value="{{ $c }}" @selected($city===$c)>{{ $c }}</option>
    @endforeach
  </select>

  @if($city || $intent || $brand)
    <a href="{{ route('admin.chat.index', ['status' => $status]) }}" class="text-xs text-gray-500 self-center hover:text-gray-700">Filtreleri temizle</a>
  @endif
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($threads as $thread)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap {{ $thread->unread_by_admin ? 'bg-amber-50' : '' }}">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          @if($thread->guest_avatar_url)
            <img src="{{ $thread->guest_avatar_url }}" alt="" class="w-6 h-6 rounded-full shrink-0">
          @endif
          <span class="font-bold text-gray-900">{{ $thread->guest_name ?? 'Misafir #'.$thread->id }}{{ $thread->guest_age ? ' ('.$thread->guest_age.')' : '' }}</span>
          @if($thread->sibling_threads_count > 1)
            <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $thread->sibling_threads_count }} sohbet</span>
          @endif
          @if($thread->unread_by_admin)<span class="bg-orange-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">yeni</span>@endif
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
          {{ $thread->brand }} · {{ ['sohbet' => 'Sohbet', 'dertlesme' => 'Dertleşme', 'fikir' => 'Fikir', 'temsilci' => 'Temsilci'][$thread->intent] ?? $thread->intent }} ·
          {{ ['erkek' => 'Bay', 'kadin' => 'Bayan', 'farketmez' => 'Farketmez'][$thread->operator_gender_preference] ?? '—' }} ·
          {{ $thread->city_name ?? '—' }} · İlgilenen: {{ $thread->assignedAdmin->name ?? '—' }} · {{ ($thread->last_message_at ?? $thread->created_at)->format('d.m.Y H:i') }}
        </div>
        <div class="text-xs text-gray-600 mt-1 break-words">{{ $thread->last_message_preview ?? '—' }}</div>
      </div>
      <a href="{{ route('admin.chat.show', $thread) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">Görüntüle</a>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $threads->links() }}</div>
@endsection
