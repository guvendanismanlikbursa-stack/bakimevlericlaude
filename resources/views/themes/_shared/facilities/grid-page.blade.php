@extends('layouts.brand')
@section('title', $title)
@section('meta_description', ($subtitle ?? $title).' | '.current_brand()['name'])
@section('content')
@php $brand = current_brand(); @endphp
<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="mb-6">
    <h1 class="text-3xl font-black text-gray-950">{{ $title }}</h1>
    @if($subtitle ?? null)<p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>@endif
  </div>

  @isset($statsBar){!! $statsBar !!}@endisset

  @if($facilities->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Bu kritere uygun kurum bulunamadı.</p>
      <a href="{{ brand_route('facilities.index') }}" class="text-primary underline mt-2 inline-block">Tüm kurumlara git</a>
    </div>
  @else
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($facilities as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility, 'badge' => $badges[$facility->id] ?? null])
      @endforeach
    </div>
    @if(method_exists($facilities, 'links'))
      <div class="mt-8">{{ $facilities->links() }}</div>
    @endif
  @endif
</div>
@endsection
