@extends('admin.layout')
@section('title', 'Canlı Sohbet')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Canlı Sohbet</h1>
  <a href="{{ route('admin.chat-settings.edit') }}" class="text-sm text-blue-600">Çalışma saatlerini ayarla →</a>
</div>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="open" @selected($status==='open')>Açık</option>
    <option value="assigned" @selected($status==='assigned')>Üstlenilmiş</option>
    <option value="closed" @selected($status==='closed')>Kapalı</option>
    <option value="all" @selected($status==='all')>Tümü</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Marka</th><th class="p-3">Niyet</th><th class="p-3">Tercih</th><th class="p-3">Şehir</th><th class="p-3">Son mesaj</th><th class="p-3">İlgilenen</th><th class="p-3">Tarih</th><th class="p-3"></th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($threads as $thread)
        <tr class="{{ $thread->unread_by_admin ? 'bg-amber-50' : '' }}">
          <td class="p-3 font-medium">{{ $thread->brand }}</td>
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
        <tr><td class="p-3 text-gray-400" colspan="8">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $threads->links() }}</div>
@endsection
