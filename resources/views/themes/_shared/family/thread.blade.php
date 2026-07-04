@extends('layouts.brand')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
  <a href="{{ brand_route('family.dashboard') }}" class="text-sm text-gray-500">← Panelime dön</a>
  <h1 class="text-xl font-bold mt-2 mb-6">{{ $offerRequest->facility->name ?? optional($offerRequest->quotes->first())->facility->name ?? 'Talep' }} ile mesajlaşma</h1>

  <div class="bg-white rounded-xl shadow-sm p-4 space-y-3 mb-4 max-h-96 overflow-y-auto">
    @forelse($offerRequest->messages as $m)
      <div class="{{ $m->sender_type === 'family' ? 'text-right' : 'text-left' }}">
        <div class="inline-block px-3 py-2 rounded-lg text-sm {{ $m->sender_type === 'family' ? 'bg-primary text-white' : 'bg-gray-100' }}">{{ $m->body }}</div>
        <div class="text-xs text-gray-400 mt-1">{{ $m->created_at->format('d.m.Y H:i') }}</div>
      </div>
    @empty
      <p class="text-sm text-gray-400">Henüz mesaj yok.</p>
    @endforelse
  </div>

  <form method="POST" action="{{ brand_route('family.thread.store', $offerRequest) }}" class="flex gap-2">
    @csrf
    <input type="text" name="body" placeholder="Mesajınızı yazın..." required class="border rounded-lg px-3 py-2 flex-1">
    <button class="btn-primary px-5 py-2 rounded-lg font-semibold">Gönder</button>
  </form>
</div>
@endsection
