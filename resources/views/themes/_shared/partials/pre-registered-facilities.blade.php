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
    {{-- 18 Agustos 2026: kullanicinin talebi - on kayitli kurumlar TUM
         sitelerde/aramalarda kucuk kart olarak gorunmeli, sadece kurum
         detay sayfasindaki "Benzer Kurumlar" degil. Burada kendi buyuk kart
         + "Sahiplen" butonlu ayri bir tasarim vardi (kullanicinin daha once
         acikca reddettigi bir desen: on kayitli kartta tiklama SADECE tam
         detay sayfasina gitmeli, ayri aksiyon butonu olmamali) - artik
         digerleriyle aynı paylasilan kucuk yatay mini-kart kullaniliyor. --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($preRegistered as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
  @else
    <div class="rounded-xl border border-dashed border-gray-200 bg-white p-6 text-center shadow-sm">
      <div class="text-lg font-black text-gray-950">Bu bölüm için henüz onaylı ön kayıt yok.</div>
      <p class="text-sm text-gray-500 mt-2">Bu bölgedeki kurumlar tespit edilip ekibimiz tarafından onaylandığında kartlar burada otomatik görünür.</p>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug'], 'pre_registered' => 1]) }}" class="mt-4 inline-flex rounded-lg px-4 py-2 text-sm font-black text-white" style="background: {{ $colors['primary'] }};">Ön kayıt listesini aç</a>
    </div>
  @endif
</section>
