{{--
  15 Ekim 2026: kullanicinin talebi - "editor secimi / bu ay one cikanlar"
  vitrini. BILEREK is_featured (odeme bazli) rozetiyle KARISTIRILMAZ - bu
  bolum SADECE gercek puan + yeterli yorum sayisi esigini gecen kurumlari
  gosterir (bkz. HomeController::index() $editorsPicks), "burada gercekten
  birisi bakip seciyor" hissi versin diye. Hicbir kurum esigi gecmezse bu
  partial hic cagrilmaz (bkz. @if kontrolu).
--}}
@if($editorsPicks->isNotEmpty())
<section class="bg-gray-50 border-b border-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex items-center gap-2 mb-5">
      <span class="text-xl">🏅</span>
      <div>
        <div class="text-xs font-black uppercase tracking-wide" style="color: {{ $colors['primary'] }};">Editör Seçimi</div>
        <h2 class="text-xl font-black text-gray-950">Yüksek puanlı, dikkat çeken kurumlar</h2>
      </div>
    </div>
    <div class="grid sm:grid-cols-3 gap-5">
      @foreach($editorsPicks as $facility)
        <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block rounded-xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden">
          <div class="h-32 overflow-hidden bg-gray-100">
            <img src="{{ facility_card_image($facility, service_section_for_scope($facility->category->brand_scope)) }}" alt="{{ $facility->imageAltText() }}" loading="lazy" class="w-full h-full object-cover">
          </div>
          <div class="p-4">
            <div class="font-black text-gray-950 truncate">{{ $facility->name }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $facility->city->name }} · {{ $facility->district }}</div>
            <div class="text-amber-700 font-black text-sm mt-2">★ {{ number_format($facility->rating, 1) }}</div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif
