@extends('layouts.brand')
@section('title', 'Bakım Rehberi - Karar Vermenize Yardımcı Yazılar')
@section('meta_description', current_brand()['name'].' ile bakım kararlarınıza yardımcı olabilecek rehber makaleleri, sık sorulan soruları ve uzman tavsiyelerini keşfedin.')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-2">Bakım Rehberi</h1>
  <p class="text-sm text-gray-500 mb-8">Karar verirken işinize yarayacak yazılar.</p>

  @if($guides->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Henüz rehber makalesi eklenmedi.</p>
    </div>
  @else
    <div class="grid md:grid-cols-2 gap-5">
      @foreach($guides as $guide)
        <a href="{{ brand_route('pages.show', ['slug' => $guide->slug]) }}" class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition block">
          <h2 class="font-black text-lg text-gray-950 mb-2">{{ $guide->title }}</h2>
          @if($guide->summary)<p class="text-sm text-gray-600 line-clamp-3">{{ $guide->summary }}</p>@endif
          <span class="text-xs text-primary font-black mt-3 inline-block">Devamını oku →</span>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
