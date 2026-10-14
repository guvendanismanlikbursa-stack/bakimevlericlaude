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
    <div class="flex flex-wrap gap-2">
      @foreach($salesFunnel as $i => $stage)
        <div class="flex-1 min-w-[140px] rounded-lg bg-gray-50 p-3">
          <div class="text-xs text-gray-500">{{ $stage['label'] }}</div>
          <div class="text-xl font-black text-gray-950 mt-1">{{ $stage['count'] }}</div>
          <div class="text-xs font-semibold text-gray-400 mt-0.5">
            %{{ $stage['percent_of_total'] }} toplam
            @if($stage['percent_of_prev'] !== null)
              · %{{ $stage['percent_of_prev'] }} önceki adımdan
            @endif
          </div>
        </div>
        @if(! $loop->last)
          <div class="flex items-center text-gray-300 text-lg px-1">→</div>
        @endif
      @endforeach
    </div>
  @endif
</div>

{{-- 29 Agustos 2026: kullanicinin talebi - "kullanicilar hangi kurum
     turunu en cok ariyor" sorusuna panelde dogrudan cevap (bkz.
     Admin\DashboardController::categoryDemandSummary()) - tahmin degil,
     kurumlarin gercek goruntulenme sayisina gore dagilim.
     6 Eylul 2026: kullanicinin bildirdigi gercek gozlem - "yuzdeler hic
     degismiyor" (TUM ZAMANLAR toplami kullaniliyordu, bkz. controller ayni
     tarihli yorum) - artik SON 30 GUNDEKI GERCEK degisim gosteriliyor.
     Ayrica doldurma cubugu "bg-primary" adinda TANIMLI OLMAYAN bir Tailwind
     rengi kullaniyordu (bkz. site-stats sayfasindaki ayni turden hata) -
     gorunmez kaliyordu, gercek bir hex renge cevrildi. --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-10">
  <h2 class="font-bold text-lg mb-1">Kurum Türüne Göre İlgi</h2>
  @if($categoryDemand['has_trend_data'])
    <p class="text-xs text-gray-500 mb-4">Son 30 günde kazanılan görüntülenmeye göre — tahmin değil, gerçek kullanıcı verisi.</p>
  @else
    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
      30 günlük gerçek trend verisi henüz birikiyor (yaklaşık {{ $categoryDemand['days_until_trend'] }} gün sonra hazır olacak) — o zamana kadar aşağıda kurumların bugüne kadarki TOPLAM görüntülenmesine göre dağılım gösteriliyor, bu yüzden gün gün neredeyse hiç değişmez.
    </p>
  @endif
  <div class="space-y-3">
    @foreach($categoryDemand['rows'] as $row)
      <div>
        <div class="flex items-center justify-between text-sm mb-1">
          <span class="font-semibold text-gray-800">{{ $row['title'] }}</span>
          <span class="font-black text-gray-950">%{{ number_format($row['percent'], 1, ',', '.') }} <span class="font-normal text-gray-400">({{ number_format($row['count'], 0, ',', '.') }} görüntülenme)</span></span>
        </div>
        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
          <div class="h-full rounded-full" style="width: {{ $row['percent'] }}%; background:#0b5d8c;"></div>
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- 6 Eylul 2026: kullanicinin talebi - yukaridaki kart bot dahil TUM
     istekleri sayan views_count'u kullaniyor (kullanicinin kendi eski
     talebi uzerine). Bu YENI, AYRI kart SADECE gercek (bot haric, oturumda
     24 saatte 1 kez) tiklamayi gosterir - bkz. Admin\DashboardController::
     categoryRealClickSummary() ayni tarihli yorum. --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-10" id="en-cok-tiklanan">
  <h2 class="font-bold text-lg mb-1">Gerçek Tıklama (Bot Hariç)</h2>
  <p class="text-xs text-gray-500 mb-4">
    Son 30 gün · yalnızca bilinen arama motoru/SEO botları hariç tutulan, aynı tarayıcının aynı kurumu 24 saatte yalnızca 1 kez saydığı ölçüm
    @if($realClickDemand['tracking_since'])
      · takip başlangıcı: {{ \Illuminate\Support\Carbon::parse($realClickDemand['tracking_since'])->format('d.m.Y') }}
    @endif
  </p>
  @if($realClickDemand['total'] === 0)
    <p class="text-sm text-gray-400">Henüz gerçek tıklama kaydı yok.</p>
  @else
    <div class="space-y-3">
      @foreach($realClickDemand['rows'] as $row)
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="font-semibold text-gray-800">{{ $row['title'] }}</span>
            <span class="font-black text-gray-950">%{{ number_format($row['percent'], 1, ',', '.') }} <span class="font-normal text-gray-400">({{ number_format($row['count'], 0, ',', '.') }} tıklama)</span></span>
          </div>
          <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full rounded-full" style="width: {{ $row['percent'] }}%; background:#1e6f5c;"></div>
          </div>
        </div>
      @endforeach
    </div>
    <div class="mt-5 pt-5 border-t border-gray-100">
      <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <div class="text-xs font-semibold text-gray-500">En çok tıklanan kurumlar</div>
        {{-- 13 Ekim 2026: kullanicinin talebi - "turkiye ve il bazli
             filtreleme olmali, bolum bolum de ayrilmali" - sayfa bu GET
             formuyla kendini ayni filtrelerle yeniden yukler, secim
             degisince otomatik gonderir (JS'siz de calisan native fallback). --}}
        {{-- 13 Ekim 2026: kullanicinin talebi - "filtreleme yapinca sayfanin
             en ustune atmamali, oldugu yerde kalmali". GET formu dogasi
             geregi sayfayi yeniden yukler (bu, native/JS'siz fallback'in
             BEDELI) - ama action'a #en-cok-tiklanan eklenince tarayici
             yukleme sonrasi otomatik bu karta kaydirir, en uste atmaz. --}}
        <form method="GET" action="{{ url()->current() }}#en-cok-tiklanan" class="flex items-center gap-2 flex-wrap">
          <select name="il" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-xs">
            <option value="">Türkiye geneli</option>
            @foreach($realClickCities as $city)
              <option value="{{ $city->id }}" @selected($realClickCityId === $city->id)>{{ $city->name }}</option>
            @endforeach
          </select>
          <select name="bolum" onchange="this.form.submit()" class="border rounded-lg px-2 py-1 text-xs">
            <option value="">Tüm bölümler</option>
            @foreach($realClickSections as $slug => $section)
              <option value="{{ $slug }}" @selected($realClickSection === $slug)>{{ $section['title'] }}</option>
            @endforeach
          </select>
          <noscript><button type="submit" class="border rounded-lg px-2 py-1 text-xs font-semibold">Filtrele</button></noscript>
        </form>
      </div>
      @if($realClickDemand['top_facilities']->isNotEmpty())
        <div class="space-y-1.5">
          @foreach($realClickDemand['top_facilities'] as $i => $f)
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-700"><span class="text-gray-400 font-mono text-xs mr-1">{{ $i + 1 }}.</span>{{ $f->name }}</span>
              <span class="font-black text-gray-950">{{ number_format($f->toplam, 0, ',', '.') }} tıklama</span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-sm text-gray-400">Bu filtreyle eşleşen gerçek tıklama kaydı yok.</p>
      @endif
    </div>
  @endif
</div>

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
