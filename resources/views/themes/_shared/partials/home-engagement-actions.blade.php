@php
  $brand = current_brand();
  $theme = $brand['theme'];
  $actions = [
    'yasli-bakim' => [
      ['title' => 'Bakım ihtiyacını seç', 'text' => 'Sağlık takibi, oda tipi ve ziyaret düzenine göre listeyi daralt.', 'href' => brand_route('engagement.wizard', ['bolum' => $section['slug']])],
      ['title' => 'Kurumları yan yana koy', 'text' => 'Fiyat, kapasite, hizmet ve konum bilgilerini aynı tabloda gör.', 'href' => brand_route('engagement.compare')],
      ['title' => 'Ziyaret listesi hazırla', 'text' => 'Görüşmede sorulacak yaşlı bakım sorularını rehberden oku.', 'href' => brand_route('pages.show', ['slug' => $section['slug'].'-ziyaret-kontrol-listesi'])],
    ],
    'cocuk' => [
      ['title' => 'Yaş grubuna göre ara', 'text' => 'Kreş, anaokulu ve özel eğitim seçeneklerini ihtiyaca göre filtrele.', 'href' => brand_route('engagement.wizard', ['bolum' => $section['slug']])],
      ['title' => 'Eğitim programını incele', 'text' => 'Sınıf mevcudu, servis, yemek ve gelişim takibi başlıklarını kontrol et.', 'href' => brand_route('pages.show', ['slug' => $section['slug'].'-egitim-programi'])],
      ['title' => 'Aile kısa listesi yap', 'text' => 'Beğendiğin kurumları favoriye al, sonra ailece tekrar bak.', 'href' => brand_route('engagement.favorites')],
    ],
    'rehabilitasyon' => [
      ['title' => 'Terapi hedefini seç', 'text' => 'Fizyoterapi, ekipman, seans ve ev programına göre doğru listeye git.', 'href' => brand_route('engagement.wizard', ['bolum' => $section['slug']])],
      ['title' => 'Uzman kadroyu karşılaştır', 'text' => 'Merkezleri branş, ekipman, puan ve fiyat bilgisiyle yan yana incele.', 'href' => brand_route('engagement.compare')],
      ['title' => 'Ev programı takibini oku', 'text' => 'Merkez dışı takip ve raporlama sürecini rehberle netleştir.', 'href' => brand_route('pages.show', ['slug' => $section['slug'].'-ev-programi-takip'])],
    ],
  ][$section['slug']] ?? [];
@endphp
<div class="{{ $theme === 'bakimevleri' ? 'grid md:grid-cols-3 gap-3 max-w-3xl mt-7' : ($theme === 'bakimeviara' ? 'grid md:grid-cols-3 gap-3 mt-6' : 'grid md:grid-cols-3 gap-3 mb-7') }}">
  @foreach($actions as $action)
    <a href="{{ $action['href'] }}" class="{{ $theme === 'bakimevleri' ? 'rounded-xl border border-white/14 bg-white/8 p-4 text-white hover:bg-white/14' : ($theme === 'bakimeviara' ? 'rounded-2xl bg-white border border-gray-100 shadow-sm p-4 hover:shadow-md' : 'rounded-lg bg-white border border-gray-100 shadow-sm p-4 hover:shadow-md') }} transition">
      <div class="text-sm font-black {{ $theme === 'bakimevleri' ? 'text-white' : 'text-gray-950' }}">{{ $action['title'] }}</div>
      <p class="text-xs leading-relaxed mt-1 {{ $theme === 'bakimevleri' ? 'text-white/64' : 'text-gray-500' }}">{{ $action['text'] }}</p>
    </a>
  @endforeach
</div>