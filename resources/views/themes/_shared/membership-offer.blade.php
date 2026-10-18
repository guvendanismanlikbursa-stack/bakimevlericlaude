@extends('layouts.brand')
@section('title', $facility->name.' için Üyelik Teklifi')
{{-- 18 Ekim 2026: kullanıcının talebi - kuruma özel İyilik Hareketi teklif
     sayfası. Fiyatlar yalnızca burada ve kurum panelinde görünür; sayfa her
     zaman Google'a kapalıdır, linki bilmeyen açamaz. İstatistikler GERÇEK
     verilerdir (real_view olayları, ücret/teklif talepleri, telefon/WhatsApp
     tıklamaları) - artırılmış görüntülenme sayıları bilerek kullanılmaz. --}}
@section('robots_meta', 'noindex,nofollow')
@section('content')
@php
  $platformWhatsapp = normalize_whatsapp_number(\App\Models\Setting::get('whatsapp_number', config('platform.default_whatsapp.number')));
  $waText = rawurlencode("Merhaba, {$facility->name} için İyilik Hareketi üyeliği hakkında bilgi almak istiyorum.");
  $contactsHidden = ! $facility->isActiveSubscriber() && ! $facility->is_broker_managed;
@endphp

@if($isPreview)
  <div class="text-center text-sm font-bold px-4 py-2" style="background:#fde047;color:#422006;">Önizleme — İyilik Hareketi henüz yayında değil. Bu teklif sayfası şu an yalnızca admin oturumunda açılır.</div>
@endif

<section class="text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 50%,#92400e 100%);">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <div class="text-xs font-black uppercase tracking-wide text-white/80 mb-2">💛 İyilik Hareketi · Kurumunuza özel teklif</div>
    <h1 class="text-2xl md:text-4xl font-black leading-tight">{{ $facility->name }}</h1>
    <p class="text-white/80 mt-1">{{ $facility->category->name ?? '' }}@if($facility->city) · {{ $facility->district ? $facility->district.', ' : '' }}{{ $facility->city->name }}@endif</p>
  </div>
</section>

{{-- KURUMUN GERÇEK RAKAMLARI --}}
<section class="bg-white">
  <div class="max-w-4xl mx-auto px-4 py-10">
    <h2 class="text-xl font-black text-gray-900 mb-4">Son 30 günde kurumunuza gösterilen ilgi</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="rounded-2xl p-5 text-center" style="background:#fdf4ff;">
        <div class="text-4xl font-black" style="color:#701a75;">{{ number_format($stats['real_views'], 0, ',', '.') }}</div>
        <div class="text-sm text-gray-600 mt-1">kişi kurum sayfanızı ziyaret etti</div>
      </div>
      <div class="rounded-2xl p-5 text-center" style="background:#fef3c7;">
        <div class="text-4xl font-black" style="color:#92400e;">{{ number_format($stats['price_requests'], 0, ',', '.') }}</div>
        <div class="text-sm text-gray-600 mt-1">aile size ücret veya teklif sordu</div>
      </div>
      <div class="rounded-2xl p-5 text-center bg-gray-50">
        <div class="text-4xl font-black text-gray-800">{{ number_format($stats['contact_clicks'], 0, ',', '.') }}</div>
        <div class="text-sm text-gray-600 mt-1">kez telefon / WhatsApp butonunuza tıklandı</div>
      </div>
    </div>
    @if($contactsHidden)
      <p class="text-sm text-gray-700 mt-4 bg-gray-50 rounded-xl p-4">📞 Telefon ve WhatsApp butonlarınız şu an <strong>gizli</strong>; sayfanızı ziyaret eden aileler size doğrudan ulaşamıyor. Üye olduğunuzda butonlarınız görünür olur ve gelen tüm talepler doğrudan size ulaşır.</p>
    @endif
  </div>
</section>

{{-- ŞEHRE GÖRE MESAJ --}}
<section class="bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 py-10 text-gray-700 leading-relaxed space-y-3">
    <h2 class="text-xl font-black text-gray-900">Üyeliğiniz nereye harcanıyor?</h2>
    @if($isBursa)
      <p>Üyelik gelirinin bir bölümü <strong>Güven Bakım Hizmetleri</strong> iş birliğiyle, <strong>Bursa Şehir Hastanesi</strong>'nde yatan kimsesiz ve maddi imkânı bulunmayan yaşlı komşularımızın profesyonel bakımına; bir bölümü de kurumunuzun ve platformun Bursa'daki tanıtımına ayrılır.</p>
      <p>Siz kurumunuzu büyütürken, hastanedeki bir yaşlımızın yatak içi hijyen bakımı, refakatçi desteği ve şefkat ihtiyacı karşılanır.</p>
    @else
      <p>Üyelik gelirinin bir bölümü, şehrinizdeki{{ $facility->city ? ' ('.$facility->city->name.')' : '' }} ailelerin kurumunuzu bulması için tanıtıma harcanır.</p>
      <p>Diğer bölümü, sosyal sorumluluk projemizin ilk ayağı olan <strong>Bursa Şehir Hastanesi</strong>'nde <strong>Güven Bakım Hizmetleri</strong> iş birliğiyle kimsesiz ve maddi imkânı bulunmayan yaşlılarımızın bakımına ayrılır.</p>
    @endif
    @if($impact['patients'])
      <p class="font-bold" style="color:#701a75;">{{ $impact['period'] }} üyelerimiz sayesinde {{ number_format($impact['patients'], 0, ',', '.') }} hastamızın bakımı üstlenildi.</p>
    @endif
    <p><a href="{{ brand_route('pages.membership') }}" class="underline font-semibold">İyilik Hareketi hakkında ayrıntılı bilgi</a></p>
  </div>
</section>

{{-- PAKETLER (FİYATLI) --}}
<section class="bg-white">
  <div class="max-w-5xl mx-auto px-4 py-10">
    <h2 class="text-xl font-black text-gray-900 mb-2">Üyelik seçenekleri</h2>
    @if($launchNotice = membership_launch_notice())
      <div class="rounded-xl px-4 py-3 mb-6 text-center font-black" style="background:#fef3c7;color:#92400e;">🎉 {{ $launchNotice }}</div>
    @endif
    <p class="text-sm text-gray-600 mb-6">Her üyelikte, üyelik süresi boyunca <strong>ücretsiz ve sınırsız teklif</strong> verir, <strong>sınırsız fotoğraf</strong> yükler, sahipli kurumların tüm panel özelliklerini kullanırsınız; kurum kartınızda ve sayfanızda üyelik rozetiniz görünür. Kurum panelinden tanıtım videosu yükleme yalnızca Vizyoner İyilik Elçisi üyeliğinde.</p>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      @foreach($packages as $package)
        <div class="rounded-2xl p-5 border-2 {{ $package->is_premium ? 'border-amber-300' : 'border-gray-100' }} bg-white shadow-sm flex flex-col">
          <div class="text-xs font-black px-2 py-1 rounded-full self-start" style="background:#fae8ff;color:#a21caf;">{{ $package->badge_emoji }} {{ $package->badge_label }}</div>
          <div class="font-bold text-gray-900 mt-3">{{ $package->name }}</div>
          @if($package->hasLaunchDiscount())
            <div class="text-sm text-gray-400 mt-1"><span style="text-decoration:line-through;">{{ number_format($package->list_price, 0, ',', '.') }}₺</span> <span class="text-xs">liste fiyatı</span></div>
            <div class="text-xs font-black mt-1" style="color:#92400e;">Lansman fiyatı</div>
          @endif
          <div class="text-3xl font-black my-1" style="color:#701a75;">{{ number_format($package->price, 0, ',', '.') }}₺</div>
          @if($package->free_months_label)<div class="text-xs font-bold text-green-700">🎁 {{ $package->free_months_label }}</div>@endif
          @if($package->tagline)<p class="text-sm text-gray-600 mt-3">{{ $package->tagline }}</p>@endif
          @if($package->featureList())
            <ul class="text-sm text-gray-600 mt-3 space-y-1">
              @foreach($package->featureList() as $feature)<li>✓ {{ $feature }}</li>@endforeach
            </ul>
          @endif
        </div>
      @endforeach
    </div>
    <div class="rounded-2xl p-4 mt-6 text-sm text-gray-700" style="background:#ecfeff;">
      <strong>🌐 3 sitede yayın, tek panel:</strong> Kurumunuz bakimevleri.com, bakimevibul.com ve bakimeviara.com'da aynı anda yayınlanır; üç siteden gelen tüm aile talepleri, mesajlar, ziyaret istekleri ve sorular tek kurum panelinize düşer.
    </div>
    <p class="text-xs text-gray-500 mt-4">Faturanız "Dijital Listeleme, Reklam ve Aracılık Hizmet Bedeli" olarak düzenlenir.</p>
  </div>
</section>

{{-- NASIL ÜYE OLUNUR --}}
<section class="text-white" style="background:linear-gradient(135deg,#4a044e 0%,#701a75 100%);">
  <div class="max-w-3xl mx-auto px-4 py-12 text-center">
    <h2 class="text-2xl font-black mb-3">Nasıl üye olurum?</h2>
    @if($hasPanelAccount)
      <p class="text-white/90 mb-6">Kurum panelinize giriş yapın, <strong>Paketler</strong> bölümünden üyeliğinizi seçip havale/EFT dekontunuzu yükleyin. Onaylandığı anda üyeliğiniz başlar.</p>
      <a href="{{ brand_route('facility.login') }}" class="inline-block bg-white font-black px-6 py-3 rounded-xl" style="color:#701a75;">Kurum Panelime Giriş Yap</a>
    @else
      <p class="text-white/90 mb-6">Önce kurumunuzu sahiplenin (birkaç dakika sürer, yetki belgesi gerekir). Başvurunuz onaylanınca kurum panelinizden üyeliğinizi seçip dekontunuzu yükleyebilirsiniz.</p>
      <a href="{{ brand_route('facility-claim.create', ['slug' => $facility->slug]) }}" class="inline-block bg-white font-black px-6 py-3 rounded-xl" style="color:#701a75;">Kurumumu Sahiplen</a>
    @endif
    @if($platformWhatsapp)
      <div class="mt-4"><a href="https://wa.me/{{ $platformWhatsapp }}?text={{ $waText }}" target="_blank" rel="noopener" class="inline-block border font-bold px-5 py-2 rounded-xl text-sm" style="border-color:rgba(255,255,255,.4);">Sorularınız için WhatsApp'tan yazın</a></div>
    @endif
  </div>
</section>
@endsection
