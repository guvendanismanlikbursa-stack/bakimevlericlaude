@extends('layouts.brand')
@section('title', 'İyilik Hareketi · Kurum Üyelik Paketleri')
@section('meta_description', 'bakimevleri.com kurum üyelik paketleri: Ekonomik, Standart ve Premium. Kurumunuz üç sitede yayında, aile talepleri doğrudan size; üyelik ücretinin bir bölümü kimsesiz yaşlıların bakımına.')
{{-- 18 Ekim 2026: İyilik Hareketi bilgilendirme sayfası (fiyat YOK).
     22 Ekim 2026: kullanıcının talebi - sayfa 3 yıllık pakete göre baştan
     düzenlendi: Ekonomik (Farkındalık Destekçisi), Standart (Kurumsal İyilik
     Ortağı), Premium (Altın Kalpli Kurum). Paket içerikleri admin
     "Paketler" ekranından gelir; fiyatlar yalnızca kurum panelinde ve
     kuruma özel teklif sayfasında görünür. Program yayına alınana kadar
     sayfa yalnızca admin önizlemesinde açılır ve Google'a kapalıdır. --}}
@section('robots_meta', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('content')
@php
  $tierPackages = $packages->sortBy('sort_order')->values();
  $doctorText = $doctorCount >= 1000
      ? number_format(floor($doctorCount / 100) * 100, 0, ',', '.').'\'ü aşkın'
      : number_format($doctorCount, 0, ',', '.');
  // "Ekonomik Üyelik" -> "Ekonomik" (cümle içinde kısa ad kullanılır).
  $tierName = fn (int $i) => trim(str_ireplace(['Üyeliği', 'Üyelik'], '', $tierPackages->get($i)?->name ?? ['Ekonomik', 'Standart', 'Premium'][$i]));
@endphp

@if($isPreview)
  <div class="text-center text-sm font-bold px-4 py-2" style="background:#fde047;color:#422006;">Önizleme — bu sayfa henüz yayında değil. Yalnızca admin oturumunda görünür; admin panelinde Paketler &gt; "Yayına al" ile yayınlanır.</div>
@endif

{{-- HERO --}}
<section class="relative overflow-hidden text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 45%,#92400e 100%);">
  <div class="max-w-4xl mx-auto px-4 py-16 text-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-wide mb-6">💛 İyilik Hareketi · Kurum Üyelik Paketleri</div>
    <h1 class="text-3xl md:text-5xl font-black leading-tight mb-6 text-balance">Kurumunuz her gün ailelerin aradığı yerde olsun.</h1>
    <p class="text-lg text-white/90 leading-relaxed max-w-2xl mx-auto">bakimevleri.com üyelik paketleri; kurumunuzu üç sitede birden yayınlar, ailelerin taleplerini doğrudan size ulaştırır ve bölgenizde öne çıkmanızı sağlar.</p>
    <p class="text-white/90 leading-relaxed max-w-2xl mx-auto mt-4"><strong>Ekonomik</strong> ile görünür olun, <strong>Standart</strong> ile kendi internet sitenize kavuşun, <strong>Premium</strong> ile 360° sanal turunuz ve vitrin yerinizle bölgenin lideri olun.</p>
    <div class="mt-8 text-sm font-black" style="letter-spacing:.2em;">EKONOMİK · STANDART · PREMİUM</div>
  </div>
</section>

{{-- ÜYELİK KARTLARI --}}
@if($tierPackages->isNotEmpty())
  <section class="bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 py-12">
      @if($launchNotice = membership_launch_notice())
        <div class="rounded-xl px-4 py-3 mb-6 text-center font-black" style="background:#fef3c7;color:#92400e;">🎉 {{ $launchNotice }}</div>
      @endif
      @include('themes._shared.membership._tier-cards', ['packages' => $tierPackages, 'showPrice' => false])
      <div class="rounded-2xl p-5 mt-6 text-sm text-gray-700" style="background:#ecfeff;">
        <strong class="text-gray-900">Tüm paketlerde ortak:</strong> 3 sitede yayın (bakimevleri.com, bakimevibul.com, bakimeviara.com), tek kurum paneli, doğrulanmış üye rozeti, sınırsız ücretsiz teklif ve bölgenizde kurumunuz dışındaki taleplerin size yönlendirilmesi. Bütün üyelikler yıllıktır.
      </div>
      <p class="text-sm text-gray-500 text-center mt-4">Üyelik ücretleri kurumlara özel olarak iletilir. Kurum panelinizden veya bizimle iletişime geçerek öğrenebilirsiniz.</p>
    </div>
  </section>
@endif

{{-- BİZ KİMİZ --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-12 grid md:grid-cols-5 gap-8 items-center">
    <div class="{{ $impact['office_image_url'] ? 'md:col-span-3' : '' }} space-y-4 text-gray-700 leading-relaxed" style="{{ $impact['office_image_url'] ? '' : 'grid-column:1/-1;' }}">
      <h2 class="text-2xl md:text-3xl font-black text-gray-900">Biz Kimiz?</h2>
      <p>Biz <strong class="text-gray-900">Bursa Şehir Hastanesi</strong> içerisinde özel hasta bakım hizmeti veren <strong class="text-gray-900">Güven Bakım Hizmetleri</strong>'yiz. Ailelerin aradığı hizmeti tek çatı altında toplayan ve aileleri gereksiz karmaşadan kurtaran bir platform kurduk. Bu sayede aileler aradığı hizmete kolaylıkla kavuşurken, iş yeri sahipleri de kurumlarının en iyi şekilde tanıtımını yapabiliyor.</p>
      <p>Toplanan üyelik ücretlerinin bir bölümünü, pilot hastane olarak başladığımız Bursa Şehir Hastanesi'nde kimsesi olmayan, ihtiyaç sahibi hastalarımızın öz bakımına ayırıyoruz. Böylece aileler aradığı kurumu kolaylıkla bulurken, üye olan kurum kendi tanıtımını yapmanın yanında bir hastanın da duasını almış oluyor.</p>
      <p class="text-xl font-black" style="color:#a21caf;">Unutmayın, dua bereketi artırır, türlü musibetlerden korur.</p>
      <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-sm">
        <strong class="text-gray-900">Bursa dışındaki kurumlarımız için:</strong> Üyelik gelirinin tanıtıma ayrılan bölümü, kendi şehrinizdeki ailelerin kurumunuzu bulması için kullanılır. Diğer bölümü, sosyal sorumluluk projemizin ilk ayağı olan Bursa Şehir Hastanesi'ndeki kimsesiz yaşlılarımızın bakımına gider.
      </div>
    </div>
    @if($impact['office_image_url'])
      <div class="md:col-span-2">
        <img src="{{ $impact['office_image_url'] }}" alt="Güven Bakım Hizmetleri, Bursa Şehir Hastanesi ofisi" class="w-full rounded-2xl shadow-lg object-cover" loading="lazy">
        <p class="text-xs text-gray-500 mt-2 text-center">Güven Bakım Hizmetleri — Bursa Şehir Hastanesi</p>
      </div>
    @endif
  </div>
</section>

{{-- İYİLİK ROZETİ + ŞEFFAFLIK SAYACI --}}
<section style="background:#fdf4ff;">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-4">İyilik Rozeti</h2>
    <p class="text-gray-700 leading-relaxed">Üyelik ücretinizin bir bölümü, kimsesi olmayan ve bakım ücretini ödeyemeyen ihtiyaç sahibi yaşlıların bakımı için harcanır. Kurumunuzun profilinde, arama sonuçlarındaki kartında ve internet sitesinde üyeliğinize göre iyilik rozetiniz yer alır. Aileler, üyeliğinizle bir yaşlının bakımına destek olduğunuzu görür. Üyelik ücretleriyle bakımı yapılan hastalarımızın sayısı ise her ay bu sayfada yayınlanır.</p>
    @if($tierPackages->isNotEmpty())
      <div class="flex flex-wrap gap-3 mt-5">
        @foreach($tierPackages as $package)
          <span class="bg-white font-black text-sm" style="border-radius:999px;padding:8px 14px;color:#701a75;box-shadow:0 1px 2px rgba(0,0,0,.06);">{{ $package->badge_emoji }} {{ $package->badge_label }} <span class="font-semibold text-gray-500">· {{ $package->name }}</span></span>
        @endforeach
      </div>
    @endif
    @if($impact['patients'])
      <div class="bg-white rounded-2xl shadow-sm p-6 mt-6 text-center max-w-sm">
        <div class="text-xs font-black uppercase tracking-wide mb-2" style="color:#a21caf;">Şeffaflık Paneli · {{ $impact['period'] }}</div>
        <div class="text-4xl font-black" style="color:#701a75;">{{ number_format($impact['patients'], 0, ',', '.') }}</div>
        <div class="text-sm text-gray-600 mt-1">üyelerimiz sayesinde bakımı üstlenilen hasta</div>
      </div>
    @endif
  </div>
</section>

{{-- PAKETLER BİR BAKIŞTA --}}
@if($tierPackages->count() === 3)
  <section class="bg-white">
    <div class="max-w-5xl mx-auto px-4 py-12">
      <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-2">Paketler bir bakışta</h2>
      <p class="text-gray-600 mb-6">Üç paketin özelliklerinin karşılaştırması. Her paketin ayrıntılı açıklaması aşağıdadır.</p>
      @include('themes._shared.membership._compare-table', ['packages' => $tierPackages, 'showPrice' => false])
    </div>
  </section>
@endif

{{-- PAKET AYRINTILARI --}}
@if($tierPackages->isNotEmpty())
  <section class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-12 space-y-6">
      <h2 class="text-2xl md:text-3xl font-black text-gray-900">Paketlerin ayrıntıları</h2>
      @foreach($tierPackages as $package)
        @include('themes._shared.membership._package-detail', ['package' => $package, 'showPrice' => false])
      @endforeach
    </div>
  </section>
@endif

{{-- YÖNLENDİRME NASIL ÇALIŞIR --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-4">Bölgenizdeki taleplerin yönlendirilmesi nasıl çalışır?</h2>
    <p class="text-gray-700 leading-relaxed">Aileler bazen belirli bir kurum seçmeden ilçede bakım arar, bazen de talep gönderdikleri kurum zamanında yanıt vermez. Bu talepler boşa gitmez:</p>
    <ul class="space-y-2 mt-4 text-gray-700">
      <li><strong class="text-gray-900">{{ $tierName(0) }} üyelik:</strong> Bölgenizdeki uygun talepler size bildirilir; teklif vererek aileye ulaşırsınız.</li>
      <li><strong class="text-gray-900">{{ $tierName(1) }} üyelik:</strong> Aynı talepler size {{ $tierName(0) }} üyelerinden önce, öncelikli olarak iletilir.</li>
      <li><strong class="text-gray-900">{{ $tierName(2) }} üyelik:</strong> Talepler ilk olarak size iletilir; aileye ilk ulaşan kurum siz olursunuz.</li>
    </ul>
    <p class="text-sm text-gray-600 mt-4">Kurum panelinizdeki Fırsat Havuzu'nda, yapılan anket çalışmalarında ve platforma gelen, bölgenizde bakım arayan ailelerin taleplerini görürsünüz. Yönlendirme yalnızca kurumunuzun hizmet verdiği türdeki (yaşlı bakım, çocuk, rehabilitasyon) ve bölgenizdeki talepler için yapılır. Ailenin bilgileri yalnızca teklif verme ve iletişim amacıyla paylaşılır.</p>
  </div>
</section>

{{-- DOKTORLAR BÖLÜMÜ --}}
<section class="bg-gray-50">
  <div class="max-w-5xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-2">Doktorlarımız, size aile getirir</h2>
    <p class="text-gray-600 mb-6">{{ $brand['name'] }}'da Türkiye genelinde {{ $doctorText }} doktor ve uzman yer alıyor. Doktor arayan aileler, bölgelerindeki kurumları da burada görür.</p>
    <div class="grid md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-black uppercase" style="color:#a21caf;">Yaşlı bakım kurumları için</div>
        <h3 class="font-black text-gray-900 mt-1 mb-2">Bakım kararı doktor odasında başlar</h3>
        <p class="text-sm text-gray-700">Ailelerin çoğu huzurevi aramaya kendiliğinden başlamaz. Bir nörolog alzheimer ya da inme teşhisi koyduğunda, bir ortopedist kalça kırığı sonrası uzun bakım gerektiğini söylediğinde ya da bir geriatri uzmanı evde bakımın artık yetmediğini anlattığında, aile o gün bakım kurumu aramaya başlar.</p>
        <p class="text-sm text-gray-700 mt-2">Annesi ya da babası için nörolog, geriatri, ortopedi veya fizik tedavi uzmanı arayan aileler sitemize gelir, bölgelerindeki bakım kurumlarını da burada görür. Bakım ihtiyacı yeni ortaya çıkan ve henüz hiçbir kurumla görüşmemiş ailelere ilk ulaşan kurum siz olursunuz.</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-black uppercase" style="color:#a21caf;">Çocuk kurumları için</div>
        <h3 class="font-black text-gray-900 mt-1 mb-2">Anne babalar en çok çocuk doktorlarına güvenir</h3>
        <p class="text-sm text-gray-700">Bir çocuğun gelişimiyle ilgili ilk soru genellikle çocuk doktorunun odasında sorulur: konuşması, arkadaşlarıyla ilişkisi, okula hazır olup olmadığı. Çocuk doktoru, çocuk psikiyatristi ya da çocuk diş hekimi arayan anne babalar, aynı zamanda çocuklarına uygun bir kreş ya da anaokulu da arayan ailelerdir.</p>
        <p class="text-sm text-gray-700 mt-2">Çocuğuna doktor arayan bir anne baba, çocuğunun bakımına en çok özen gösteren ailedir. Bu aileler, kurumunuzun en uzun süre kalan ve sizi en çok tavsiye eden aileleri olur.</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-black uppercase" style="color:#a21caf;">Rehabilitasyon ve özel eğitim için</div>
        <h3 class="font-black text-gray-900 mt-1 mb-2">Teşhisi koyan doktor, aileyi size yönlendirir</h3>
        <p class="text-sm text-gray-700">Otizm, serebral palsi, gelişim geriliği ya da inme sonrası felç: Bu teşhislerin hepsini bir doktor koyar, ardından aileye "düzenli terapi ve eğitim gerekiyor" der. Aile o andan itibaren bir rehabilitasyon merkezi aramaya başlar.</p>
        <p class="text-sm text-gray-700 mt-2">Teşhisten hemen sonra kurum arayan aile, karar vermeye en hazır ailedir. Sizinle görüşmeden önce başka bir merkeze bağlanmamış olur.</p>
      </div>
    </div>
  </div>
</section>

{{-- 3 SİTE / FATURA --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-12 space-y-4">
    <div class="rounded-2xl p-5" style="background:#ecfeff;">
      <div class="font-black text-gray-900 mb-1">🌐 3 Sitede Yayın, Tek Kurum Paneli</div>
      <p class="text-sm text-gray-700">Kurumunuz <strong>bakimevleri.com</strong>, <strong>bakimevibul.com</strong> ve <strong>bakimeviara.com</strong> sitelerinde aynı anda yayınlanır. Hangi siteden kayıt olduğunuzun önemi yoktur. Aileler bu üç siteden hangisinden gelirse gelsin; ücret/teklif talebi, mesaj, ziyaret talebi ve soruların tamamı <strong>tek kurum panelinize</strong> düşer, tek hesapla hepsine cevap verirsiniz.</p>
    </div>
    <div class="rounded-2xl p-5 bg-gray-50">
      <div class="font-black text-gray-900 mb-1">🧾 Faturalı, Gider Gösterilebilir Hizmet</div>
      <p class="text-sm text-gray-700">Aldığınız hizmet karşılığında adınıza kesilen fatura "Dijital Listeleme, Reklam ve Aracılık Hizmet Bedeli" olarak düzenlenir. İşletmeniz bu faturayı pazarlama gideri olarak değerlendirebilir; ayrıntılar için mali müşavirinize danışabilirsiniz.</p>
    </div>
  </div>
</section>

{{-- SIK SORULANLAR --}}
<section class="bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-6">Sık sorulanlar</h2>
    <div class="space-y-3">
      @foreach([
        ['Paketimi sonradan yükseltebilir miyim?', 'Evet. '.$tierName(0).' üyelikten '.$tierName(1).' ya da '.$tierName(2).' üyeliğe dilediğiniz zaman geçebilirsiniz; kalan süreniz yeni pakete eklenir.'],
        ['İnternet sitem ne zaman hazır olur?', $tierName(1).' ve '.$tierName(2).' üyelerinde bilgileriniz ve fotoğraflarınız tamamlandıktan sonra siteniz 24 saat içinde yayına alınır.'],
        ['360° sanal tur çekimi nasıl yapılır?', $tierName(2).' üyelerinde randevu oluşturulur, çekim ekibimiz kurumunuza gelir. Çekim yaklaşık 1–2 saat sürer.'],
        ['Üyeliğim bitince ne olur?', 'Kurum profiliniz yayında kalır; üyelik avantajları (ücretsiz teklif, rozet, internet sitesi, sanal tur) süre sonunda durur. Yenilediğinizde kaldığınız yerden devam eder.'],
      ] as [$q, $a])
        <details class="bg-white rounded-xl p-4 shadow-sm">
          <summary class="font-black text-gray-900" style="cursor:pointer;">{{ $q }}</summary>
          <p class="text-sm text-gray-700 mt-2">{{ $a }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- ŞEFFAFLIK SÖZÜ / İLETİŞİM --}}
<section style="background:linear-gradient(135deg,#4a044e 0%,#701a75 100%);" class="text-white">
  <div class="max-w-3xl mx-auto px-4 py-12 text-center">
    <h2 class="text-2xl md:text-3xl font-black mb-4">Şeffaflık Sözümüz</h2>
    <p class="text-white/90 leading-relaxed">Üyelerimizin katkısıyla kaç hastamızın bakımının üstlenildiği bu sayfada her ay paylaşılır. Gelin, kurumunuzu büyütürken bir büyük çınarın duasında buluşalım.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a href="{{ brand_route('facility.login') }}" class="bg-white font-black px-5 py-3 rounded-xl" style="color:#701a75;">Kurum Panelime Giriş Yap</a>
      <a href="{{ brand_route('facility-registration.create') }}" class="border font-bold px-5 py-3 rounded-xl" style="border-color:rgba(255,255,255,.4);">Kurumumu Kaydet</a>
      <a href="{{ brand_route('contact.create') }}" class="border font-bold px-5 py-3 rounded-xl" style="border-color:rgba(255,255,255,.4);">Bize Ulaşın</a>
    </div>
    <p class="text-xs text-white/70 mt-4">Kurumunuz sitemizde listeleniyorsa, kurum sayfanızdaki "Kurum Size mi Ait?" bağlantısıyla sahiplenip üye olabilirsiniz.</p>
  </div>
</section>
@endsection
