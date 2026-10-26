@extends('admin.layout')
@section('title', 'Site İstatistikleri')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sitelere Giriş Sayıları & Aile Konumları</h1>

<h2 class="font-bold text-lg mb-3">Sitelere Giriş Sayıları</h2>
<p class="text-xs text-gray-400 mb-4">"Giriş" burada bir tarayıcı oturumunun o markanın sitesine bir gün içindeki ilk ziyaretini ifade eder (aynı oturumda tekrar sayılmaz).</p>
<div class="grid md:grid-cols-3 gap-4 mb-10">
  @foreach($visitStats as $slug => $stat)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <div class="font-black text-gray-950 mb-3">{{ $stat['name'] }}</div>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div><div class="text-gray-500 text-xs">Bugün</div><div class="font-black text-lg">{{ number_format($stat['today'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 7 gün</div><div class="font-black text-lg">{{ number_format($stat['last_7_days'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 30 gün</div><div class="font-black text-lg">{{ number_format($stat['last_30_days'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 3 ay</div><div class="font-black text-lg">{{ number_format($stat['last_90_days'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 6 ay</div><div class="font-black text-lg">{{ number_format($stat['last_180_days'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 1 yıl</div><div class="font-black text-lg">{{ number_format($stat['last_365_days'], 0, ',', '.') }}</div></div>
        <div><div class="text-gray-500 text-xs">Toplam</div><div class="font-black text-lg">{{ number_format($stat['all_time'], 0, ',', '.') }}</div></div>
      </div>
    </div>
  @endforeach
</div>

{{-- 26 Ekim 2026: kullanıcının talebi - "bot mu insan mı" ayrıntılı tablo, dönem seçmeli. --}}
@php
  $markaAd = ['bakimevleri' => 'bakimevleri.com', 'bakimevibul' => 'bakimevibul.com', 'bakimeviara' => 'bakimeviara.com'];
  $turAd = \App\Support\TrafikAnaliz::TURLER;
  $turRenk = ['insan' => '#15803d', 'supheli' => '#b45309', 'googlebot' => '#2563eb', 'bing' => '#0891b2', 'yz' => '#7c3aed', 'diger_bot' => '#6b7280'];
  $th = 'padding:8px 10px;text-align:right;font-size:12px;color:#6b7280;font-weight:800;white-space:nowrap;border-bottom:1px solid #e5e7eb;';
  $td = 'padding:8px 10px;text-align:right;font-size:14px;white-space:nowrap;border-bottom:1px solid #f1f5f9;';
  $toplamlar = [];
@endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10" id="ziyaretci-turleri">
  <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center;">
    <h2 class="font-bold text-lg">Ziyaretçi Türleri — Bot mu, İnsan mı?</h2>
    <div style="display:inline-flex;flex-wrap:wrap;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
      @foreach($donemler as $k => $ad)
        <a href="{{ request()->fullUrlWithQuery(['donem' => $k]) }}#ziyaretci-turleri" style="padding:6px 11px;font-size:13px;font-weight:700;text-decoration:none;{{ $donem === (string) $k ? 'background:#0b5d8c;color:#fff;' : 'color:#4b5563;' }}{{ $loop->first ? '' : 'border-left:1px solid #e5e7eb;' }}">{{ $ad }}</a>
      @endforeach
    </div>
  </div>
  <p style="font-size:12.5px;color:#6b7280;margin:8px 0 12px;line-height:1.55;">
    Sunucunun kendi erişim kayıtlarından, yalnızca <b>sayfa</b> istekleri (resim, stil, yazı tipi hariç). <b>Gerçek kişi (muhtemel):</b> tarayıcı ile gelip sayfanın resim ve stil dosyalarını da yükleyen ziyaretçi.
    <b>Şüpheli tarayıcı:</b> kendini tarayıcı gibi gösterip yalnızca sayfa metnini çeken ziyaretçi; büyük kısmı tarayıcı taklidi yapan botlardır. Botlar kendi adlarıyla ayrılır.
    Parantez içi: günlük farklı IP sayılarının toplamı.
    @if($trafikBaslangic) Kayıt başlangıcı: {{ \Illuminate\Support\Carbon::parse($trafikBaslangic)->format('d.m.Y') }} — bu tarihten önceki günler için ayrım yok. @endif
  </p>
  @if(! $trafik)
    <p style="font-size:14px;color:#9ca3af;">Bu dönem için henüz kayıt yok.</p>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;min-width:820px;">
        <thead><tr><th style="{{ $th }}text-align:left;">Site</th>@foreach($turAd as $k => $ad)<th style="{{ $th }}color:{{ $turRenk[$k] }};">{{ $ad }}</th>@endforeach<th style="{{ $th }}">Toplam</th><th style="{{ $th }}">Gerçek kişi payı</th></tr></thead>
        <tbody>
          @foreach($markaAd as $m => $ad)
            @php $satir = $trafik[$m] ?? []; $top = collect($satir)->sum('istek'); @endphp
            <tr>
              <td style="{{ $td }}text-align:left;font-weight:800;">{{ $ad }}</td>
              @foreach($turAd as $k => $x)
                @php $d = $satir[$k] ?? ['istek' => 0, 'ip' => 0]; $toplamlar[$k]['istek'] = ($toplamlar[$k]['istek'] ?? 0) + $d['istek']; $toplamlar[$k]['ip'] = ($toplamlar[$k]['ip'] ?? 0) + $d['ip']; @endphp
                <td style="{{ $td }}"><b>{{ number_format($d['istek'], 0, ',', '.') }}</b> <span style="color:#9ca3af;font-size:12px;">({{ number_format($d['ip'], 0, ',', '.') }})</span></td>
              @endforeach
              <td style="{{ $td }}font-weight:800;">{{ number_format($top, 0, ',', '.') }}</td>
              <td style="{{ $td }}font-weight:800;color:#15803d;">%{{ $top ? round(($satir['insan']['istek'] ?? 0) / $top * 100) : 0 }}</td>
            </tr>
          @endforeach
          @php $genel = collect($toplamlar)->sum('istek'); @endphp
          <tr style="background:#f8fafc;">
            <td style="{{ $td }}text-align:left;font-weight:900;">Toplam</td>
            @foreach($turAd as $k => $x)<td style="{{ $td }}font-weight:900;">{{ number_format($toplamlar[$k]['istek'] ?? 0, 0, ',', '.') }} <span style="color:#9ca3af;font-size:12px;font-weight:600;">(%{{ $genel ? round(($toplamlar[$k]['istek'] ?? 0) / $genel * 100) : 0 }})</span></td>@endforeach
            <td style="{{ $td }}font-weight:900;">{{ number_format($genel, 0, ',', '.') }}</td>
            <td style="{{ $td }}font-weight:900;color:#15803d;">%{{ $genel ? round(($toplamlar['insan']['istek'] ?? 0) / $genel * 100) : 0 }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    @if($trafikGunluk->count() > 1)
      <details style="margin-top:14px;">
        <summary style="cursor:pointer;font-weight:800;font-size:14px;color:#111827;">Gün gün döküm (3 site toplamı)</summary>
        <div style="overflow-x:auto;margin-top:8px;">
          <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead><tr><th style="{{ $th }}text-align:left;">Gün</th>@foreach($turAd as $k => $ad)<th style="{{ $th }}color:{{ $turRenk[$k] }};">{{ $ad }}</th>@endforeach<th style="{{ $th }}">Toplam</th></tr></thead>
            <tbody>
              @foreach($trafikGunluk->sortKeysDesc() as $g => $rows)
                @php $gTop = $rows->sum('istek'); $byTur = $rows->keyBy('tur'); @endphp
                <tr><td style="{{ $td }}text-align:left;">{{ \Illuminate\Support\Carbon::parse($g)->format('d.m.Y') }}</td>@foreach($turAd as $k => $x)<td style="{{ $td }}">{{ number_format((int) ($byTur[$k]->istek ?? 0), 0, ',', '.') }}</td>@endforeach<td style="{{ $td }}font-weight:800;">{{ number_format($gTop, 0, ',', '.') }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </details>
    @endif
  @endif
</div>

@if($dailySeries->isNotEmpty())
  {{-- 5 Ekim 2026: kullanicinin talebi - eski cubuk grafik "amator" bulundu,
       Google Search Console tarzi (duz cizgi, altinda hafif dolgu, hover'da
       tooltip) bir cizgi grafigine + 1/7/30 gun arasinda gecis yapan bir
       anahtara cevrildi. Sunucudan hep 30 gunluk veri gelir, 1/7 gunluk
       gorunum JS tarafinda son N gunu dilimleyerek (ekstra istek atmadan,
       aninda) uretilir. Chart.js admin panelinde ilk kez kullaniliyor,
       jsDelivr CDN'den yuklenir (CSP yok, sorun degil). --}}
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-4">
      <h2 class="font-bold text-lg">Giriş Trendi</h2>
      <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm" id="trend-range-toggle">
        <button type="button" data-range="1" class="trend-range-btn px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-50 transition">1 gün</button>
        <button type="button" data-range="7" class="trend-range-btn px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-50 border-l border-gray-200 transition">7 gün</button>
        <button type="button" data-range="30" class="trend-range-btn px-3 py-1.5 font-semibold border-l border-gray-200 transition" style="background:#0b5d8c;color:#fff;">30 gün</button>
        <button type="button" data-range="90" class="trend-range-btn px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-50 border-l border-gray-200 transition">90 gün</button>
        <button type="button" data-range="180" class="trend-range-btn px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-50 border-l border-gray-200 transition">6 ay</button>
        <button type="button" data-range="365" class="trend-range-btn px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-50 border-l border-gray-200 transition">1 yıl</button>
      </div>
    </div>
    <div style="height: 260px;">
      <canvas id="trend-chart"></canvas>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      var dailyData = @json($dailySeries->map(fn ($rows, $date) => ['date' => $date, 'total' => $rows->sum('count')])->values());
      var aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
      function formatDate(d) {
        var parts = d.split('-');
        return parseInt(parts[2], 10) + ' ' + aylar[parseInt(parts[1], 10) - 1];
      }
      function formatNum(n) {
        return new Intl.NumberFormat('tr-TR').format(n);
      }

      var ctx = document.getElementById('trend-chart').getContext('2d');
      var gradient = ctx.createLinearGradient(0, 0, 0, 260);
      gradient.addColorStop(0, 'rgba(11,93,140,0.22)');
      gradient.addColorStop(1, 'rgba(11,93,140,0.01)');

      var chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Giriş',
            data: [],
            borderColor: '#0b5d8c',
            backgroundColor: gradient,
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointBackgroundColor: '#0b5d8c',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#111827',
              padding: 10,
              titleFont: { weight: 'bold' },
              callbacks: { label: function (c) { return 'Giriş: ' + formatNum(c.parsed.y); } },
            },
          },
          scales: {
            x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 }, maxRotation: 0, autoSkip: true } },
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 11 }, callback: formatNum } },
          },
        },
      });

      function render(rangeDays) {
        var slice = dailyData.slice(-rangeDays);
        chart.data.labels = slice.map(function (d) { return formatDate(d.date); });
        chart.data.datasets[0].data = slice.map(function (d) { return d.total; });
        chart.data.datasets[0].pointRadius = rangeDays <= 7 ? 3 : 0;
        chart.update();
      }

      render(30);

      document.querySelectorAll('.trend-range-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.querySelectorAll('.trend-range-btn').forEach(function (b) {
            b.style.background = '';
            b.style.color = '';
            b.classList.add('text-gray-600');
          });
          btn.style.background = '#0b5d8c';
          btn.style.color = '#fff';
          btn.classList.remove('text-gray-600');
          render(parseInt(btn.dataset.range, 10));
        });
      });
    })();
  </script>
@endif

<h2 class="font-bold text-lg mb-3">Kayıt Olan Ailelerin Konumları</h2>
<div class="grid sm:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Toplam Aile</div>
    <div class="text-2xl font-black mt-1">{{ number_format($totalFamilies, 0, ',', '.') }}</div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Konum Paylaşan</div>
    <div class="text-2xl font-black mt-1">{{ number_format($withLocation, 0, ',', '.') }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($totalFamilies, 0, ',', '.') }}</span></div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">E-posta Doğrulayan</div>
    <div class="text-2xl font-black mt-1">{{ number_format($verifiedCount, 0, ',', '.') }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($totalFamilies, 0, ',', '.') }}</span></div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Farklı Şehir</div>
    <div class="text-2xl font-black mt-1">{{ $cityCounts->count() }}</div>
  </div>
</div>

@if($cityCounts->isNotEmpty())
  @php $maxCity = $cityCounts->max('total'); @endphp
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10">
    <h3 class="font-bold mb-3">Şehre Göre Dağılım</h3>
    <div class="space-y-2">
      @foreach($cityCounts as $row)
        <div class="flex items-center gap-3">
          <span class="w-28 text-sm font-semibold text-gray-700 truncate">{{ $row->signup_city_name }}</span>
          <span class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden"><span class="block h-full bg-primary rounded-full" style="width: {{ max(4, round($row->total / $maxCity * 100)) }}%"></span></span>
          <span class="w-10 text-right text-sm font-black">{{ $row->total }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif

<form method="GET" class="mb-4 flex gap-3">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="has_location" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Konum Farketmez</option>
    <option value="1" @selected(request('has_location')==='1')>Sadece konum paylaşanlar</option>
    <option value="0" @selected(request('has_location')==='0')>Sadece konum paylaşmayanlar</option>
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($families as $family)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $family->name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $family->email }} · {{ $brands[$family->registered_brand]['name'] ?? $family->registered_brand }} · {{ $family->signup_city_name ?? '—' }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Rıza: {{ $family->consent_accepted_at?->format('d.m.Y H:i') ?? '—' }} · Kayıt: {{ $family->created_at->format('d.m.Y H:i') }}</div>
        <div class="mt-1">
          @if($family->hasVerifiedEmail())
            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">E-posta Doğrulandı</span>
          @else
            <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">E-posta Bekliyor</span>
          @endif
        </div>
      </div>
      <div class="flex items-center gap-3 flex-wrap justify-end">
        @if($family->signup_lat && $family->signup_lng)
          <a href="https://www.google.com/maps?q={{ $family->signup_lat }},{{ $family->signup_lng }}" target="_blank" class="text-primary text-sm font-semibold whitespace-nowrap">Haritada gör →</a>
        @endif
        <a href="{{ route('admin.family-users.show', $family) }}" class="text-primary text-sm font-semibold whitespace-nowrap">Detay →</a>
      </div>
    </div>
  @empty
    <div class="p-8 text-center text-gray-500">Kayıt bulunamadı.</div>
  @endforelse
</div>
<div class="mt-6">{{ $families->links() }}</div>
@endsection
