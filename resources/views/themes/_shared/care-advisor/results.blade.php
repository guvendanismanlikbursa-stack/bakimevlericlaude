@extends('layouts.brand')
@section('title', $totalMatches.' Kurum Size Teklif Hazırlayabilir - Pusula')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <a href="{{ brand_route('care-advisor.form', ['bolum' => $activeSection['slug']]) }}" class="text-sm text-gray-500">← Kriterleri değiştir</a>
  <h1 class="text-3xl font-black text-gray-950 mt-2 mb-2">{{ $totalMatches }} kurum size teklif hazırlayabilir</h1>
  <p class="text-sm text-gray-500 mb-8">
    {{ $city?->name ?? 'Tüm iller' }}@if($data['district'] ?? null) / {{ $data['district'] }}@endif · {{ $category?->name ?? $activeSection['title'] }}
    @if($criteria['budget_max']) · {{ number_format($criteria['budget_max'], 0, ',', '.') }} TL ve altı bütçe @endif
  </p>

  {{-- 13 Ekim 2026 (4): kullanıcının talebi - "isim telefon almadan sonuç
       gösterilmemeli veya sadece 2 kurum gösterilmeli, tam sonuç için isim
       ve telefon girilme şartı aranmalı". İletişim bırakmayan ziyaretçiye
       sadece 2 kurum gösterilir, kalanları görmek için AYNI kriterlerle
       (gizli alanlar) isim+telefon ekleyip tekrar gönderir - quiz'i baştan
       doldurmasına gerek yok. --}}
  @if(! $isUnlocked && $totalMatches > $results->count())
    <div class="rounded-2xl border-2 p-5 mb-8" style="border-color: {{ $activeSection['theme']['primary'] }}; background: {{ $activeSection['theme']['soft'] }};">
      <p class="font-black text-gray-950 mb-1">✨ {{ $totalMatches }} kurum sizi bekliyor, şu an {{ $results->count() }} tanesini gösteriyoruz</p>
      <p class="text-sm text-gray-700 mb-4">Tümünü görmek ister misiniz? Adınızı ve telefonunuzu bırakmanız yeterli, kurumlar da size doğrudan ulaşabilsin — kriterlerinizi tekrar doldurmanıza gerek yok.</p>
      <form method="GET" action="{{ brand_route('care-advisor.results') }}" class="flex flex-col sm:flex-row gap-3">
        @foreach($data as $key => $value)
          @continue(in_array($key, ['name', 'phone', 'concerns', 'bolum'], true) || $value === null)
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        @foreach($data['concerns'] ?? [] as $concern)
          <input type="hidden" name="concerns[]" value="{{ $concern }}">
        @endforeach
        <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">
        <input type="text" name="name" required placeholder="Adınız Soyadınız" class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-white">
        <input type="tel" name="phone" required placeholder="05xx xxx xx xx" class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-white">
        <button type="submit" class="rounded-xl text-white font-black px-5 py-3 whitespace-nowrap" style="background: {{ $activeSection['theme']['primary'] }};">Tam Listeyi Gör →</button>
      </form>
    </div>
  @endif

  @if($results->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Bu kriterlere tam uyan kurum bulunamadı. Kriterleri gevşetmeyi deneyin.</p>
      <a href="{{ brand_route('care-advisor.form', ['bolum' => $activeSection['slug']]) }}" class="text-primary underline mt-2 inline-block">Kriterleri değiştir</a>
    </div>
  @else
    <div class="grid md:grid-cols-4 gap-6">
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
