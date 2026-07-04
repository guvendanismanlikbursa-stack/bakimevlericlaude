@extends('layouts.brand')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-1">Son Eklenen Fotoğraflar</h1>
  <p class="text-sm text-gray-500 mb-6">Kurumların galerilerine en son eklenen görseller.</p>

  @if($images->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Henüz görsel eklenmemiş.</p>
    </div>
  @else
    <div class="grid sm:grid-cols-3 md:grid-cols-4 gap-4">
      @foreach($images as $image)
        <a href="{{ brand_route('facilities.show', ['slug' => $image->facility->slug]) }}" class="group block">
          <div class="h-36 rounded-xl overflow-hidden bg-gray-100 border border-gray-100">
            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->facility->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
          </div>
          <p class="text-xs font-semibold text-gray-700 mt-1.5 truncate">{{ $image->facility->name }}</p>
          <p class="text-xs text-gray-400">{{ $image->facility->city->name ?? '' }} · {{ $image->created_at->diffForHumans() }}</p>
        </a>
      @endforeach
    </div>
    <div class="mt-8">{{ $images->links() }}</div>
  @endif
</div>
@endsection
