@extends('admin.layout')
@section('title', 'Bildirimler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Bildirimler</h1>

<div class="flex gap-2 mb-4">
  <a href="{{ route('admin.notifications.index', ['tab' => 'unread']) }}"
     class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $tab === 'unread' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
    <span>Okunmamış</span>
    <span class="text-xs {{ $tab === 'unread' ? 'text-gray-300' : 'text-gray-400' }}">{{ $unreadCount }}</span>
  </a>
  <a href="{{ route('admin.notifications.index', ['tab' => 'read']) }}"
     class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $tab === 'read' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
    <span>Okunmuş</span>
  </a>
</div>

<div class="bg-white rounded-xl shadow-sm divide-y">
  @forelse($notifications as $n)
    @php $actionUrl = notification_action_url($admin, $n->type, $n->data ?? []); @endphp
    <div class="p-4 flex items-start justify-between gap-3 {{ $n->read_at ? '' : 'bg-blue-50/50' }}">
      <div>
        @if($actionUrl)
          <a href="{{ $actionUrl }}" class="font-semibold hover:text-blue-700 hover:underline">{{ $n->title }}</a>
        @else
          <div class="font-semibold">{{ $n->title }}</div>
        @endif
        @if($n->body)<div class="text-sm text-gray-500 mt-1 whitespace-pre-line">{{ $n->body }}</div>@endif
        <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <div class="flex gap-2 shrink-0">
        @unless($n->read_at)
          <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
            @csrf
            <button class="text-xs text-blue-700 font-semibold whitespace-nowrap">Okundu işaretle</button>
          </form>
        @endunless
        <form method="POST" action="{{ route('admin.notifications.destroy', $n->id) }}" onsubmit="return confirm('Bu bildirim silinsin mi?');">
          @csrf @method('DELETE')
          <button class="text-xs text-gray-500 font-semibold whitespace-nowrap">Sil</button>
        </form>
      </div>
    </div>
  @empty
    <div class="p-6 text-sm text-gray-400">{{ $tab === 'read' ? 'Okunmuş bildirim yok.' : 'Okunmamış bildirim yok.' }}</div>
  @endforelse
</div>

<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
