@extends('admin.layout')
@section('title', 'Genel Bakış')

@section('content')
@php
  $offerStatusLabels = ['new' => 'Yeni talep', 'contacted' => 'İletişime geçildi', 'closed' => 'Kapandı'];
  $extraClaimsCount = max(0, $pendingClaims - $latestClaims->count());
@endphp
<h1 class="text-2xl font-bold mb-6">Genel Bakış</h1>

@if($pendingClaims > 0 || $pendingTopups > 0 || $pendingRegistrations > 0 || $newVisitServiceRequests > 0 || $unreadContactMessages > 0 || $pendingAccountDeletions > 0 || $newInterestLeads > 0)
<div class="grid md:grid-cols-3 gap-4 mb-6">
  @if($pendingClaims > 0)
    <a href="{{ route('admin.claims.index') }}" class="block bg-orange-50 border border-orange-200 text-orange-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingClaims }}</strong> sahiplenme başvurusu onay bekliyor →
      @if($latestClaims->isNotEmpty())
        <div class="mt-2 text-xs font-normal text-orange-700">{{ $latestClaims->pluck('facility.name')->filter()->implode(', ') }}{{ $extraClaimsCount > 0 ? ' ve '.$extraClaimsCount.' diğeri' : '' }}</div>
      @endif
    </a>
  @endif
  @if($pendingTopups > 0)
    <a href="{{ route('admin.topups.index') }}" class="block bg-blue-50 border border-blue-200 text-blue-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingTopups }}</strong> bakiye yükleme talebi onay bekliyor
      <div class="mt-1 text-xs font-normal text-blue-700">Toplam {{ number_format($pendingTopupsAmount, 0, ',', '.') }} ₺ →</div>
    </a>
  @endif
  @if($pendingRegistrations > 0)
    <a href="{{ route('admin.registrations.index') }}" class="block bg-purple-50 border border-purple-200 text-purple-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingRegistrations }}</strong> yeni kurum kaydı onay bekliyor →
    </a>
  @endif
  {{-- 27 Agustos 2026: kullanicinin bildirdigi gercek eksiklik - bkz.
       DashboardController ayni tarihli yorum. --}}
  @if($newVisitServiceRequests > 0)
    <a href="{{ route('admin.visit-service.index', ['status' => 'yeni']) }}" class="block bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl">
      <strong>{{ $newVisitServiceRequests }}</strong> yeni "Yakınımı Ziyaret Et" talebi var →
    </a>
  @endif
  {{-- 5 Ekim 2026: kullanicinin bildirdigi gercek eksiklik - bkz.
       DashboardController ayni tarihli yorum. --}}
  @if($newInterestLeads > 0)
    <a href="{{ route('admin.fee-info-requests.index') }}" class="block bg-teal-50 border border-teal-200 text-teal-800 px-5 py-4 rounded-xl">
      <strong>{{ $newInterestLeads }}</strong> yeni ilgi/bilgi talebi var →
    </a>
  @endif
  {{-- 4 Eylul 2026: kullanicinin talebi - ContactMessage (is_read) ve
       AccountDeletionRequest (status) verisi zaten vardi ama bu kutuya hic
       yansimiyordu, 27 Agustos'taki VisitServiceRequest duzeltmesiyle AYNI
       eksiklik turu (bkz. DashboardController ayni tarihli yorum). --}}
  @if($unreadContactMessages > 0)
    <a href="{{ route('admin.contact-messages.index', ['unread' => 1]) }}" class="block bg-yellow-50 border border-yellow-200 text-yellow-800 px-5 py-4 rounded-xl">
      <strong>{{ $unreadContactMessages }}</strong> okunmamış iletişim mesajı var →
    </a>
  @endif
  @if($pendingAccountDeletions > 0)
    <a href="{{ route('admin.account-deletions.index') }}" class="block bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl">
      <strong>{{ $pendingAccountDeletions }}</strong> hesap silme talebi onay bekliyor →
    </a>
  @endif
</div>
@endif

{{-- 23 Ekim 2026: kullanıcının talebi - kurum siteleri özeti (standart/profesyonel, bitişler, son 30 gün). --}}
@include('admin.facility-websites._ozet')
@include('admin.expert-websites._ozet')

{{-- 14 Agustos 2026: kullanicinin talebi - "isletme sagligi ozeti", her
     seferinde ayri sayfalara girmeden gunluk trendi (ziyaret, yeni
     basvuru, yeni talep, yeni hata) tek ekranda gormek icin (bkz.
     Admin\DashboardController::healthSummary()). --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-10">
  <div class="flex items-center justify-between mb-1">
    <h2 class="font-bold text-lg">İşletme Sağlığı — Son 14 Gün</h2>
    <a href="{{ route('admin.platform-errors.index') }}" class="text-xs font-semibold {{ $health['unresolved_errors'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $health['unresolved_errors'] }} çözülmemiş hata →</a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    @php
      $healthCards = [
        ['key' => 'visits', 'label' => 'Site Ziyareti (3 marka)', 'color' => '#0b5d8c'],
        ['key' => 'claims', 'label' => 'Yeni Sahiplenme Başvurusu', 'color' => '#1e6f5c'],
        ['key' => 'offers', 'label' => 'Yeni Teklif Talebi', 'color' => '#5b3a8e'],
        ['key' => 'errors', 'label' => 'Yeni Hata', 'color' => '#dc2626'],
      ];
    @endphp
    @foreach($healthCards as $card)
      @php
        $d = $health[$card['key']];
        $diff = $d['this_week'] - $d['last_week'];
        $pct = $d['last_week'] > 0 ? round($diff / $d['last_week'] * 100) : ($d['this_week'] > 0 ? 100 : 0);
        $maxVal = max(1, max($d['daily']));
        $isErrorCard = $card['key'] === 'errors';
      @endphp
      <div class="rounded-lg bg-gray-50 p-3">
        <div class="text-xs text-gray-500">{{ $card['label'] }}</div>
        <div class="text-xl font-black text-gray-950 mt-1">{{ number_format($d['this_week'], 0, ',', '.') }}</div>
        <div class="text-xs font-bold mt-0.5 {{ ($diff >= 0) === $isErrorCard ? 'text-red-600' : 'text-green-600' }}">
          {{ $diff >= 0 ? '▲' : '▼' }} %{{ abs($pct) }} geçen haftaya göre
        </div>
        <div class="flex items-end gap-0.5 h-10 mt-2">
          @foreach($d['daily'] as $i => $val)
            <div class="flex-1 rounded-t" style="height: {{ max(2, round($val / $maxVal * 40)) }}px; background: {{ $card['color'] }}{{ $val > 0 ? '' : '33' }};" title="{{ $health['dates'][$i] }}: {{ $val }}"></div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- 29 Eylul 2026: kullanicinin talebi - pazarlamaci satis surecini
     ("arandi -> yetkiliyle gorustu -> detay dinledi -> fiyat istedi ->
     odeme") ana sayfada ayrintili takip edebilmek icin (bkz.
     FacilityMarketingNote::funnelStages() - ayni hesap admin/pazarlamaci-
     hunisi tam sayfasiyla PAYLASILIR, iki ayri hesap yok). --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-10">
  <div class="flex items-center justify-between mb-4">
    <h2 class="font-bold text-lg">Pazarlamacı Satış Hunisi</h2>
    <a href="{{ route('admin.marketers.funnel') }}" class="text-xs font-semibold text-gray-500">Ayrıntılı görünüm →</a>
  </div>
  @if($salesFunnel[0]['count'] === 0)
    <div class="text-sm text-gray-400">Henüz hiçbir kurum aranmamış, huni burada birikecek.</div>
  @else
    {{-- 15 Kasım 2026: kullanıcı "çok kötü görünüyor" - dar ekranda kelimeler harf harf kırılıyordu (min-w-[140px] derlenmiş CSS'te yok).
         Satır içi ızgara: her adım en az 130 px, sığmayan alt satıra geçer; oklar yerine adım numarası. --}}
    <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));">
      @foreach($salesFunnel as $i => $stage)
        <div style="background:#f9fafb;border:1px solid #eef0f3;border-radius:12px;padding:12px 14px;min-width:0;">
          <div style="font-size:12.5px;color:#6b7280;line-height:1.35;word-break:normal;overflow-wrap:normal;hyphens:none;"><span style="color:#9ca3af;font-weight:800;">{{ $i + 1 }}.</span> {{ $stage['label'] }}</div>
          <div style="font-size:22px;font-weight:900;color:#030712;margin-top:4px;">{{ $stage['count'] }}</div>
          <div style="font-size:11.5px;font-weight:600;color:#9ca3af;margin-top:2px;line-height:1.4;">
            %{{ $stage['percent_of_total'] }} toplam
            @if($stage['percent_of_prev'] !== null)
              <br>%{{ $stage['percent_of_prev'] }} önceki adımdan
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- 15 Kasım 2026: kullanıcının talebi - "bütün istatistikleri tek sayfada topla" + "sayaçlarımıza güvenmiyorum".
     Bölümlere giriş, kurum türüne göre ilgi ve en çok tıklanan kurumlar İstatistikler sayfasına taşındı ve
     Google Analytics verisiyle gösteriliyor (bkz. admin/site-stats/_analytics.blade.php). --}}
<a href="{{ route('admin.site-stats.index') }}" style="display:block;background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:14px;padding:16px 20px;margin-bottom:28px;text-decoration:none;color:#0c4a6e;">
  <div style="font-size:16px;font-weight:800;">📊 Ziyaretçi, bölüm ve kurum istatistikleri İstatistikler sayfasında →</div>
  <div style="font-size:13px;margin-top:4px;">Google Analytics verisiyle: 3 site, 4 bölüm, kurum türleri, en çok bakılan kurumlar, trafik kaynakları ve Google arama.</div>
  {{-- 16 Kasım 2026: son 24 saat giriş sayıları (saatlik görevin önbelleğinden; sayfa açılışında Google'a gidilmez). --}}
  @php $son24 = \Illuminate\Support\Facades\Cache::get('google_son24'); @endphp
  @if($son24)
    <div style="display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:10px;font-size:13.5px;">
      <span style="font-weight:800;">Son 24 saat:</span>
      @foreach($son24['siteler'] as $s => $x)
        <span>{{ $s }}: <b>{{ number_format($x['kisi'], 0, ',', '.') }}</b> ziyaretçi</span>
      @endforeach
    </div>
  @endif
</a>

<div class="grid md:grid-cols-3 gap-6 mb-10">
  @foreach($stats as $slug => $s)
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-bold mb-3">{{ $s['name'] }}</h2>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div><div class="text-gray-500">Kurum</div><div class="font-bold text-lg">{{ $s['facilities'] }}</div></div>
        <div><div class="text-gray-500">Sahiplenilmiş</div><div class="font-bold text-lg text-green-700">{{ $s['claimed'] }}</div></div>
        <div><div class="text-gray-500">Teklif Talebi</div><div class="font-bold text-lg">{{ $s['offer_requests'] }}</div></div>
        <div><div class="text-gray-500">Yeni Talep</div><div class="font-bold text-lg text-orange-600">{{ $s['new_offer_requests'] }}</div></div>
        <div><div class="text-gray-500">İlgi Talebi</div><div class="font-bold text-lg">{{ $s['interest_leads'] }}</div></div>
        <div><div class="text-gray-500">Yeni İlgi</div><div class="font-bold text-lg text-teal-600">{{ $s['new_interest_leads'] }}</div></div>
      </div>
      <a href="{{ route('admin.facilities.index', ['brand' => $slug]) }}" class="text-xs text-blue-600 mt-3 inline-block">Kurumları görüntüle →</a>
    </div>
  @endforeach
</div>

<h2 class="text-lg font-bold mb-4">Son Teklif Talepleri</h2>
{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap kart. --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($latestOffers as $offer)
    <div class="p-4 flex items-center justify-between gap-3 flex-wrap text-sm">
      <div>
        <span class="font-semibold">{{ $offer->full_name }}</span>
        <span class="text-gray-500"> · {{ $offer->phone }} · {{ $offer->facility?->name ?? '-' }}</span>
      </div>
      <span class="text-xs text-gray-500">{{ $offerStatusLabels[$offer->status] ?? $offer->status }} · {{ $offer->created_at->format('d.m.Y H:i') }}</span>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz talep yok.</div>
  @endforelse
</div>
@endsection
