@extends('layouts.brand')
@section('title', 'Hakkımızda')
@section('meta_description', $brand['name'].' — yaşlı bakım, çocuk bakım ve rehabilitasyon kurumlarını tek platformda bir araya getiren güvenilir karşılaştırma ve başvuru platformu.')
@section('content')
@php
  $brandName = ucfirst($brand['name']);
  $primary = $brand['primary_color'];
  $secondary = $brand['secondary_color'];
@endphp

{{-- HERO: 3 alanin gercek fotograflarindan olusan bir kolaj arka plan +
     baslik/alt baslik. Kullanicinin talebi - sayfa isletmenin "vitrin"
     alani kapasitesinde olmali, bu yuzden duz metin yerine gercek kurum
     fotograflariyla acan tam genislik bir sahne kullaniliyor. --}}
<section class="relative overflow-hidden bg-gray-950 text-white">
  <div class="absolute inset-0 grid grid-cols-3">
    @foreach($sections as $section)
      <div class="relative">
        <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gray-950/72"></div>
      </div>
    @endforeach
  </div>
  <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/55 to-gray-950/30"></div>

  <div class="relative max-w-4xl mx-auto px-4 py-20 md:py-28 text-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-wide mb-6">Hakkımızda</div>
    <h1 class="text-3xl md:text-5xl font-black leading-tight mb-6 text-balance">Bakım ve destek hizmetlerine ihtiyaç duyan herkes için daha kolay, daha doğru ve daha güvenilir bir yol.</h1>
    <p class="text-lg text-white/85 leading-relaxed max-w-2xl mx-auto">
      <strong class="text-white">{{ $brandName }}</strong>, bireylerin ve ailelerin ihtiyaç duydukları
      <strong class="text-white">yaşlı bakım, çocuk bakım ve rehabilitasyon</strong> hizmetlerine daha kolay ulaşabilmeleri amacıyla geliştirilmiş kapsamlı bir platformdur.
    </p>
  </div>
</section>

{{-- ISTATISTIK SERIDI: gercek, canli platform verisi - "vitrin" sayfasina
     guven veren somut rakamlar. --}}
<section class="bg-white border-b border-gray-100">
  <div class="max-w-4xl mx-auto px-4 py-8 grid grid-cols-3 gap-4 text-center">
    <div>
      <div class="text-2xl md:text-3xl font-black text-gray-950">{{ number_format($facilityCount, 0, ',', '.') }}</div>
      <div class="text-xs md:text-sm text-gray-500 mt-1">Listelenen kurum</div>
    </div>
    <div>
      <div class="text-2xl md:text-3xl font-black text-gray-950">{{ $cityCount }} / {{ $totalCities }}</div>
      <div class="text-xs md:text-sm text-gray-500 mt-1">İl genelinde kapsam</div>
    </div>
    <div>
      <div class="text-2xl md:text-3xl font-black text-gray-950">{{ number_format($claimedCount, 0, ',', '.') }}</div>
      <div class="text-xs md:text-sm text-gray-500 mt-1">Doğrulanmış/sahiplenilmiş kurum</div>
    </div>
  </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-14 md:py-16">

  {{-- GIRIS / MISYON --}}
  <div class="max-w-2xl mx-auto text-center mb-16">
    <p class="text-gray-600 leading-relaxed">
      Bir aile için yaşlı bir yakınının bakımını emanet edeceği doğru kurumu bulmak, çocuğu için uygun bir bakım ve eğitim kurumu seçmek ya da rehabilitasyon ihtiyacına cevap verebilecek doğru merkeze ulaşmak; yalnızca bir kurum aramaktan çok daha fazlasıdır.
    </p>
    <p class="text-gray-600 leading-relaxed mt-4">
      Bu süreçte doğru bilgiye ulaşmak, farklı seçenekleri değerlendirmek ve ihtiyaçlara uygun kurumu bulabilmek büyük önem taşır.
    </p>
  </div>

  {{-- UC ALAN --}}
  <div class="text-center mb-8">
    <div class="text-sm font-black uppercase tracking-wide" style="color: {{ $primary }};">Üç farklı alan, tek platform</div>
    <h2 class="text-2xl md:text-3xl font-black text-gray-950 mt-2">{{ $brandName }}'da neleri bir araya getiriyoruz</h2>
  </div>
  @php
    $areaCopy = [
      'yasli-bakim' => 'Yaşlı bireylerin bakım ve yaşam ihtiyaçlarına yönelik kurum ve hizmetleri',
      'cocuk' => 'Çocukların bakım ve gelişim ihtiyaçlarına yönelik kreş, anaokulu ve ilgili kurumları',
      'rehabilitasyon' => 'Bireylerin fiziksel, zihinsel, gelişimsel ve özel eğitim ihtiyaçlarına yönelik rehabilitasyon merkezlerini',
    ];
  @endphp
  <div class="grid md:grid-cols-3 gap-5 mb-16">
    @foreach($sections as $slug => $section)
      <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden hover:shadow-lg transition">
        <div class="h-36 overflow-hidden">
          <img src="{{ $section['hero_image'] }}" alt="{{ $section['title'] }}" class="w-full h-full object-cover">
        </div>
        <div class="p-5">
          <div class="inline-flex items-center gap-2 mb-2">
            <span class="inline-flex rounded-lg p-1.5" style="background: {{ $section['theme']['soft'] }}; color: {{ $section['theme']['primary'] }};">
              @include('themes._shared.partials.section-icon', ['section' => $section, 'class' => 'w-4 h-4'])
            </span>
            <span class="font-black text-gray-950">{{ $section['title'] }}</span>
          </div>
          <p class="text-sm text-gray-500 leading-relaxed">{{ $areaCopy[$slug] ?? '' }}</p>
        </div>
      </div>
    @endforeach
  </div>
  <p class="text-center text-gray-600 max-w-2xl mx-auto mb-16">tek bir platformda buluşturuyoruz.</p>

  {{-- KOLAYLASTIRMA --}}
  <div class="grid md:grid-cols-2 gap-8 items-center mb-16">
    <div>
      <div class="text-sm font-black uppercase tracking-wide" style="color: {{ $primary }};">Nasıl çalışıyoruz</div>
      <h2 class="text-2xl md:text-3xl font-black text-gray-950 mt-2 mb-4">Aradığınız kuruma ulaşmanızı kolaylaştırıyoruz</h2>
      <p class="text-gray-600 leading-relaxed">
        Kullanıcılarımız şehir, ilçe ve hizmet alanlarına göre araştırma yapabilir; kurumların sunduğu hizmetleri inceleyebilir ve kendileri için uygun seçenekleri değerlendirebilir.
      </p>
      <p class="text-gray-600 leading-relaxed mt-4">
        İhtiyacınızı belirterek birden fazla kuruma aynı anda başvuru yapabilir, böylece kurumları tek tek aramak ve aynı bilgileri tekrar tekrar paylaşmak yerine seçeneklerinizi daha hızlı değerlendirebilirsiniz.
      </p>
    </div>
    <div class="space-y-3">
      <div class="rounded-xl border border-gray-100 bg-white shadow-sm p-4 flex items-start gap-3">
        <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $primary }}14; color: {{ $primary }};">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 3.5c-4.5 0-7.5 3.5-8.5 6.5 1 3 4 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4-6.5-8.5-6.5Zm0 10.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg>
        </span>
        <div><div class="font-black text-gray-950 text-sm">Şehir, ilçe ve hizmete göre filtreleme</div><p class="text-xs text-gray-500 mt-0.5">İhtiyacınıza en uygun kurumu birkaç tıkla daraltın.</p></div>
      </div>
      <div class="rounded-xl border border-gray-100 bg-white shadow-sm p-4 flex items-start gap-3">
        <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $primary }}14; color: {{ $primary }};">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M4 4h9l3 3v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm6 4v5m-2.5-2.5h5"/></svg>
        </span>
        <div><div class="font-black text-gray-950 text-sm">Tek seferde çoklu başvuru</div><p class="text-xs text-gray-500 mt-0.5">Bilgilerinizi tekrar tekrar paylaşmadan birden çok kuruma ulaşın.</p></div>
      </div>
    </div>
  </div>

  {{-- MISYON PULL-QUOTE --}}
  <div class="rounded-2xl p-8 md:p-10 text-center mb-16" style="background: {{ $primary }}0d;">
    <div class="text-sm font-black uppercase tracking-wide mb-3" style="color: {{ $primary }};">Çünkü her ihtiyaç farklıdır</div>
    <p class="text-gray-600 leading-relaxed max-w-2xl mx-auto">
      Her ailenin beklentisi, bütçesi ve önceliği aynı değildir. Kimi zaman yaşlı bir yakının güvenli ve kaliteli bir bakım ortamına ihtiyacı vardır. Kimi zaman çalışan bir ailenin çocuğu için güvenilir bir bakım ve eğitim kurumuna ulaşması gerekir. Kimi zaman ise özel eğitim veya rehabilitasyon desteğine ihtiyaç duyan bir birey için doğru merkezin bulunması gerekir.
    </p>
    <p class="text-lg md:text-xl font-black text-gray-950 max-w-2xl mx-auto mt-5 leading-snug text-balance">
      {{ $brandName }}'un amacı, tüm bu farklı ihtiyaçlara tek bir kalıpla yaklaşmak yerine, kullanıcıların kendi ihtiyaçlarına uygun seçenekleri daha kolay bulabilmelerini sağlamaktır.
    </p>
  </div>

  {{-- KURUMLAR ICIN --}}
  <div class="rounded-2xl overflow-hidden mb-16 grid md:grid-cols-[1.2fr_1fr]" style="background: {{ $primary }};">
    <div class="p-8 md:p-10 text-white">
      <div class="text-sm font-black uppercase tracking-wide text-white/70 mb-2">Kurumlar için de daha görünür bir platform</div>
      <p class="leading-relaxed text-white/90">
        {{ $brandName }} yalnızca hizmet arayan aileler için değil, <strong class="text-white">yaşlı bakım, çocuk bakım ve rehabilitasyon</strong> alanlarında hizmet veren kurumlar için de kendilerini tanıtabilecekleri ve ihtiyaç sahibi bireylere ulaşabilecekleri bir platform olmayı hedeflemektedir.
      </p>
      <p class="leading-relaxed text-white/90 mt-3">
        Kurumların doğru bilgilerle temsil edilmesi, kullanıcıların daha bilinçli değerlendirme yapabilmesi ve hizmet sunan kurumlarla hizmet arayanların daha kolay buluşabilmesi için platformumuzu sürekli geliştiriyoruz.
      </p>
      <a href="{{ brand_route('facility-registration.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-black mt-6" style="color: {{ $primary }};">
        Kurumunuzu ekleyin →
      </a>
    </div>
    <div class="hidden md:block relative">
      <img src="{{ $sections[array_key_first($sections)]['hero_image'] }}" alt="" class="w-full h-full object-cover opacity-80">
    </div>
  </div>

  {{-- HEDEF --}}
  <div class="text-center">
    <div class="text-sm font-black uppercase tracking-wide mb-3" style="color: {{ $primary }};">Hedefimiz</div>
    <p class="text-gray-600 leading-relaxed max-w-2xl mx-auto">
      Türkiye genelinde <strong class="text-gray-950">yaşlı bakım, çocuk bakım ve rehabilitasyon hizmetleri</strong> konusunda ihtiyaç duyulan bilgiye ulaşılabilecek, seçeneklerin karşılaştırılabileceği ve ailelerin karar verme sürecini kolaylaştıracak güçlü bir platform oluşturmak.
    </p>
    <p class="text-gray-500 leading-relaxed max-w-2xl mx-auto mt-4">Çünkü doğru hizmeti bulmak, yalnızca bir arama sonucuna ulaşmak değildir.</p>
    <p class="text-xl md:text-2xl font-black text-gray-950 max-w-2xl mx-auto mt-4 leading-snug text-balance">
      Doğru bilgiye ulaşmak, seçenekleri değerlendirmek ve sevdikleriniz için içinize sinen kararı verebilmektir.
    </p>

    <div class="mt-10 pt-8 border-t border-gray-100">
      <div class="text-xl font-black text-gray-950">{{ $brandName }}</div>
      <div class="text-sm font-bold mt-1" style="color: {{ $primary }};">
        @foreach($sections as $section){{ $section['title'] }}@if(!$loop->last) &nbsp;•&nbsp; @endif @endforeach
      </div>
      <a href="{{ brand_route('engagement.wizard') }}" class="inline-flex items-center gap-2 rounded-lg text-white px-6 py-3 text-sm font-black mt-6" style="background: {{ $primary }};">
        Karar Sihirbazı'nı deneyin →
      </a>
    </div>
  </div>

</div>
@endsection
