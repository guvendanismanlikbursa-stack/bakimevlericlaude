@extends('layouts.brand')
@section('title', $facility->name.' - '.($facility->city->name ?? '').' '.($facility->district ?? ''))
@section('og_title', $facility->name.' - '.($facility->city->name ?? '').' '.($facility->district ?? ''))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($facility->description), 100).' '.facility_brand_framing($facility, current_brand())['meta_suffix'])
@section('og_image', facility_card_image($facility))
@section('breadcrumb_jsonld')
  @include('themes._shared.partials.breadcrumb-jsonld', ['items' => [
      ['name' => current_brand()['name'], 'url' => brand_route('home')],
      ['name' => $facility->city->name ?? '', 'url' => brand_route('facilities.index', ['city' => $facility->city->slug ?? null])],
      ['name' => $facility->name, 'url' => brand_route('facilities.show', ['slug' => $facility->slug])],
  ]])
@endsection

@section('content')
@php
  $brand = current_brand();
  $section = $serviceSection;
  $colors = $section['theme'] ?? ['primary' => $brand['primary_color'], 'soft' => '#f8fafc'];
  $sectionSlug = $section['slug'] ?? null;
  $sectionActions = [
    'yasli-bakim' => ['Ziyaret planı çıkar', 'Fiyat ve bakım kapsamını karşılaştır', 'Aile için soru listesi oluştur'],
    'cocuk' => ['Eğitim programını incele', 'Servis ve yemek düzenini sor', 'Gelişim takibi detayını öğren'],
    'rehabilitasyon' => ['Terapi planını sor', 'Uzman kadro ve ekipmanı karşılaştır', 'Ev programı takibini öğren'],
  ][$sectionSlug] ?? ['Detayları incele', 'Teklif iste', 'Karşılaştır'];
  $sectionChecklist = [
    'yasli-bakim' => ['Günlük sağlık takibi yazılı mı?', 'Acil durumda aileye haber süreci net mi?', 'Ziyaret saatleri ve görüntülü görüşme imkanı var mı?', 'Ek bakım ücretleri açıkça belirtilmiş mi?', 'Beslenme ve ilaç takibi kim tarafından yapılıyor?'],
    'cocuk' => ['Yaş grubu ve sınıf mevcudu uygun mu?', 'Servis, yemek ve güvenlik süreçleri yazılı mı?', 'Gelişim takibi aileyle düzenli paylaşılıyor mu?', 'Rehberlik/psikolog desteği var mı?', 'Özel ihtiyaçlarda bireysel plan hazırlanıyor mu?'],
    'rehabilitasyon' => ['İlk değerlendirme uzman tarafından mı yapılıyor?', 'Seans hedefleri ve süreleri yazılı mı?', 'Cihaz ve terapi alanları ihtiyaca uygun mu?', 'Ev programı ve ara takip veriliyor mu?', 'İlerleme raporu aile/kullanıcı ile paylaşılıyor mu?'],
  ][$sectionSlug] ?? [];
@endphp
<div class="max-w-6xl mx-auto px-4 py-10 grid md:grid-cols-3 gap-8">
  <div class="md:col-span-2">
    <div class="flex items-center gap-2 mb-2 flex-wrap">
      @if($facility->is_claimed)
        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Onaylı / sahiplenilmiş kurum</span>
      @else
        <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-1 rounded-full">Ön kayıtlı profil</span>
      @endif
      @if($section)<span class="text-xs font-semibold px-2 py-1 rounded-full" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">{{ $section['title'] }}</span>@endif
      @include('themes._shared.partials.price-tier-badge', ['facility' => $facility])
    </div>
    <h1 class="text-3xl font-black text-gray-950">{{ $facility->name }}</h1>
    <p class="text-gray-500 mt-1">{{ $facility->city->name }} · {{ $facility->district }} · {{ $facility->category->name }}</p>
    <p class="text-sm text-gray-600 mt-3 italic">{{ facility_brand_framing($facility, $brand)['intro'] }}</p>

    <div class="mt-5 grid sm:grid-cols-3 gap-3">
      @if($facility->is_claimed)
        <button type="button" class="js-engagement-toggle rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700" data-mode="favorites" data-id="{{ $facility->id }}" data-slug="{{ $facility->slug }}">Favori</button>
        <button type="button" class="js-engagement-toggle rounded-xl px-4 py-3 text-sm font-black text-white" style="background: {{ $colors['primary'] }};" data-mode="compare" data-id="{{ $facility->id }}">Karşılaştır</button>
      @endif
      <a href="{{ brand_route('engagement.wizard', $sectionSlug ? ['bolum' => $sectionSlug] : []) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-center text-gray-700">Sihirbaza git</a>
    </div>


    @php
      $brandTheme = current_brand()['theme'];
      $quality = $facility->profileQuality();
      $scoreBase = $quality['score'];
      $scoreLabel = $brandTheme === 'bakimeviara' ? 'Aile uygunluk skoru' : ($brandTheme === 'bakimevleri' ? 'Veri kalite skoru' : 'Hızlı skor');
    @endphp
    <div class="mt-6 grid sm:grid-cols-3 gap-3">
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-black text-gray-500">{{ $scoreLabel }}</div>
        <div class="text-3xl font-black mt-1" style="color: {{ $colors['primary'] }};">{{ round($scoreBase) }}/100</div>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        @if($facility->approved_reviews_avg_rating)
          <div class="text-sm font-black text-gray-500">Yorum puanı</div>
          <div class="text-3xl font-black text-amber-500 mt-1">★ {{ number_format($facility->approved_reviews_avg_rating, 1) }}</div>
        @elseif($facility->rating > 0)
          <div class="text-sm font-black text-gray-500">Google puanı</div>
          <div class="text-3xl font-black text-amber-500 mt-1">★ {{ number_format($facility->rating, 1) }}</div>
        @else
          <div class="text-sm font-black text-gray-500">Yorum puanı</div>
          <div class="text-lg font-black text-gray-400 mt-2">Henüz puan yok</div>
        @endif
      </div>
      <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-black text-gray-500">Güven sinyali</div>
        <div class="text-lg font-black text-gray-950 mt-2">{{ $facility->is_claimed ? 'Onaylı kurum' : 'Profil doğrulama bekliyor' }}</div>
      </div>
    </div>
    @php
      $galleryImages = $facility->images->take(10);
      $galleryCount = $galleryImages->count();
    @endphp
    <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-4">
        <div>
          <div class="text-sm font-black" style="color: {{ $colors['primary'] }};">Kurum görselleri</div>
          <h2 class="text-xl font-black text-gray-950">Fotoğraf galerisi</h2>
        </div>
        <div class="text-xs font-semibold text-gray-500">{{ $galleryCount }}/10 görsel</div>
      </div>

      @if($galleryImages->isNotEmpty())
        <div class="grid lg:grid-cols-[1.5fr_1fr] gap-3">
          <img src="{{ asset('storage/'.$galleryImages->first()->path) }}" class="h-72 w-full rounded-xl object-cover border border-gray-100" alt="{{ $facility->name }} ana görseli">
          <div class="grid grid-cols-2 gap-3">
            @foreach($galleryImages->skip(1)->take(4) as $img)
              <img src="{{ asset('storage/'.$img->path) }}" class="h-[132px] w-full rounded-xl object-cover border border-gray-100" alt="{{ $facility->name }} görseli">
            @endforeach
            @for($i = max(1, $galleryCount); $i < 5; $i++)
              <div class="h-[132px] rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-xs text-gray-400 text-center px-3">Ek görsel alanı</div>
            @endfor
          </div>
        </div>
      @else
        <div class="h-72 rounded-xl border border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center text-center px-6">
          <div class="text-lg font-black text-gray-700">Bu kurum henüz galeri görseli eklememiş</div>
          <p class="text-sm text-gray-500 mt-2 max-w-md">Kurum yetkilisi panelden en fazla 10 gerçek kurum görseli yükleyebilir. Görseller eklendiğinde ziyaretçiler odaları, ortak alanları ve hizmet ortamını burada inceleyebilir.</p>
        </div>
      @endif

      <div class="grid grid-cols-5 sm:grid-cols-10 gap-2 mt-3">
        @foreach($galleryImages as $img)
          <img src="{{ asset('storage/'.$img->path) }}" class="h-16 w-full rounded-lg object-cover border border-gray-100" alt="{{ $facility->name }} küçük görsel">
        @endforeach
        @for($i = $galleryCount; $i < 10; $i++)
          <div class="h-16 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-[11px] text-gray-400">{{ $i + 1 }}</div>
        @endfor
      </div>
    </section>

    <div class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="text-sm font-black mb-3" style="color: {{ $colors['primary'] }};">Bu bölümde sorulacak aksiyonlar</div>
      <div class="grid sm:grid-cols-3 gap-3">
        @foreach($sectionActions as $action)
          <div class="rounded-lg px-3 py-3 text-sm font-semibold" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">{{ $action }}</div>
        @endforeach
      </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between gap-4 mb-4">
        <div>
          <div class="text-sm font-black" style="color: {{ $colors['primary'] }};">Detaylı kontrol listesi</div>
          <h2 class="text-xl font-black text-gray-950">Görüşmeden önce işaretleyin</h2>
        </div>
        <a href="{{ brand_route('engagement.compare') }}" class="text-sm font-black text-primary">Karşılaştırmaya git →</a>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($sectionChecklist as $item)
          <label class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-700">
            <input type="checkbox" class="mt-1 rounded border-gray-300">
            <span>{{ $item }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <div class="flex gap-2 flex-wrap mt-5">
      @foreach($facility->services ?? [] as $service)
        <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">{{ $service }}</span>
      @endforeach
    </div>

    <p class="mt-6 text-gray-700 leading-relaxed">{{ $facility->description }}</p>

    <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="text-gray-500">Kapasite</div>
        <div class="font-black text-gray-950">{{ $facility->capacity ?? '-' }} kişi</div>
      </div>
      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="text-gray-500">Fiyat Aralığı</div>
        <div class="font-black text-gray-950">
          @if($facility->price_min)
            {{ number_format($facility->price_min,0,',','.') }} TL - {{ number_format($facility->price_max,0,',','.') }} TL
          @else
            Bilgi için iletişime geçin
          @endif
        </div>
      </div>
    </div>

    @php $perf = $facility->performanceSummary(); @endphp
    <div class="mt-6 bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
      <h3 class="font-black text-gray-950 mb-3">Kurum Performansı</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div><div class="text-gray-500 text-xs">İncelenme</div><div class="font-black">{{ number_format($perf['views_count']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Favoriye Eklenme</div><div class="font-black">{{ number_format($perf['favorites_count']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Alınan Teklif</div><div class="font-black">{{ number_format($perf['offers_count']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Son Güncelleme</div><div class="font-black">{{ $perf['last_updated_at']->diffForHumans() }}</div></div>
      </div>
      <div class="mt-3 flex gap-2 flex-wrap">
        @if($perf['is_claimed'])
          <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Yetkilisi tarafından doğrulandı{{ $perf['claimed_at'] ? ' · '.$perf['claimed_at']->format('d.m.Y') : '' }}</span>
        @else
          <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Henüz doğrulanmadı (ön kayıtlı profil)</span>
        @endif
      </div>
    </div>

    @php
      $canReview = $facility->is_claimed
        && session('family_user_id')
        && \App\Models\OfferRequest::where('family_user_id', session('family_user_id'))->where('facility_id', $facility->id)->exists();
    @endphp
    <section class="mt-10 grid lg:grid-cols-[1fr_360px] gap-6">
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
        <h2 class="text-xl font-black text-gray-950 mb-4">Kurum yorumları</h2>
        <div class="space-y-3">
          @forelse(($facility->approvedReviews ?? collect()) as $review)
            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
              <div class="flex items-center justify-between"><div class="font-black text-gray-950">{{ $review->reviewer_name }}</div><div class="text-amber-500 font-black">★ {{ $review->rating }}</div></div>
              <p class="text-sm text-gray-600 mt-2">{{ $review->body }}</p>
            </div>
          @empty
            <div class="rounded-lg border border-dashed border-gray-200 p-5 text-sm text-gray-500">Henüz onaylı yorum yok.</div>
          @endforelse
        </div>
      </div>
      <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm h-fit">
        <h3 class="font-black text-gray-950 mb-3">Yorum bırak</h3>
        @if($canReview)
          <form method="POST" action="{{ brand_route('reviews.store', ['slug' => $facility->slug]) }}" class="space-y-3">
            @csrf
            <select name="rating" required class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Puan seçin</option><option value="5">5 - Çok iyi</option><option value="4">4 - İyi</option><option value="3">3 - Orta</option><option value="2">2 - Zayıf</option><option value="1">1 - Kötü</option></select>
            <textarea name="body" placeholder="Deneyiminizi veya görüşme notunuzu yazın" rows="4" class="border rounded-lg px-3 py-2 w-full"></textarea>
            <button class="btn-primary w-full rounded-lg py-2 font-black">Yorumu Gönder</button>
            <p class="text-xs text-gray-400">Yorumlar admin onayından sonra yayınlanır.</p>
          </form>
        @elseif(! $facility->is_claimed)
          <p class="text-sm text-gray-500">Bu kurum henüz sahiplenilmedi. Yorum yapabilmek için kurumun onaylanmış olması gerekir.</p>
        @else
          <p class="text-sm text-gray-500">Yorum yapabilmek için önce bu kurumdan <a href="#teklif-talebi" class="font-black text-primary underline">ücret/teklif bilgisi</a> istemelisiniz.</p>
        @endif
      </div>
    </section>

    <section class="mt-10 bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
      <h2 class="text-xl font-black text-gray-950 mb-4">Aile Soruları</h2>
      <div class="space-y-3 mb-5">
        @forelse($facility->answeredQuestions as $question)
          <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
            <div class="text-sm font-black text-gray-900 mb-1">S: {{ $question->question }}</div>
            <div class="text-sm text-gray-600">C: {{ $question->answer }}</div>
          </div>
        @empty
          <div class="rounded-lg border border-dashed border-gray-200 p-5 text-sm text-gray-500">Henüz yanıtlanmış soru yok.@if($facility->is_claimed) İlk soruyu siz sorabilirsiniz; kurum yetkilisi cevapladığında burada görünür.@endif</div>
        @endforelse
      </div>
      @if($facility->is_claimed)
        <form method="POST" action="{{ brand_route('questions.store', ['slug' => $facility->slug]) }}" class="flex flex-col sm:flex-row gap-2">
          @csrf
          <input type="text" name="asker_name" placeholder="Adınız (opsiyonel)" class="border rounded-lg px-3 py-2 text-sm sm:w-48">
          <input type="text" name="question" placeholder="Örn: Alzheimer hastası kabul ediyor musunuz?" required class="border rounded-lg px-3 py-2 text-sm flex-1">
          <button class="btn-primary rounded-lg px-5 py-2 text-sm font-black whitespace-nowrap">Soru Sor</button>
        </form>
      @else
        <p class="text-sm text-gray-500">Bu kurum henüz sahiplenilmedi; soru sorabilmek için kurumun onaylanmış olması gerekir.</p>
      @endif
    </section>

    @if($related->isNotEmpty())
      <h2 class="font-black text-xl mt-10 mb-4">Benzer Kurumlar</h2>
      <div class="grid md:grid-cols-3 gap-4">
        @foreach($related as $r)
          <a href="{{ brand_route('facilities.show', ['slug' => $r->slug]) }}" class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md border border-gray-100">
            <div class="font-black text-gray-950">{{ $r->name }}</div>
            <div class="text-xs text-gray-500">{{ $r->city->name }}</div>
          </a>
        @endforeach
      </div>
    @endif
  </div>

  <div>
    <div id="teklif-talebi" class="bg-white p-6 rounded-xl shadow-sm sticky top-24 border border-gray-100">
      @if($facility->is_claimed)
        <h3 class="font-black mb-4 text-gray-950">Ücret / Teklif Bilgisi Al</h3>
        <form method="POST" action="{{ brand_route('offer-requests.store') }}" class="space-y-3">
          @csrf
          <input type="hidden" name="facility_id" value="{{ $facility->id }}">
          <select name="care_for" class="border rounded-lg px-3 py-2 w-full bg-white">
            <option value="">Kimin için? (opsiyonel)</option>
            <option value="kendisi">Kendim için</option>
            <option value="anne-baba">Anne/Babam için</option>
            <option value="cocuk">Çocuğum için</option>
            <option value="yakin">Yakınım için</option>
          </select>
          <input type="text" name="patient_name" placeholder="Hasta/çocuk adı (opsiyonel)" class="border rounded-lg px-3 py-2 w-full">
          <input type="text" name="full_name" placeholder="Adınız Soyadınız" required class="border rounded-lg px-3 py-2 w-full">
          <input type="text" name="phone" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
          <input type="email" name="email" placeholder="E-posta" class="border rounded-lg px-3 py-2 w-full">
          <textarea name="message" placeholder="Mesajınız / ihtiyaç detayı" rows="3" class="border rounded-lg px-3 py-2 w-full"></textarea>
          <button class="btn-primary w-full py-2 rounded-lg font-black">Ücret Bilgisi İste</button>
          <p class="text-xs text-gray-400">Devam ederseniz, ücret bilgisi alabilmek için ücretsiz bir aile hesabı oluşturmanız istenecektir.</p>
        </form>
        @if($facility->phone)<div class="mt-4 text-sm text-gray-600">Telefon: {{ $facility->phone }}</div>@endif


        <div class="mt-6 pt-6 border-t">
          <h3 class="font-black mb-3 text-gray-950">Ziyaret / randevu talebi</h3>
          <form method="POST" action="{{ brand_route('visit-requests.store', ['slug' => $facility->slug]) }}" class="space-y-3">
            @csrf
            <input type="text" name="full_name" placeholder="Adınız Soyadınız" required class="border rounded-lg px-3 py-2 w-full">
            <input type="text" name="phone" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
            <input type="email" name="email" placeholder="E-posta" class="border rounded-lg px-3 py-2 w-full">
            <div class="grid grid-cols-2 gap-2">
              <select name="preferred_day" class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Gün</option><option>Hafta içi</option><option>Hafta sonu</option><option>Fark etmez</option></select>
              <select name="preferred_time" class="border rounded-lg px-3 py-2 w-full bg-white"><option value="">Saat</option><option>Sabah</option><option>Öğlen</option><option>Akşamüstü</option></select>
            </div>
            <textarea name="message" placeholder="Ziyaret notu" rows="2" class="border rounded-lg px-3 py-2 w-full"></textarea>
            <button class="w-full rounded-lg border border-primary text-primary font-black py-2">Ziyaret Talebi Gönder</button>
          </form>
        </div>

        <div class="mt-6 pt-6 border-t">
          <h3 class="font-black mb-1 text-gray-950">Kontenjan Sor</h3>
          <p class="text-xs text-gray-500 mb-3">Tek tıkla "Boş yer var mı?" sorusu kuruma iletilir.</p>
          <form method="POST" action="{{ brand_route('visit-requests.availability', ['slug' => $facility->slug]) }}" class="flex gap-2">
            @csrf
            <input type="text" name="full_name" placeholder="Adınız" required class="border rounded-lg px-3 py-2 w-1/2 text-sm">
            <input type="text" name="phone" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-1/2 text-sm">
            <button class="whitespace-nowrap rounded-lg bg-gray-900 text-white font-black px-3 text-sm">Sor</button>
          </form>
        </div>
      @else
        <h3 class="font-black mb-2 text-gray-950">Bu kurum henüz sahiplenilmedi</h3>
        <p class="text-sm text-gray-500">Ücret/teklif bilgisi, ziyaret talebi ve kontenjan sorgusu ancak kurum yetkilisi profili sahiplenip onayladıktan sonra kullanılabilir. Bu bilgiler Google Maps verilerinden otomatik toplanmış ön kayıt profilidir.</p>
      @endif

      @unless($facility->is_claimed)
        <div class="mt-6 pt-6 border-t">
          <p class="text-sm text-gray-600 mb-2">Bu kurumun yetkilisi misiniz?</p>
          <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="block text-center border border-primary text-primary font-black py-2 rounded-lg">Kurumu Sahiplen</a>
        </div>
      @endunless
    </div>
  </div>
</div>

<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => $facility->name,
  'description' => $facility->description,
  'telephone' => $facility->phone,
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => $facility->address,
    'addressLocality' => trim(($facility->district ? $facility->district.', ' : '').($facility->city->name ?? '')),
    'addressCountry' => 'TR',
  ],
  'aggregateRating' => [
    '@type' => 'AggregateRating',
    'ratingValue' => (float) ($facility->approved_reviews_avg_rating ?: $facility->rating ?: 0),
    'reviewCount' => max(1, ($facility->approvedReviews ?? collect())->count()),
  ],
  'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

@include('themes._shared.partials.engagement-script')
@endsection
