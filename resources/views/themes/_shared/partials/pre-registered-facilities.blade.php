<section class="max-w-6xl mx-auto px-4 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
    <div>
      <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Yeni eklenen ön kayıtlar</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-950">Ön Kayıtlı Kurumlar<span class="sr-only">On Kayitli Kurumlar</span></h2>
      <p class="text-sm text-gray-500 mt-1">Admin onayından geçen, yetkili sahiplenmesi bekleyen kurum profilleri burada listelenir.</p>
    </div>
    <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'pre_registered' => 1]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-black whitespace-nowrap" style="color: {{ $colors['primary'] }};">Filtreli listeyi aç</a>
  </div>

  @if(($preRegistered ?? collect())->isNotEmpty())
    <div class="grid md:grid-cols-3 gap-5">
      @foreach($preRegistered as $facility)
        @php($cardImage = facility_card_image($facility, $section))
        <article class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition min-w-0">
          <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="block">
            <div class="h-40 overflow-hidden flex items-center justify-center" style="background: {{ $colors['soft'] }};">
              <img src="{{ $cardImage }}" alt="{{ $facility->name }}" class="w-full h-full object-cover">
            </div>
            <div class="p-4 min-w-0">
              <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 mb-2">Ön kayıtlı</span>
              <h3 class="font-black text-gray-950 mb-1 break-words">{{ $facility->name }}</h3>
              <p class="text-sm text-gray-500 break-words">{{ $facility->city->name }} &middot; {{ $facility->district }} &middot; {{ $facility->category->name }}</p>
            </div>
          </a>
          <div class="px-4 pb-4 grid grid-cols-2 gap-2">
            <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-black text-gray-700 whitespace-nowrap">İncele</a>
            <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="rounded-lg px-3 py-2 text-center text-sm font-black text-white whitespace-nowrap" style="background: {{ $colors['primary'] }};">Sahiplen</a>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="rounded-xl border border-dashed border-gray-200 bg-white p-6 text-center shadow-sm">
      <div class="text-lg font-black text-gray-950">Bu bölüm için henüz onaylı ön kayıt yok.</div>
      <p class="text-sm text-gray-500 mt-2">Veri çekici ekranında kurumlar çekilip admin tarafından onaylandığında kartlar burada otomatik görünür.</p>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'pre_registered' => 1]) }}" class="mt-4 inline-flex rounded-lg px-4 py-2 text-sm font-black text-white" style="background: {{ $colors['primary'] }};">Ön kayıt listesini aç</a>
    </div>
  @endif
</section>
