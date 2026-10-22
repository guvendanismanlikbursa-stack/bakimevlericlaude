@extends('layouts.brand')
@section('title', 'İyilik Hareketi · Kurum Üyelik Paketleri')
@section('meta_description', 'bakimevleri.com kurum üyelik paketleri: Ekonomik, Standart ve Premium. Kurumunuz sitelerimizde yayında, aile talepleri doğrudan size; üyelik ücretinin bir bölümü kimsesiz yaşlıların bakımına.')
{{-- 18 Ekim 2026: İyilik Hareketi bilgilendirme sayfası (fiyat YOK).
     22 Ekim 2026: kullanıcının talebi - sayfa 3 yıllık pakete göre baştan
     düzenlendi: Ekonomik (Farkındalık Destekçisi), Standart (Kurumsal İyilik
     Ortağı), Premium (Altın Kalpli Kurum). Aynı gün ikinci talep: "her
     ölçüdeki cihazda en güzel şekilde, özellikle mobilde dengeli ve şık" -
     bütün bölümler aynı genişlik/boşluk düzenine alındı (bkz. _styles).
     Paket içerikleri admin "Paketler" ekranından gelir; fiyatlar yalnızca
     kurum panelinde ve kuruma özel teklif sayfasında görünür. Program yayına
     alınana kadar sayfa yalnızca admin önizlemesinde açılır. --}}
@section('robots_meta', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('content')
@include('themes._shared.membership._styles')
@php
  $tierPackages = $packages->sortBy('sort_order')->values();
  $doctorText = $doctorCount >= 1000
      ? number_format(floor($doctorCount / 100) * 100, 0, ',', '.').'\'ü aşkın'
      : number_format($doctorCount, 0, ',', '.');
  // "Ekonomik Üyelik" -> "Ekonomik" (cümle içinde kısa ad kullanılır).
  $tierName = fn (int $i) => trim(str_ireplace(['Üyeliği', 'Üyelik'], '', $tierPackages->get($i)?->name ?? ['Ekonomik', 'Standart', 'Premium'][$i]));
  $tierEmoji = fn (int $i) => $tierPackages->get($i)?->badge_emoji ?? ['💜', '💚', '💛'][$i];
@endphp

@if($isPreview)
  <div class="text-center text-sm font-bold px-4 py-2" style="background:#fde047;color:#422006;">Önizleme — bu sayfa henüz yayında değil. Yalnızca admin oturumunda görünür; admin panelinde Paketler &gt; "Yayına al" ile yayınlanır.</div>
@endif

{{-- HERO --}}
<section class="text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 48%,#92400e 100%);">
  <div class="ih-wrap ih-center" style="padding-top:clamp(48px,9vw,96px);padding-bottom:clamp(48px,9vw,96px);">
    <div style="display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);border-radius:999px;padding:8px 14px;font-size:12px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;">💛 İyilik Hareketi · Kurum Üyelikleri</div>
    <h1 style="font-size:clamp(30px,6vw,56px);font-weight:900;line-height:1.1;margin:22px auto 18px;max-width:820px;letter-spacing:-.015em;">Kurumunuz her gün ailelerin aradığı yerde olsun.</h1>
    <p style="font-size:clamp(16px,2.2vw,19px);line-height:1.65;color:rgba(255,255,255,.9);max-width:680px;margin:0 auto;">bakimevleri.com üyelik paketleri; kurumunuzu sitelerimizde yayınlar, ailelerin taleplerini doğrudan size ulaştırır ve bölgenizde öne çıkmanızı sağlar.</p>
    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:28px;">
      @foreach($tierPackages as $i => $package)
        <a href="#paketler" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:9px 14px;font-weight:800;font-size:14px;">{{ $package->badge_emoji }} {{ $package->name }}</a>
      @endforeach
    </div>
  </div>
</section>

{{-- BİZ KİMİZ --}}
<section class="bg-white ih-sec">
  <div class="ih-wrap">
    <div class="{{ $impact['office_image_url'] ? 'ih-split' : '' }}">
      <div>
        <span class="ih-eyebrow">Biz Kimiz?</span>
        <h2 class="ih-h2">Aileleri doğru kuruma, kurumları doğru aileye ulaştırıyoruz</h2>
        <div class="ih-lead" style="display:grid;gap:14px;">
          <p>Biz <b style="color:#111827;">Bursa Şehir Hastanesi</b> içerisinde özel hasta bakım hizmeti veren <b style="color:#111827;">Güven Bakım Hizmetleri</b>'yiz. Ailelerin aradığı hizmeti tek çatı altında toplayan ve aileleri gereksiz karmaşadan kurtaran bir platform kurduk. Böylece aileler aradığı hizmete kolayca kavuşurken, iş yeri sahipleri de kurumlarını en iyi şekilde tanıtabiliyor.</p>
          <p>Toplanan üyelik ücretlerinin bir bölümünü, pilot hastane olarak başladığımız Bursa Şehir Hastanesi'nde kimsesi olmayan, ihtiyaç sahibi hastalarımızın öz bakımına ayırıyoruz. Üye olan kurum, kendi tanıtımını yaparken bir hastanın da duasını almış oluyor.</p>
        </div>
        <p style="margin-top:18px;font-size:clamp(18px,2.6vw,21px);font-weight:900;color:#a21caf;line-height:1.4;">Unutmayın, dua bereketi artırır, türlü musibetlerden korur.</p>
      </div>
      @if($impact['office_image_url'])
        <figure style="margin:0;">
          <img src="{{ $impact['office_image_url'] }}" alt="Güven Bakım Hizmetleri, Bursa Şehir Hastanesi ofisi" class="ih-photo" loading="lazy">
          <figcaption style="font-size:12px;color:#6b7280;text-align:center;margin-top:10px;">Güven Bakım Hizmetleri — Bursa Şehir Hastanesi</figcaption>
        </figure>
      @endif
    </div>
    <div class="ih-note" style="margin-top:28px;"><b style="color:#111827;">Bursa dışındaki kurumlarımız için:</b> Üyelik gelirinin tanıtıma ayrılan bölümü, kendi şehrinizdeki ailelerin kurumunuzu bulması için kullanılır. Diğer bölümü, sosyal sorumluluk projemizin ilk ayağı olan Bursa Şehir Hastanesi'ndeki kimsesiz yaşlılarımızın bakımına gider.</div>
  </div>
</section>

{{-- İYİLİK ROZETİ + ŞEFFAFLIK --}}
<section class="ih-sec" style="background:#fdf4ff;">
  <div class="ih-wrap">
    <div class="{{ $impact['patients'] ? 'ih-split' : '' }}">
      <div>
        <span class="ih-eyebrow">İyilik Rozeti</span>
        <h2 class="ih-h2">Üyeliğiniz bir yaşlının bakımına dönüşür</h2>
        <p class="ih-lead">Üyelik ücretinizin bir bölümü, kimsesi olmayan ve bakım ücretini ödeyemeyen ihtiyaç sahibi yaşlıların bakımı için harcanır. Kurumunuzun profilinde, arama sonuçlarındaki kartında ve internet sitesinde üyeliğinize göre iyilik rozetiniz yer alır; aileler bir yaşlının bakımına destek olduğunuzu görür.</p>
        @if($tierPackages->isNotEmpty())
          <div class="ih-badges">
            @foreach($tierPackages as $package)
              <div class="ih-badge"><span style="font-size:22px;color:inherit;">{{ $package->badge_emoji }}</span><div><b>{{ $package->badge_label }}</b><br><span>{{ $package->name }}</span></div></div>
            @endforeach
          </div>
        @endif
      </div>
      @if($impact['patients'])
        <div class="ih-stat">
          <div class="ih-eyebrow">Şeffaflık Paneli · {{ $impact['period'] }}</div>
          <div class="ih-stat-num" style="margin-top:14px;">{{ number_format($impact['patients'], 0, ',', '.') }}</div>
          <div style="margin-top:10px;color:#4b5563;line-height:1.5;">üyelerimiz sayesinde<br>bakımı üstlenilen hasta</div>
          <div style="margin-top:14px;font-size:12px;color:#9ca3af;">Bu sayı her ay güncellenir.</div>
        </div>
      @endif
    </div>
  </div>
</section>

{{-- ÜYELİK KARTLARI --}}
@if($tierPackages->isNotEmpty())
  <section id="paketler" class="bg-gray-50 ih-sec" style="scroll-margin-top:80px;">
    <div class="ih-wrap">
      <div class="ih-center" style="margin-bottom:28px;">
        <span class="ih-eyebrow">Üyelik Paketleri</span>
        <h2 class="ih-h2">Size uygun üyeliği seçin</h2>
        <p class="ih-lead"><b style="color:#111827;">Ekonomik</b> ile görünür olun, <b style="color:#111827;">Standart</b> ile kendi internet sitenize kavuşun, <b style="color:#111827;">Premium</b> ile 360° sanal turunuz ve vitrin yerinizle bölgenin lideri olun. Bütün üyelikler yıllıktır.</p>
      </div>
      @if($launchNotice = membership_launch_notice())
        <div class="ih-center" style="border-radius:14px;padding:12px 16px;margin:0 auto 22px;max-width:720px;background:#fef3c7;color:#92400e;font-weight:900;">🎉 {{ $launchNotice }}</div>
      @endif
      @include('themes._shared.membership._tier-cards', ['packages' => $tierPackages, 'showPrice' => false])
      <div class="ih-note" style="margin-top:22px;background:#ecfeff;border-color:#cffafe;"><b style="color:#111827;">Tüm paketlerde ortak:</b> sitelerimizde yayın, tek kurum paneli, doğrulanmış üye rozeti, sınırsız ücretsiz teklif ve bölgenizde kurumunuz dışındaki taleplerin size yönlendirilmesi. Üyelik ücretleri kurumlara özel iletilir; kurum panelinizden ya da bizimle iletişime geçerek öğrenebilirsiniz.</div>
    </div>
  </section>
@endif

{{-- PAKETLER BİR BAKIŞTA --}}
@if($tierPackages->count() === 3)
  <section class="bg-white ih-sec">
    <div class="ih-wrap">
      <div class="ih-center" style="margin-bottom:28px;">
        <span class="ih-eyebrow">Karşılaştırma</span>
        <h2 class="ih-h2">Paketler bir bakışta</h2>
        <p class="ih-lead">Üç paketin özelliklerinin karşılaştırması. Her paketin ayrıntılı açıklaması aşağıdadır.</p>
      </div>
      @include('themes._shared.membership._compare-table', ['packages' => $tierPackages, 'showPrice' => false])
    </div>
  </section>
@endif

{{-- PAKET AYRINTILARI --}}
@if($tierPackages->isNotEmpty())
  <section class="bg-gray-50 ih-sec">
    <div class="ih-wrap" style="max-width:900px;">
      <div class="ih-center" style="margin-bottom:24px;">
        <span class="ih-eyebrow">Ayrıntılar</span>
        <h2 class="ih-h2">Paketlerin ayrıntıları</h2>
        <p class="ih-lead">Pakete dokunarak içindeki bütün maddeleri görebilirsiniz.</p>
      </div>
      <div style="display:grid;gap:14px;">
        @foreach($tierPackages as $package)
          @include('themes._shared.membership._package-detail', ['package' => $package, 'showPrice' => false])
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- YÖNLENDİRME NASIL ÇALIŞIR --}}
<section class="bg-white ih-sec">
  <div class="ih-wrap">
    <div class="ih-center" style="margin-bottom:28px;">
      <span class="ih-eyebrow">Talep Yönlendirme</span>
      <h2 class="ih-h2">Bölgenizdeki talepler size nasıl ulaşır?</h2>
      <p class="ih-lead">Aileler bazen belirli bir kurum seçmeden ilçede bakım arar, bazen de talep gönderdikleri kurum zamanında yanıt vermez. Bu talepler boşa gitmez.</p>
    </div>
    <div class="ih-grid3">
      <div class="ih-card"><div class="ih-step-head"><span style="font-size:24px;">{{ $tierEmoji(0) }}</span>{{ $tierName(0) }} üyelik</div><p style="margin-top:10px;color:#4b5563;line-height:1.65;">Bölgenizdeki uygun talepler size bildirilir; teklif vererek aileye ulaşırsınız.</p></div>
      <div class="ih-card"><div class="ih-step-head"><span style="font-size:24px;">{{ $tierEmoji(1) }}</span>{{ $tierName(1) }} üyelik</div><p style="margin-top:10px;color:#4b5563;line-height:1.65;">Aynı talepler size {{ $tierName(0) }} üyelerinden önce, öncelikli olarak iletilir.</p></div>
      <div class="ih-card ih-tier is-premium"><div class="ih-step-head"><span style="font-size:24px;">{{ $tierEmoji(2) }}</span>{{ $tierName(2) }} üyelik</div><p style="margin-top:10px;color:#4b5563;line-height:1.65;">Talepler ilk olarak size iletilir; aileye ilk ulaşan kurum siz olursunuz.</p></div>
    </div>
    <p class="ih-lead" style="margin:24px auto 0;font-size:14px;text-align:center;">Kurum panelinizdeki Fırsat Havuzu'nda, yapılan anket çalışmalarında ve platforma gelen, bölgenizde bakım arayan ailelerin taleplerini görürsünüz. Yönlendirme yalnızca kurumunuzun hizmet verdiği türdeki (yaşlı bakım, çocuk, rehabilitasyon) ve bölgenizdeki talepler için yapılır; ailenin bilgileri yalnızca teklif ve iletişim amacıyla paylaşılır.</p>
  </div>
</section>

{{-- DOKTORLAR BÖLÜMÜ --}}
<section class="bg-gray-50 ih-sec">
  <div class="ih-wrap">
    <div class="ih-center" style="margin-bottom:28px;">
      <span class="ih-eyebrow">Doktorlarımız</span>
      <h2 class="ih-h2">Doktorlarımız, size aile getirir</h2>
      <p class="ih-lead">{{ $brand['name'] }}'da Türkiye genelinde {{ $doctorText }} doktor ve uzman yer alıyor. Doktor arayan aileler, bölgelerindeki kurumları da burada görür.</p>
    </div>
    <div class="ih-grid3">
      @foreach([
        ['👵', 'Yaşlı bakım kurumları için', 'Bakım kararı doktor odasında başlar', 'Bir nörolog alzheimer ya da inme teşhisi koyduğunda, bir ortopedist kalça kırığı sonrası uzun bakım gerektiğini söylediğinde ya da bir geriatri uzmanı evde bakımın artık yetmediğini anlattığında, aile o gün bakım kurumu aramaya başlar.', 'Nörolog, geriatri, ortopedi veya fizik tedavi uzmanı arayan aileler sitemize gelir; henüz hiçbir kurumla görüşmemiş bu ailelere ilk ulaşan kurum siz olursunuz.'],
        ['🧒', 'Çocuk kurumları için', 'Anne babalar en çok çocuk doktorlarına güvenir', 'Bir çocuğun gelişimiyle ilgili ilk soru genellikle çocuk doktorunun odasında sorulur: konuşması, arkadaşlarıyla ilişkisi, okula hazır olup olmadığı.', 'Çocuk doktoru, çocuk psikiyatristi ya da çocuk diş hekimi arayan anne babalar, çocuklarına uygun bir kreş ya da anaokulu da arayan ailelerdir.'],
        ['🤝', 'Rehabilitasyon ve özel eğitim için', 'Teşhisi koyan doktor, aileyi size yönlendirir', 'Otizm, serebral palsi, gelişim geriliği ya da inme sonrası felç: teşhisi bir doktor koyar ve aileye "düzenli terapi ve eğitim gerekiyor" der.', 'Teşhisten hemen sonra kurum arayan aile, karar vermeye en hazır ailedir; sizinle görüşmeden önce başka bir merkeze bağlanmamış olur.'],
      ] as [$icon, $eyebrow, $title, $p1, $p2])
        <div class="ih-card">
          <div style="font-size:30px;line-height:1;">{{ $icon }}</div>
          <div class="ih-eyebrow" style="margin-top:12px;">{{ $eyebrow }}</div>
          <h3 style="font-size:18px;font-weight:900;color:#111827;margin:6px 0 10px;line-height:1.3;">{{ $title }}</h3>
          <p style="font-size:14px;line-height:1.7;color:#4b5563;">{{ $p1 }}</p>
          <p style="font-size:14px;line-height:1.7;color:#4b5563;margin-top:8px;">{{ $p2 }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 3 SİTE / FATURA --}}
<section class="bg-white ih-sec">
  <div class="ih-wrap">
    <div class="ih-grid2">
      <div class="ih-card" style="background:#ecfeff;border-color:#cffafe;">
        <div style="font-size:28px;line-height:1;">🌐</div>
        <h3 style="font-size:18px;font-weight:900;color:#111827;margin:12px 0 8px;">3 Sitede Yayın, Tek Kurum Paneli</h3>
        <p style="font-size:14px;line-height:1.7;color:#374151;">Kurumunuz <b>bakimevleri.com</b>, <b>bakimevibul.com</b> ve <b>bakimeviara.com</b> sitelerinde aynı anda yayınlanır; Standart ve Premium üyelikte buna kendi internet siteniz de eklenir. Aileler hangi siteden gelirse gelsin; ücret/teklif talebi, mesaj, ziyaret talebi ve soruların tamamı <b>tek kurum panelinize</b> düşer.</p>
      </div>
      <div class="ih-card">
        <div style="font-size:28px;line-height:1;">🧾</div>
        <h3 style="font-size:18px;font-weight:900;color:#111827;margin:12px 0 8px;">Faturalı, Gider Gösterilebilir Hizmet</h3>
        <p style="font-size:14px;line-height:1.7;color:#374151;">Adınıza kesilen fatura "Dijital Listeleme, Reklam ve Aracılık Hizmet Bedeli" olarak düzenlenir. İşletmeniz bu faturayı pazarlama gideri olarak değerlendirebilir; ayrıntılar için mali müşavirinize danışabilirsiniz.</p>
      </div>
    </div>
  </div>
</section>

{{-- SIK SORULANLAR --}}
<section class="bg-gray-50 ih-sec">
  <div class="ih-wrap" style="max-width:820px;">
    <div class="ih-center" style="margin-bottom:24px;">
      <span class="ih-eyebrow">Sık Sorulanlar</span>
      <h2 class="ih-h2">Merak edilenler</h2>
    </div>
    <div style="display:grid;gap:12px;">
      @foreach([
        ['Paketimi sonradan yükseltebilir miyim?', 'Evet. '.$tierName(0).' üyelikten '.$tierName(1).' ya da '.$tierName(2).' üyeliğe dilediğiniz zaman geçebilirsiniz; kalan süreniz yeni pakete eklenir.'],
        ['İnternet sitem ne zaman hazır olur?', $tierName(1).' ve '.$tierName(2).' üyelerinde bilgileriniz ve fotoğraflarınız tamamlandıktan sonra siteniz 24 saat içinde yayına alınır.'],
        ['360° sanal tur çekimi nasıl yapılır?', $tierName(2).' üyelerinde randevu oluşturulur, çekim ekibimiz kurumunuza gelir. Çekim yaklaşık 1–2 saat sürer.'],
        ['Üyeliğim bitince ne olur?', 'Kurum profiliniz yayında kalır; üyelik avantajları (ücretsiz teklif, rozet, internet sitesi, sanal tur) süre sonunda durur. Yenilediğinizde kaldığınız yerden devam eder.'],
      ] as [$q, $a])
        <details class="ih-card ih-faq">
          <summary>{{ $q }}</summary>
          <p>{{ $a }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- ŞEFFAFLIK SÖZÜ / İLETİŞİM --}}
<section class="text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 100%);">
  <div class="ih-wrap ih-center" style="padding-top:clamp(48px,8vw,80px);padding-bottom:clamp(48px,8vw,80px);max-width:760px;">
    <h2 style="font-size:clamp(26px,4.4vw,36px);font-weight:900;line-height:1.2;">Şeffaflık Sözümüz</h2>
    <p style="margin-top:14px;line-height:1.7;color:rgba(255,255,255,.9);">Üyelerimizin katkısıyla kaç hastamızın bakımının üstlenildiği bu sayfada her ay paylaşılır. Gelin, kurumunuzu büyütürken bir büyük çınarın duasında buluşalım.</p>
    <div class="ih-btns" style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:28px;">
      <a href="{{ brand_route('facility.login') }}" class="ih-btn" style="background:#fff;color:#701a75;">Kurum Panelime Giriş Yap</a>
      <a href="{{ brand_route('facility-registration.create') }}" class="ih-btn" style="border:1px solid rgba(255,255,255,.4);">Kurumumu Kaydet</a>
      <a href="{{ brand_route('contact.create') }}" class="ih-btn" style="border:1px solid rgba(255,255,255,.4);">Bize Ulaşın</a>
    </div>
    <p style="font-size:12px;color:rgba(255,255,255,.7);margin-top:16px;">Kurumunuz sitemizde listeleniyorsa, kurum sayfanızdaki "Kurum Size mi Ait?" bağlantısıyla sahiplenip üye olabilirsiniz.</p>
  </div>
</section>
@endsection
