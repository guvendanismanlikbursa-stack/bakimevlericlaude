@extends('layouts.brand')
@section('content')
@include('themes._shared.partials.facility-panel-header', ['title' => 'Aile Soruları'])

<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="bg-white rounded-xl shadow-sm divide-y">
    @forelse($questions as $q)
      <div class="p-5">
        <div class="text-xs text-gray-400 mb-1">{{ $q->asker_name ?: 'Ziyaretçi' }} · {{ $q->created_at->format('d.m.Y H:i') }}</div>
        <div class="font-semibold text-gray-900 mb-3">{{ $q->question }}</div>
        @if($q->answer)
          <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
            <span class="text-xs font-black text-primary block mb-1">Cevabınız</span>
            {{ $q->answer }}
          </div>
        @else
          <form method="POST" action="{{ brand_route('facility.questions.answer', $q) }}" class="flex gap-2">
            @csrf
            <label for="answer-{{ $q->id }}" class="sr-only">Cevabınız</label>
            <textarea id="answer-{{ $q->id }}" name="answer" rows="2" required placeholder="Cevabınızı yazın..." class="border rounded-lg px-3 py-2 text-sm flex-1"></textarea>
            <button class="btn-primary rounded-lg px-4 text-sm font-black">Yanıtla</button>
          </form>
        @endif
      </div>
    @empty
      <div class="p-6 text-sm text-gray-400">Henüz size sorulmuş bir soru yok.</div>
    @endforelse
  </div>

  <div class="mt-4">{{ $questions->links() }}</div>
</div>
@endsection
