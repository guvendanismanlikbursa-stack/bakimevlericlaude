{{-- 28 Agustos 2026: kullanicinin talebi - "sahiplenilmis kurumlar" (Öne
     Çıkanlar'da olmayan, is_claimed=true kurumlar) Öne Çıkanlar ile Ön
     Kayıtlı Kurumlar arasinda ayri bir bolum. Ön Kayıtlı'nin aksine bu
     kurumlar zaten dogrulanmis/aktif oldugu icin bos oldugunda ayrica bir
     "henuz yok" mesaji GOSTERILMEZ, bolum tumuyle atlanir - bkz. cagiran
     _results.blade.php'deki @if(isNotEmpty) sarti. --}}
@if(($claimedFacilities ?? collect())->isNotEmpty())
  <section class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">
      <div>
        <div class="text-sm font-black mb-1" style="color: {{ $colors['primary'] }};">Aktif profiller</div>
        <h2 class="text-2xl md:text-3xl font-black text-gray-950">Sahiplenilmiş Kurumlar</h2>
        <p class="text-sm text-gray-600 mt-1">Yetkilisi tarafından onaylanmış, güncel bilgilere sahip kurum profilleri.</p>
      </div>
      <a href="{{ brand_route('facilities.index', ['bolum' => $section['slug']]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-black whitespace-nowrap" style="color: {{ $colors['primary'] }};">Tüm kurumları gör</a>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      @foreach($claimedFacilities as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
  </section>
@endif
