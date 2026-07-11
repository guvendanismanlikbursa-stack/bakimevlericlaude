@extends('layouts.brand')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <a href="{{ brand_route('family.dashboard') }}" class="text-sm text-gray-500">← Panele dön</a>
  <h1 class="text-2xl font-bold mt-2 mb-6">Bildirimler</h1>

  <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    @include('themes._shared.partials.notification-permission')
  </div>

  <div class="bg-white rounded-xl shadow-sm divide-y">
    @forelse($notifications as $n)
      <div class="p-4 flex items-start justify-between gap-3 {{ $n->read_at ? '' : 'bg-blue-50/50' }}">
        <div>
          <div class="font-semibold">{{ $n->title }}</div>
          @if($n->body)<div class="text-sm text-gray-500 mt-1">{{ $n->body }}</div>@endif
          <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->format('d.m.Y H:i') }}</div>
        </div>
        @unless($n->read_at)
          <form method="POST" action="{{ brand_route('family.notifications.read', $n->id) }}">
            @csrf
            <button class="text-xs text-primary font-semibold whitespace-nowrap">Okundu işaretle</button>
          </form>
        @endunless
      </div>
    @empty
      <div class="p-6 text-sm text-gray-400">Henüz bildiriminiz yok.</div>
    @endforelse
  </div>

  <div class="mt-4">{{ $notifications->links() }}</div>
</div>
@endsection
