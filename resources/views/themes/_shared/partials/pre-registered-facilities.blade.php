<section class="max-w-6xl mx-auto px-4 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
    <div>
      <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Yeni eklenen &ouml;n kay&#305;tlar</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-950">&Ouml;n Kay&#305;tl&#305; Kurumlar<span class="sr-only">On Kayitli Kurumlar</span></h2>
      <p class="text-sm text-gray-500 mt-1">Admin onay&#305;ndan ge&ccedil;en, yetkili sahiplenmesi bekleyen kurum profilleri burada listelenir.</p>
    </div>
    <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'pre_registered' => 1]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-black whitespace-nowrap" style="color: {{ $colors['primary'] }};">Filtreli listeyi a&ccedil;</a>
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
              <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 mb-2">&Ouml;n kay&#305;tl&#305;</span>
              <h3 class="font-black text-gray-950 mb-1 break-words">{{ $facility->name }}</h3>
              <p class="text-sm text-gray-500 break-words">{{ $facility->city->name }} &middot; {{ $facility->district }} &middot; {{ $facility->category->name }}</p>
            </div>
          </a>
          <div class="px-4 pb-4 grid grid-cols-2 gap-2">
            <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-black text-gray-700 whitespace-nowrap">&#304;ncele</a>
            <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="rounded-lg px-3 py-2 text-center text-sm font-black text-white whitespace-nowrap" style="background: {{ $colors['primary'] }};">Sahiplen</a>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="rounded-xl border border-dashed border-gray-200 bg-white p-6 text-center shadow-sm">
      <div class="text-lg font-black text-gray-950">Bu b&ouml;l&uuml;m i&ccedil;in hen&uuml;z onayl&#305; &ouml;n kay&#305;t yok.</div>
      <p class="text-sm text-gray-500 mt-2">Veri &ccedil;ekici ekran&#305;nda kurumlar &ccedil;ekilip admin taraf&#305;ndan onayland&#305;&#287;&#305;nda kartlar burada otomatik g&ouml;r&uuml;n&uuml;r.</p>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'pre_registered' => 1]) }}" class="mt-4 inline-flex rounded-lg px-4 py-2 text-sm font-black text-white" style="background: {{ $colors['primary'] }};">&Ouml;n kay&#305;t listesini a&ccedil;</a>
    </div>
  @endif
</section>
