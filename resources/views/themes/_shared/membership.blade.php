@extends('layouts.brand')
@section('title', 'İyilik Hareketi')
@section('meta_description', 'Güven Bakım Hizmetleri iş birliğiyle Bursa Şehir Hastanesi\'ndeki kimsesiz yaşlı hastalarımıza el uzatan İyilik Hareketi: kurumunuzu büyütürken bir yaşlının ömrüne dokunun.')
{{-- 18 Ekim 2026: kullanıcının talebi - İyilik Hareketi bilgilendirme sayfası
     (fiyat YOK). Program yayına alınana kadar yalnızca admin açabilir ve
     Google'a kapalıdır. Metin kullanıcının verdiği tanıtım metninden; oran
     vaadi, "bağış/fon" ve kesin vergi vaadi içeren ifadeler kullanıcıyla
     konuşulduğu şekilde yumuşatıldı. --}}
@section('robots_meta', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('content')
@php
  $brandName = $brand['name'];
  $tierPackages = $packages->sortBy('sort_order')->values();
  $memberRights = [
      ['📩', 'Ailelerden gelen ücret ve teklif taleplerini alma, üyelik süresince ücretsiz ve sınırsız teklif verme'],
      ['💬', 'Ailelerle platform üzerinden doğrudan mesajlaşma'],
      ['📅', 'Ziyaret ve kontenjan taleplerini alma'],
      ['❓', 'Ailelerin kurumunuza sorduğu soruları cevaplama'],
      ['⭐', 'Aile yorumlarına kurumunuz adına cevap verme'],
      ['🖼️', 'Profil, fotoğraf galerisi, video ve yemek listesi yönetimi'],
      ['🛏️', 'Doluluk ve boş kontenjan bilgisini güncelleme'],
      ['👥', 'Ekip arkadaşlarınızı kurum panelinize davet etme'],
      ['🧑‍💼', 'Kurumunuza gelen iş başvurularını alma'],
      ['📊', 'Görüntülenme ve iletişim istatistiklerinizi takip etme'],
      ['🧭', 'Fırsat Havuzu: bölgenizde bakım arayan ailelerin taleplerini görme'],
      ['📞', 'Kurum sayfanızda görünür telefon ve WhatsApp butonları, öne çıkan kurum kartı ve üyelik rozeti'],
  ];
@endphp

@if($isPreview)
  <div class="text-center text-sm font-bold px-4 py-2" style="background:#fde047;color:#422006;">Önizleme — bu sayfa henüz yayında değil. Yalnızca admin oturumunda görünür; admin panelinde Paketler &gt; "Yayına al" ile yayınlanır.</div>
@endif

{{-- HERO --}}
<section class="relative overflow-hidden text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 45%,#92400e 100%);">
  <div class="max-w-4xl mx-auto px-4 py-16 text-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-wide mb-6">💛 İyilik Hareketi</div>
    <h1 class="text-3xl md:text-5xl font-black leading-tight mb-6 text-balance">Bir Çocuğun Geleceğini Büyütürken, Bir Yaşlının Ömrüne Dokunmak...</h1>
    <p class="text-lg text-white/90 leading-relaxed max-w-2xl mx-auto">
      {{ $brandName }} çatısı altında, sadece kurumları ailelerle buluşturan bir rehber değil; Bursa Şehir Hastanesi'nden yükselen bir iyilik köprüsüyüz.
    </p>
  </div>
</section>

{{-- ANA METİN --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-12 grid md:grid-cols-5 gap-8 items-center">
    <div class="{{ $impact['office_image_url'] ? 'md:col-span-3' : '' }} space-y-4 text-gray-700 leading-relaxed" style="{{ $impact['office_image_url'] ? '' : 'grid-column:1/-1;' }}">
      <p>Dijital dünyada büyümek, markanıza değer katmak ve daha çok aileye ulaşmak her işletmenin hedefidir. Biz bu büyüme hedefini çok daha anlamlı bir amaca dönüştürüyoruz.</p>
      <p><strong class="text-gray-900">Güven Bakım Hizmetleri</strong> iş birliğiyle, <strong class="text-gray-900">Bursa Şehir Hastanesi Batı Poliklinik Girişi</strong>'ndeki ofisimizden her gün hastalarımıza ve ailelerine destek oluyoruz. Hastane koridorlarında, odalarında kimsesi olmayan, maddi durumu yetersiz, tek başına kalmış yaşlı çınarlarımızın hikâyelerine de bizzat tanıklık ediyoruz.</p>
      <p class="text-xl font-black" style="color:#a21caf;">İşte "İyilik Hareketi" tam olarak burada başlıyor.</p>
    </div>
    @if($impact['office_image_url'])
      <div class="md:col-span-2">
        <img src="{{ $impact['office_image_url'] }}" alt="Güven Bakım Hizmetleri, Bursa Şehir Hastanesi ofisi" class="w-full rounded-2xl shadow-lg object-cover" loading="lazy">
        <p class="text-xs text-gray-500 mt-2 text-center">Güven Bakım Hizmetleri — Bursa Şehir Hastanesi Batı Poliklinik Girişi</p>
      </div>
    @endif
  </div>
</section>

{{-- ŞEFFAFLIK SAYACI (admin panelinden girilir; boşsa görünmez) --}}
@if($impact['patients'] || $impact['hours'])
  <section style="background:#fdf4ff;">
    <div class="max-w-4xl mx-auto px-4 py-10 text-center">
      <div class="text-xs font-black uppercase tracking-wide mb-4" style="color:#a21caf;">Şeffaflık Paneli · {{ $impact['period'] }}</div>
      <div class="grid {{ $impact['patients'] && $impact['hours'] ? 'grid-cols-2' : 'grid-cols-1' }} gap-4 max-w-xl mx-auto">
        @if($impact['patients'])
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="text-4xl font-black" style="color:#701a75;">{{ number_format($impact['patients'], 0, ',', '.') }}</div>
            <div class="text-sm text-gray-600 mt-1">üyelerimiz sayesinde bakımı üstlenilen hasta</div>
          </div>
        @endif
        @if($impact['hours'])
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="text-4xl font-black" style="color:#701a75;">{{ number_format($impact['hours'], 0, ',', '.') }}</div>
            <div class="text-sm text-gray-600 mt-1">saat profesyonel bakım</div>
          </div>
        @endif
      </div>
    </div>
  </section>
@endif

{{-- SİSTEM NASIL ÇALIŞIYOR --}}
<section class="bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-4">Sistem Nasıl Çalışıyor?</h2>
    <div class="space-y-4 text-gray-700 leading-relaxed">
      <p>Platformumuza üye olan her kreş, anaokulu, huzurevi ve rehabilitasyon merkezi yalnızca bir reklam veya listeleme hizmeti satın almaz. Kurumlarımızın ödediği üyelik bedellerinin <strong>bir bölümü</strong> kurumların tanıtımı ve platformun reklam çalışmalarına; <strong>bir bölümü</strong> ise Bursa Şehir Hastanesi'nde yatan, kimsesiz ve maddi imkânı bulunmayan yaşlılarımızın profesyonel bakım hizmetlerine ayrılır.</p>
      <p>Siz kurumunuzu büyütürken, hastanedeki bir yaşlımızın yatak içi hijyen bakımı, refakatçi desteği ve şefkat ihtiyacı karşılanır.</p>
      <div class="bg-white rounded-xl border border-gray-200 p-4 text-sm">
        <strong class="text-gray-900">Bursa dışındaki kurumlarımız için:</strong> Üyelik gelirinin tanıtıma ayrılan bölümü, kendi şehrinizdeki ailelerin kurumunuzu bulması için kullanılır. Diğer bölümü, sosyal sorumluluk projemizin ilk ayağı olan Bursa Şehir Hastanesi'ndeki kimsesiz yaşlılarımızın bakımına gider.
      </div>
    </div>
  </div>
</section>

{{-- ÜYELİK SEVİYELERİ (fiyat yok) --}}
@if($tierPackages->isNotEmpty())
  <section class="bg-white">
    <div class="max-w-5xl mx-auto px-4 py-12">
      <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-2 text-center">Üyelik Seviyeleri</h2>
      <p class="text-gray-600 text-center mb-8">Kurumunuz seçtiği üyeliğe göre profil sayfasında ve kurum kartında aşağıdaki rozetlerden birini taşır.</p>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($tierPackages as $package)
          <div class="rounded-2xl p-5 border-2 {{ $package->is_premium ? 'border-amber-300' : 'border-gray-100' }} bg-white shadow-sm flex flex-col">
            <div class="text-3xl mb-2">{{ $package->badge_emoji }}</div>
            <div class="font-black text-gray-900">{{ $package->badge_label }}</div>
            <div class="text-xs font-bold text-gray-500 mt-1">{{ $package->months == 12 ? 'Yıllık' : $package->months.' aylık' }} üyelik @if($package->free_months_label)· <span class="text-green-700">{{ $package->free_months_label }}</span>@endif</div>
            @if($package->tagline)<p class="text-sm text-gray-600 mt-3">{{ $package->tagline }}</p>@endif
            @if($package->featureList())
              <ul class="text-sm text-gray-600 mt-3 space-y-1">
                @foreach($package->featureList() as $feature)<li>✓ {{ $feature }}</li>@endforeach
              </ul>
            @endif
            @if($package->only_city_slug)<div class="mt-auto pt-3 text-xs font-bold text-amber-700">Şu an yalnızca {{ mb_convert_case($package->only_city_slug, MB_CASE_TITLE, 'UTF-8') }} ilindeki kurumlarımıza (pilot uygulama)</div>@endif
          </div>
        @endforeach
      </div>
      <p class="text-sm text-gray-500 text-center mt-6">Üyelik ücretleri kurumlara özel olarak iletilir. Kurum panelinizden veya bizimle iletişime geçerek öğrenebilirsiniz.</p>
    </div>
  </section>
@endif

{{-- ÜYE HAKLARI --}}
<section class="bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-2">Üye Kurumların Kullanabildiği Özellikler</h2>
    <p class="text-gray-600 mb-6">Hangi seviyede olursa olsun her üye kurum, üyelik bitiş tarihine kadar sahipli kurumların kullandığı tüm özellikleri kullanır:</p>
    <div class="grid md:grid-cols-2 gap-3">
      @foreach($memberRights as [$icon, $text])
        <div class="bg-white rounded-xl p-4 flex gap-3 items-start shadow-sm">
          <span class="text-xl leading-none">{{ $icon }}</span>
          <span class="text-sm text-gray-700">{{ $text }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- AYRICALIKLAR --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-6">Bizimle Yürüyen Kurumların Ayrıcalıkları</h2>
    <div class="space-y-4">
      <div class="rounded-2xl p-5" style="background:#fdf4ff;">
        <div class="font-black text-gray-900 mb-1">💜 Kurumsal İyilik Rozeti</div>
        <p class="text-sm text-gray-700">Profil sayfanıza ve kurum kartınıza eklenen özel dijital rozet sayesinde, kurumunuzu tercih eden veliler ve aileler, kurumunuzun bir sosyal sorumluluk projesine destek verdiğini görür; bu da kurumunuza duyulan güveni artırır. Rozetinizi kendi sosyal medya hesaplarınızda da gururla paylaşabilirsiniz.</p>
      </div>
      <div class="rounded-2xl p-5 bg-gray-50">
        <div class="font-black text-gray-900 mb-1">🧾 Faturalı, Gider Gösterilebilir Hizmet</div>
        <p class="text-sm text-gray-700">Aldığınız hizmet karşılığında adınıza kesilen fatura "Dijital Listeleme, Reklam ve Aracılık Hizmet Bedeli" olarak düzenlenir. İşletmeniz bu faturayı pazarlama gideri olarak değerlendirebilir; ayrıntılar için mali müşavirinize danışabilirsiniz.</p>
      </div>
      <div class="rounded-2xl p-5" style="background:#fef3c7;">
        <div class="font-black text-gray-900 mb-1">🎬 Profesyonel Prodüksiyon Desteği (Yıllık Premium)</div>
        <p class="text-sm text-gray-700">Vizyoner iş ortaklarımızın kurum binaları, sınıfları ve bahçeleri profesyonel ekiplerimizce 4K drone çekimleri, iç mekân fotoğrafçılığı ve 360 derece fotoğraflarla görüntülenir; tanıtım videosu kurum sayfanızda yayınlanır. Bu hizmet şu an Bursa'da pilot olarak sunulmaktadır.</p>
      </div>
    </div>
  </div>
</section>

{{-- ŞEFFAFLIK SÖZÜ --}}
<section style="background:linear-gradient(135deg,#4a044e 0%,#701a75 100%);" class="text-white">
  <div class="max-w-3xl mx-auto px-4 py-12 text-center">
    <h2 class="text-2xl md:text-3xl font-black mb-4">Şeffaflık Sözümüz</h2>
    <p class="text-white/90 leading-relaxed">Üyelerimizin katkısıyla kaç hastamıza ne kadar bakım sağlandığı bu sayfada düzenli olarak paylaşılır. Gelin, markanızı büyütürken bir büyük çınarın duasında buluşalım.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a href="{{ brand_route('facility.login') }}" class="bg-white font-black px-5 py-3 rounded-xl" style="color:#701a75;">Kurum Panelime Giriş Yap</a>
      <a href="{{ brand_route('facility-registration.create') }}" class="border font-bold px-5 py-3 rounded-xl" style="border-color:rgba(255,255,255,.4);">Kurumumu Kaydet</a>
      <a href="{{ brand_route('contact.create') }}" class="border font-bold px-5 py-3 rounded-xl" style="border-color:rgba(255,255,255,.4);">Bize Ulaşın</a>
    </div>
    <p class="text-xs text-white/70 mt-4">Kurumunuz sitemizde listeleniyorsa, kurum sayfanızdaki "Kurum Size mi Ait?" bağlantısıyla sahiplenip üye olabilirsiniz.</p>
  </div>
</section>
@endsection
