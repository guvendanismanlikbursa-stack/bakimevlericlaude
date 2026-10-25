{{--
    11 Eylul 2026: kullanicinin talebi - bolum secim kartlarinin hemen
    altina, secilen bolume gore degisen, anlasmali (is_broker_managed)
    kurumlari tercih etmeyi tesvik eden dikkat cekici bir bilgilendirme
    kutusu. Vaat KOSULLU olarak yazildi ("sitemiz uzerinden bize ulasin") -
    amac hem aileye somut bir fayda anlatmak hem de yerlestirmenin platform
    disinda, hic haberimiz olmadan gerceklesmesini (takip/komisyon/hizmet
    kaybi riski) azaltmak. $section degiskeni cagiran sayfada zaten mevcut.
--}}
@php
  $brokerBanner = match($section['slug'] ?? null) {
    'cocuk' => [
      'icon' => '🛡️',
      'title' => 'Anlaşmalı Kreş/Anaokullarından Birini Seçin, 1 Yıl Ferdi Kaza Sigortası Bizden!',
      // 25 Ekim 2026: kullanıcının talebi - açıklama metni çok yer kaplıyordu, kaldırıldı.
      'body' => '',
      'cta' => 'Bu hediyeden yalnızca kurumla doğrudan değil, önce sitemiz üzerinden bize ulaşan aileler faydalanabilir — sigorta sürecinizi sizin için biz başlatalım.',
    ],
    'yasli-bakim' => [
      'icon' => '🤝',
      'title' => 'Anlaşmalı Kurumlarımıza Yerleştirin, Takibini Biz Üstlenelim',
      // 25 Ekim 2026: kullanıcının talebi - açıklama metni çok yer kaplıyordu, kaldırıldı.
      'body' => '',
      'cta' => 'Bu hizmetten faydalanmak için yerleştirme sürecini kurumla doğrudan değil, sitemiz üzerinden başlatmanız yeterli.',
    ],
    default => null,
  };
@endphp
@if($brokerBanner)
  <div class="rounded-xl border-2 p-4 sm:p-5 mb-6" style="background:#f0fdfa;border-color:#5eead4;">
    <div class="flex items-start gap-3">
      <span class="text-2xl leading-none" aria-hidden="true">{{ $brokerBanner['icon'] }}</span>
      <div>
        <div class="font-black text-base sm:text-lg leading-snug" style="color:#134e4a;">{{ $brokerBanner['title'] }}</div>
        @if($brokerBanner['body'] !== '')<p class="text-sm mt-1" style="color:#115e59;">{{ $brokerBanner['body'] }}</p>@endif
        <p class="text-xs font-bold mt-2" style="color:#0f766e;">📩 {{ $brokerBanner['cta'] }}</p>
      </div>
    </div>
  </div>
@endif
