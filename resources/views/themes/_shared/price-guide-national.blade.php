@extends('layouts.brand')
{{-- 18 Kasım 2026: kullanıcının talebi - "her aramada en az ilk sayfada çıkmalıyız". İl belirtilmeyen "kreş fiyatları",
     "bakımevi fiyatları", "fizik tedavi fiyatları" aramaları için ülke geneli sayfa (bkz. PriceGuideController::national).
     Uydurma fiyat yok: yalnız kurumların kendi girdiği ücretler ve config/resmi_ucretler.php'deki resmi rakamlar.
     Not: canlıda Tailwind yeniden derlenmediği için yalnız price-guide.blade.php'de zaten kullanılan sınıflar + satır içi stil. --}}
@php
  $yil = now()->year;
  $konu = $category->name ?? $section['seo_title'] ?? $section['title'];
  $konuKucuk = mb_strtolower($konu);
  $tl = fn ($v) => number_format((float) $v, 0, ',', '.').' TL';
  $ilSayisi = count($iller);
  $fiyatliIl = count(array_filter($iller, fn ($i) => $i['fiyatli'] > 0));
  $ilUrl = fn ($slug) => $category
      ? brand_route('price-guide.category', ['sectionSlug' => $section['slug'], 'citySlug' => $slug, 'categorySlug' => $category->slug])
      : brand_route('price-guide.show', ['sectionSlug' => $section['slug'], 'citySlug' => $slug]);
  $resmiYil = config('resmi_ucretler.yil');
  $resmiYasli = $section['slug'] === 'yasli-bakim' ? (config('resmi_ucretler.yasli_bakim') ?: []) : [];
  $meb = ($section['slug'] === 'rehabilitasyon' || ($category && $category->brand_scope === 'ozel-egitim')) ? config('resmi_ucretler.meb_ozel_egitim') : null;
  $sehirAdlari = \App\Models\City::cachedAll()->keyBy('slug');
  // 18 Kasım 2026: kreş/anaokulu aylık ücret özeti (bkz. config/piyasa_ucretleri.php).
  $piyasaGoster = $section['slug'] === 'cocuk' && (! $category || $category->brand_scope === 'cocuk-bakim');
  $piyasaGenel = $piyasaGoster ? config('piyasa_ucretleri.genel') : null;

  $belirleyenler = [
    'cocuk' => [
      'Çocuğun yaşı: 0–2 yaş bebek grubu, bakım ihtiyacı yüksek olduğu için genelde 3–6 yaş grubundan pahalıdır.',
      'Tam gün mü yarım gün mü: tam gün (yemekli) programlar yarım günden yüksek ücretlidir.',
      'Dahil olanlar: yemek, servis, kırtasiye, yabancı dil ve branş dersleri ücrete dahil mi, ayrı mı alınıyor?',
      'Konum ve bina olanakları: bahçe, oyun alanı, ilçe ve bina standardı ücreti etkiler.',
      'İndirimler: erken kayıt, kardeş ve peşin ödeme indirimlerini mutlaka sorun.',
    ],
    'yasli-bakim' => [
      'Resmi taban–tavan: özel huzurevi ücretleri her ilde Valilik ücret tespit komisyonunca belirlenen aralıkta olmak zorundadır.',
      'Oda tipi: tek kişilik oda, iki-üç kişilik odaya göre daha yüksek ücretlidir.',
      'Bakım ihtiyacı: yatalak, Alzheimer/demans veya yoğun bakım gerektiren durumlarda ücret artar.',
      'Sağlık hizmetleri: 7/24 hemşire, doktor takibi, fizyoterapi ücrete dahil mi, ayrı mı?',
      'Ek kalemler: ilaç, bez, özel diyet ve refakat ücretlerini önceden yazılı olarak isteyin.',
    ],
    'rehabilitasyon' => [
      'Seans sayısı ve süresi: haftalık seans sayısı ve seans uzunluğu ücreti doğrudan belirler.',
      'Bireysel mi grup mu: bireysel seanslar grup seanslarından pahalıdır.',
      'Devlet desteği: özel eğitim raporu varsa MEB, aylık belirli saatin ücretini kuruma öder.',
      'Uzman ve cihaz: uzman fizyoterapist/terapist ve kullanılan cihazlar ücreti etkiler.',
      'Paket ve iptal koşulları: seans paketlerinin iade ve iptal kurallarını önceden öğrenin.',
    ],
  ][$section['slug']] ?? [];

  $sss = [
    [$konu.' fiyatları '.$yil.' yılında ne kadar?',
      $piyasaGenel
        ? 'Özel anaokulu ve kreşlerin aylık ücreti Türkiye genelinde ortalama '.$tl($piyasaGenel['ort']).'; okulların çoğu '.$tl($piyasaGenel['alt']).' ile '.$tl($piyasaGenel['ust']).' arasında ücret alıyor ('.number_format($piyasaGenel['n'], 0, ',', '.').' okulun ilan ettiği ücretlerden). İl ve ilçeye göre fark büyüktür; ilinizin sayfasında o ilin ortalamasını görebilirsiniz.'
        : ($fiyatliIl
        ? 'Platformumuzda fiyatını kendisi giren '.number_format($fiyat['n'], 0, ',', '.').' kurumun başlangıç ücreti ortalaması '.$tl($fiyat['ort']).'. Ücret ile, ilçeye ve hizmet kapsamına göre değişir; ilinizin sayfasında o ile ait kurumları ve girilmiş ücretleri görebilirsiniz.'
        : 'Ücret ile, ilçeye ve hizmet kapsamına göre değişir. Kurumların çoğu fiyatını ilan etmediği için ilinizin sayfasından birkaç kuruma aynı anda ücretsiz fiyat talebi göndermenizi öneririz.')],
    [$konu.' fiyatlarını en hızlı nasıl öğrenirim?',
      'Aşağıdaki listeden ilinizi seçin; o ildeki kurumları puan ve konuma göre karşılaştırıp istediğiniz kurumlara ücretsiz bilgi ve fiyat talebi gönderebilirsiniz. Talebiniz doğrudan kuruma iletilir.'],
    ['Hangi ilde kaç '.$konuKucuk.' kurumu var?',
      'Türkiye genelinde '.$ilSayisi.' ilde toplam '.number_format($toplam, 0, ',', '.').' kurum listeleniyor. En çok kurum '.collect($iller)->take(3)->map(fn ($i) => $i['ad'].' ('.$i['kurum'].')')->implode(', ').' illerinde.'],
  ];
@endphp
@section('title', $konu.' Fiyatları '.$yil.($piyasaGenel ? ': İllere Göre Aylık Ücretler, Ort. '.$tl($piyasaGenel['ort']) : ': İllere Göre Ücretler ve Kurumlar'))
@section('og_title', $konu.' Fiyatları '.$yil.' – İllere Göre')
@section('meta_description', ($piyasaGenel ? $konu.' fiyatları '.$yil.': aylık ortalama '.$tl($piyasaGenel['ort']).', çoğu '.$tl($piyasaGenel['alt']).' – '.$tl($piyasaGenel['ust']).' arası. ' : '').'Türkiye genelinde '.number_format($toplam, 0, ',', '.').' '.$konuKucuk.' kurumu: il il kurum sayısı, kurumların girdiği ücretler'.($resmiYasli ? ', resmi taban–tavan ücretler' : ($meb ? ', MEB desteği' : '')).' ve aile puanları. İlinizi seçin, ücretsiz fiyat isteyin.')
@section('og_image', seo_og_image($section))
@if($__sahipUrl = bolum_sahibi_url($section['slug'] ?? null))
  @section('canonical', $__sahipUrl)
@endif
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => array_values(array_filter([
      ['name' => $brand['name'], 'url' => brand_route('home')],
      ['name' => ($section['seo_title'] ?? $section['title']).' Fiyatları', 'url' => brand_route('price-guide.national', ['sectionSlug' => $section['slug']])],
      $category ? ['name' => $category->name.' Fiyatları', 'url' => url()->current()] : null,
  ]))])
  @include('themes._shared.partials.faqpage-jsonld', ['questions' => $sss])
@endsection
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="text-xs font-black text-primary mb-2">Ücret Rehberi · Türkiye geneli</div>
  <h1 class="text-3xl font-black text-gray-950 mb-2">{{ $konu }} Fiyatları {{ $yil }}</h1>
  <p class="text-sm text-gray-500 mb-3">Son güncelleme: {{ now()->locale('tr')->translatedFormat('j F Y') }} · {{ $ilSayisi }} il · {{ number_format($toplam, 0, ',', '.') }} kurum</p>
  <p class="text-sm text-gray-700 font-semibold mb-8">{{ $konu }} ücretleri ile, ilçeye ve hizmet kapsamına göre büyük farklılık gösterir. Aşağıda Türkiye'nin {{ $ilSayisi }} ilinde listelenen {{ number_format($toplam, 0, ',', '.') }} {{ $konuKucuk }} kurumunu il il bulabilir, ilinizin sayfasında kurumları karşılaştırıp ücretsiz fiyat talebi gönderebilirsiniz.
    Burada yalnız kurumların kendi girdiği ücretleri ve resmi kaynaklı rakamları gösteriyoruz; tahmini fiyat yazmıyoruz.</p>

  @if(count($kategoriler) > 1)
    <div class="flex flex-wrap gap-2 mb-8">
      <a href="{{ brand_route('price-guide.national', ['sectionSlug' => $section['slug']]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ! $category ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">Tümü</a>
      @foreach($kategoriler as $k)
        <a href="{{ brand_route('price-guide.national-category', ['sectionSlug' => $section['slug'], 'categorySlug' => $k['slug']]) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ($category->slug ?? null) === $k['slug'] ? 'bg-primary text-white' : 'bg-gray-50 border border-gray-100 text-gray-700' }}">{{ $k['ad'] }} Fiyatları</a>
      @endforeach
    </div>
  @endif

  @if($resmiYasli)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
      <div class="text-xs text-gray-500">Resmi huzurevi ücret aralıkları ({{ $resmiYil }}, aylık, KDV hariç)</div>
      <div style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));margin-top:8px;">
        @foreach($resmiYasli as $slug => $r)
          <a href="{{ $ilUrl($slug) }}" class="text-sm text-gray-700" style="display:block;border:1px solid #f1f5f9;border-radius:10px;padding:8px 10px;">
            <b>{{ $sehirAdlari[$slug]->name ?? $slug }}</b>: {{ $tl($r['taban']) }} – {{ $tl($r['tavan']) }}
          </a>
        @endforeach
      </div>
      <p class="text-sm text-gray-600 mt-2">Valilik ücret tespit komisyonu kararları. Diğer illerin tablosu kamuya açık yayımlanmadığı için rakam vermiyoruz; ilinizin sayfasından güncel ücreti kurumlardan ücretsiz isteyebilirsiniz.</p>
    </div>
  @endif
  @if($meb)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
      <div class="text-xs text-gray-500">Devlet desteği – MEB özel eğitim ({{ $resmiYil }})</div>
      <div class="text-2xl font-black mt-1">{{ $tl($meb['bireysel']) }} <span class="text-sm font-semibold text-gray-500">/ ay bireysel eğitim, KDV hariç</span></div>
      <p class="text-sm text-gray-600 mt-2">Özel eğitim raporu olan bireyler için MEB, izinli merkezlere ayda {{ $meb['bireysel_saat'] }} saat bireysel eğitimde {{ $tl($meb['bireysel']) }}, {{ $meb['grup_saat'] }} saat grup eğitiminde {{ $tl($meb['grup']) }} öder. Kaynak: {{ $meb['kaynak'] }}.</p>
    </div>
  @endif
  @if($piyasaGenel)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
      <div class="text-xs text-gray-500">Türkiye geneli · özel anaokulu ve kreş aylık ücretleri ({{ \Illuminate\Support\Carbon::parse((config('piyasa_ucretleri.tarih') ?: now()->format('Y-m')).'-01')->locale('tr')->translatedFormat('F Y') }})</div>
      <div class="text-2xl font-black mt-1">Ortalama {{ $tl($piyasaGenel['ort']) }} <span class="text-sm font-semibold text-gray-500">/ ay</span></div>
      <div class="text-sm text-gray-700 mt-1">Okulların çoğu <b>{{ $tl($piyasaGenel['alt']) }} – {{ $tl($piyasaGenel['ust']) }}</b> arasında ücret alıyor.</div>
      <p class="text-sm text-gray-600 mt-2">{{ number_format($piyasaGenel['n'], 0, ',', '.') }} özel okulun çevrimiçi ilan ettiği aylık ücretlerden derlenmiştir. İl ve ilçeye göre fark büyüktür; aşağıdan ilinizi seçin.</p>
    </div>
  @endif
  @if(! $piyasaGenel && $fiyat['n'] > 0)
    <div class="grid sm:grid-cols-3 gap-4 mb-8">
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">Ortalama başlangıç ücreti ({{ number_format($fiyat['n'], 0, ',', '.') }} kurumun girdiği)</div>
        <div class="text-2xl font-black mt-1">{{ $tl($fiyat['ort']) }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En düşük</div>
        <div class="text-2xl font-black mt-1">{{ $tl($fiyat['en_az']) }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En yüksek</div>
        <div class="text-2xl font-black mt-1">{{ $fiyat['en_cok'] ? $tl($fiyat['en_cok']) : '—' }}</div>
      </div>
    </div>
  @endif

  <h2 class="font-black text-xl mb-4">İllere Göre {{ $konu }} Fiyatları</h2>
  <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));" class="mb-10">
    @foreach($iller as $il)
      <a href="{{ $ilUrl($il['slug']) }}" class="bg-white rounded-xl shadow-sm border border-gray-100" style="display:block;padding:12px 14px;">
        <div class="font-black text-gray-950">{{ $il['ad'] }} {{ $konu }} Fiyatları</div>
        <div class="text-sm text-gray-600" style="margin-top:2px;">
          {{ $il['kurum'] }} kurum
          @if($piyasaGoster && ($pi = config('piyasa_ucretleri.iller.'.$il['slug']))) · ort. {{ $tl($pi['ort']) }}/ay @endif
          @if(! $piyasaGoster && $il['ort_fiyat']) · ort. {{ $tl($il['ort_fiyat']) }} <span class="text-gray-500">({{ $il['fiyatli'] }} kurumun girdiği)</span>@endif
          @if($il['puan']) · ★ {{ number_format($il['puan'], 1, ',', '.') }}@endif
        </div>
      </a>
    @endforeach
  </div>

  @if($belirleyenler)
    <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mt-8">
      <h2 class="text-lg font-black text-gray-950 mb-1">{{ $konu }} Fiyatını Neler Belirler?</h2>
      <p class="text-sm text-gray-500 mb-4">Kurumlardan fiyat alırken karşılaştırmayı doğru yapmak için bu kalemleri sorun:</p>
      <ul class="space-y-2.5" style="display:grid;gap:10px;">
        @foreach($belirleyenler as $madde)
          <li class="flex items-start gap-2 text-sm text-gray-700"><span class="text-primary font-black mt-0.5">✓</span><span>{{ $madde }}</span></li>
        @endforeach
      </ul>
    </div>
  @endif

  @php $yazilar = \App\Models\ContentPage::kapsamIcinYazilar($category->brand_scope ?? ($section['scopes'][0] ?? null), crc32($section['slug']), 3); @endphp
  @if($yazilar->isNotEmpty())
    <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mt-8">
      <h2 class="text-lg font-black text-gray-950 mb-3">İlgili rehber yazıları</h2>
      <ul class="space-y-2.5" style="display:grid;gap:10px;">
        @foreach($yazilar as $y)
          <li class="text-sm"><a href="{{ $y['url'] }}" class="text-primary font-bold hover:underline">{{ $y['baslik'] }}</a></li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm mt-8">
    <h2 class="text-lg font-black text-gray-950 mb-4">Sıkça sorulan sorular</h2>
    <div class="space-y-4">
      @foreach($sss as [$soru, $cevap])
        <div>
          <div class="font-bold text-gray-900">{{ $soru }}</div>
          <p class="text-gray-600 text-sm leading-relaxed mt-1">{{ $cevap }}</p>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
