@extends('layouts.brand')
@section('title', 'Yorumlarım | Kurum Paneli')
@section('content')

@include('themes._shared.partials.facility-panel-header', ['title' => 'Kurumuma Yazılan Yorumlar', 'subtitle' => 'Onaylı yorumlara kamuya açık tek bir yanıt verebilirsiniz.'])

<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="space-y-4">
    @forelse($reviews as $review)
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div class="font-black text-gray-950">{{ $review->reviewer_name }}</div>
          <div class="text-amber-500 font-black">★ {{ $review->rating }}</div>
        </div>
        <p class="text-sm text-gray-600 mt-2">{{ $review->body }}</p>
        <div class="text-xs text-gray-400 mt-2">{{ optional($review->approved_at)->format('d.m.Y') }}</div>

        @if($review->facility_reply)
          <div class="mt-4 rounded-lg bg-gray-50 border border-gray-100 p-4">
            <div class="text-xs font-black text-gray-500 mb-1">Yanıtınız ({{ $review->facility_replied_at->format('d.m.Y') }})</div>
            <p class="text-sm text-gray-700">{{ $review->facility_reply }}</p>
          </div>
          <form method="POST" action="{{ brand_route('facility.reviews.reply', $review) }}" class="mt-3">
            @csrf
            <details>
              <summary class="text-xs font-black text-primary cursor-pointer">Yanıtı düzenle</summary>
              <textarea name="facility_reply" rows="3" maxlength="1000" class="border rounded-lg px-3 py-2 w-full mt-2 text-sm">{{ $review->facility_reply }}</textarea>
              <button class="btn-primary rounded-lg px-4 py-2 text-sm font-black mt-2">Yanıtı Güncelle</button>
            </details>
          </form>
        @else
          <form method="POST" action="{{ brand_route('facility.reviews.reply', $review) }}" class="mt-4">
            @csrf
            <textarea name="facility_reply" rows="3" maxlength="1000" placeholder="Bu yoruma kamuya açık bir yanıt yazın..." required class="border rounded-lg px-3 py-2 w-full text-sm"></textarea>
            <button class="btn-primary rounded-lg px-4 py-2 text-sm font-black mt-2">Yanıtla</button>
          </form>
        @endif
      </div>
    @empty
      <div class="bg-white border border-dashed rounded-xl p-8 text-center text-gray-500">Kurumunuza henüz onaylı bir yorum yazılmamış.</div>
    @endforelse
  </div>
</div>
@endsection
