@extends('layouts.brand')
@section('title', 'Size Uygun '.$results->count().' Kurum Bulundu - Bakım Danışmanı')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <a href="{{ brand_route('care-advisor.form', ['bolum' => $activeSection['slug']]) }}" class="text-sm text-gray-500">← Kriterleri değiştir</a>
  <h1 class="text-3xl font-black text-gray-950 mt-2 mb-2">Size uygun {{ $results->count() }} kurum bulundu</h1>
  <p class="text-sm text-gray-500 mb-8">
    {{ $city?->name ?? 'Tüm iller' }} · {{ $category?->name ?? $activeSection['title'] }}
    @if($criteria['budget_max']) · {{ number_format($criteria['budget_max']) }} TL ve altı bütçe @endif
  </p>

  @if($results->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Bu kriterlere tam uyan kurum bulunamadı. Kriterleri gevşetmeyi deneyin.</p>
      <a href="{{ brand_route('care-advisor.form', ['bolum' => $activeSection['slug']]) }}" class="text-primary underline mt-2 inline-block">Kriterleri değiştir</a>
    </div>
  @else
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($results as $item)
        @php
          $facility = $item['facility'];
          $badgeHtml = '<span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded-full">Uyum: '.min(100, round($item['score'])).'/100</span>';
        @endphp
        <div>
          @include('themes._shared.partials.facility-card', ['facility' => $facility, 'badge' => $badgeHtml])
          @if(!empty($item['reasons']))
            <div class="flex flex-wrap gap-1 mt-2">
              @foreach($item['reasons'] as $reason)
                <span class="text-xs text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">✓ {{ $reason }}</span>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  <div class="mt-10 bg-amber-50 border border-amber-100 rounded-xl p-4 text-xs text-amber-800">
    Bu sıralama, girdiğiniz bilgilerin kurum profillerindeki hizmet açıklamalarıyla eşleşmesine dayanır; tıbbi bir teşhis veya idari bir uygunluk değerlendirmesi değildir. Kesin bilgi için kurumla doğrudan görüşmenizi öneririz.
  </div>
</div>
@endsection
