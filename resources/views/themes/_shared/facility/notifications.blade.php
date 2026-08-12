@extends('layouts.brand')

@section('content')
@include('themes._shared.partials.facility-panel-header', ['title' => 'Bildirimler'])

<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    @include('themes._shared.partials.notification-permission')
  </div>

  <div class="bg-white rounded-xl shadow-sm divide-y">
    @forelse($notifications as $n)
      @php $actionUrl = notification_action_url($user, $n->type, $n->data ?? []); @endphp
      <div class="p-4 flex items-start justify-between gap-3 {{ $n->read_at ? '' : 'bg-blue-50/50' }}">
        <div>
          @if($actionUrl)
            <a href="{{ $actionUrl }}" class="font-semibold hover:text-primary hover:underline">{{ $n->title }}</a>
          @else
            <div class="font-semibold">{{ $n->title }}</div>
          @endif
          @if($n->body)<div class="text-sm text-gray-500 mt-1">{{ $n->body }}</div>@endif
          <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->format('d.m.Y H:i') }}</div>
        </div>
        @unless($n->read_at)
          <form method="POST" action="{{ brand_route('facility.notifications.read', $n->id) }}">
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
