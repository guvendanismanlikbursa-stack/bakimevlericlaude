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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Marka</th><th class="p-3">Ziyaretçi</th><th class="p-3">Bölüm</th><th class="p-3">Tercih</th><th class="p-3">Şehir</th><th class="p-3">Son mesaj</th><th class="p-3">İlgilenen</th><th class="p-3">Tarih</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($threads as $thread)
        <tr class="{{ $thread->unread_by_admin ? 'bg-amber-50' : '' }}">
          <td class="p-3 font-medium">{{ $thread->brand }}</td>
          <td class="p-3">
            <div class="flex items-center gap-2">
              @if($thread->guest_avatar_url)
                <img src="{{ $thread->guest_avatar_url }}" alt="" class="w-6 h-6 rounded-full shrink-0">
              @endif
              <span>{{ $thread->guest_name ?? 'Misafir #'.$thread->id }}{{ $thread->guest_age ? ' ('.$thread->guest_age.')' : '' }}</span>
              @if($thread->sibling_threads_count > 1)
                <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $thread->sibling_threads_count }} sohbet</span>
              @endif
            </div>
          </td>
          <td class="p-3">{{ ['sohbet' => 'Sohbet', 'dertlesme' => 'Dertleşme', 'fikir' => 'Fikir', 'temsilci' => 'Temsilci'][$thread->intent] ?? $thread->intent }}</td>
          <td class="p-3">{{ ['erkek' => 'Bay', 'kadin' => 'Bayan', 'farketmez' => 'Farketmez'][$thread->operator_gender_preference] ?? '—' }}</td>
          <td class="p-3">{{ $thread->city_name ?? '—' }}</td>
          <td class="p-3 max-w-xs truncate">{{ $thread->last_message_preview ?? '—' }}
            @if($thread->unread_by_admin)<span class="ml-1 bg-orange-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">yeni</span>@endif
          </td>
          <td class="p-3">{{ $thread->assignedAdmin->name ?? '—' }}</td>
          <td class="p-3 text-gray-400">{{ ($thread->last_message_at ?? $thread->created_at)->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right"><a href="{{ route('admin.chat.show', $thread) }}" class="text-blue-600">Görüntüle</a></td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="9">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $threads->links() }}</div>
@endsection
