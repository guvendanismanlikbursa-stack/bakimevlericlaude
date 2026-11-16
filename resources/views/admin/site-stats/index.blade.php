@extends('admin.layout')
@section('title', 'İstatistikler')

@section('content')
{{-- 15 Kasım 2026: kullanıcının talebi - "bütün istatistikleri adminde tek sayfada topla" + "bizim sayaçlarımıza güvenmiyorum".
     Ziyaret rakamları Google Analytics'ten; bot güvenliği ve aile konumları kendi kayıtlarımızdan (Analytics bunları göremez).
     Eski sayaç kartları ve giriş trendi kaldırıldı. --}}
<h1 class="text-2xl font-bold mb-6">İstatistikler</h1>

@include('admin.site-stats._analytics')

<details style="background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:16px 20px;margin-bottom:22px;" @if(request()->has('donem')) open @endif>
  <summary style="cursor:pointer;font-size:17px;font-weight:800;color:#111827;">🛡 Güvenlik — Bot İzleme <span style="font-size:12px;font-weight:700;color:#6b7280;">(sunucu kayıtlarımız; Analytics botları göremez)</span></summary>
  <div style="margin-top:16px;">
{{-- 1 Kasım 2026: saatlik bot gözcüsü - bugünkü şüpheli ağlar, tek dokunuşla engelleme (mobil dahil). --}}
@php $btn = 'display:inline-block;border:0;border-radius:8px;padding:7px 12px;font-size:13px;font-weight:800;cursor:pointer;white-space:nowrap;'; @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10" id="supheli-aglar">
  <h2 class="font-bold text-lg">🔎 Şüpheli Ağlar — Bugün</h2>
  <p style="font-size:12.5px;color:#6b7280;margin:6px 0 12px;line-height:1.55;">
    Saatte bir güncellenir{{ $gozcuSon ? ' (son: '.\Illuminate\Support\Carbon::parse($gozcuSon)->format('H:i').')' : '' }}. Firmalar, sitemizden en çok sayfa isteyen sunucu sahipleridir.
    <b>Şüpheli</b>: tarayıcı taklidi yapan, <b>Bot</b>: kendini bot olarak tanıtan istekler. <b>İnsan</b> sütunu yüksekse engellemeyin; gerçek aileler o ağdan geliyordur.
    Olağan dışı durumda size e-posta ve bildirim gelir.
  </p>
  @if($supheliFirmalar->isEmpty())
    <p style="font-size:14px;color:#9ca3af;">Bugün şüpheli yoğunluk yok (ya da gözcü henüz çalışmadı).</p>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;min-width:760px;">
        <thead><tr>
          @foreach(['Firma (AS)', 'Şüpheli', 'Bot', 'İnsan', 'Farklı IP', 'Engellenen', ''] as $b)
            <th style="padding:8px 10px;text-align:{{ $loop->first ? 'left' : 'right' }};font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;white-space:nowrap;">{{ $b }}</th>
          @endforeach
        </tr></thead>
        <tbody>
          @foreach($supheliFirmalar as $f)
            <tr>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13.5px;">
                <b>{{ $f->firma ?: 'Bilinmeyen firma' }}</b> <span style="color:#9ca3af;">{{ $f->asn ? 'AS'.$f->asn : $f->ag }}</span>
                <div style="font-size:11.5px;color:#9ca3af;">{{ $f->ag_sayisi }} ağ · {{ collect(explode(',', (string) $f->markalar))->filter()->unique()->map(fn ($m) => $m.'.com')->join(', ') }}</div>
              </td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:800;color:#b45309;">{{ number_format($f->supheli, 0, ',', '.') }}</td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;">{{ number_format($f->bot, 0, ',', '.') }}</td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;color:#15803d;font-weight:700;">{{ number_format($f->insan, 0, ',', '.') }}</td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;">{{ number_format($f->ip, 0, ',', '.') }}</td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;color:#b91c1c;">{{ number_format($f->engellenen, 0, ',', '.') }}</td>
              <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;">
                @if($f->engelli)
                  <span style="{{ $btn }}background:#dcfce7;color:#166534;cursor:default;">Engelli ✓</span>
                @elseif($f->korumali)
                  <span style="{{ $btn }}background:#f3f4f6;color:#6b7280;cursor:default;" title="{{ $f->korumali }}">Korumalı</span>
                @else
                  <form method="POST" action="{{ route('admin.bot-engel.store') }}" style="display:inline;" onsubmit="return confirm(@js(($f->firma ?: 'Bu ağ').' engellensin mi?'.($f->insan > ($f->supheli + $f->bot) * 0.2 ? ' DİKKAT: bu ağdan gerçek kişiler de geliyor.' : '')))">
                    @csrf
                    @if($f->asn)<input type="hidden" name="asn" value="{{ $f->asn }}">@else<input type="hidden" name="ag" value="{{ $f->ag }}">@endif
                    <input type="hidden" name="firma" value="{{ $f->firma }}">
                    <button type="submit" style="{{ $btn }}background:#dc2626;color:#fff;">Engelle</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- 1 Kasım 2026: engelli ağ listesi - panelden eklenenler kaldırılabilir; elle IP / AS numarasıyla engelleme. --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10" id="engelli-aglar">
  <h2 class="font-bold text-lg">⛔ Engelli Ağlar</h2>
  <form method="POST" action="{{ route('admin.bot-engel.store') }}" style="display:flex;flex-wrap:wrap;gap:8px;margin:10px 0 14px;" onsubmit="return confirm('Bu ağı sahibi olan firmanın bütün sunucularıyla birlikte engellemek istiyor musunuz?')">
    @csrf
    <input name="sorgu" required placeholder="IP adresi veya AS numarası (ör. 185.216.75.10 / AS51167)" style="flex:1 1 240px;min-width:0;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:14px;">
    <button type="submit" style="{{ $btn }}background:#dc2626;color:#fff;">Engelle</button>
  </form>
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;min-width:560px;">
      <tbody>
        @foreach((array) config('bot_kalkani.engelli_aglar', []) as $g)
          <tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13.5px;font-weight:700;">{{ $g['ad'] }}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12.5px;color:#6b7280;">{{ count($g['v4'] ?? []) }} adres bloğu · kalıcı liste (1 Kasım 2026)</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;"><span style="{{ $btn }}background:#f3f4f6;color:#6b7280;cursor:default;">Kalıcı</span></td>
          </tr>
        @endforeach
        @foreach($engelliListe as $e)
          <tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13.5px;font-weight:700;">{{ $e->firma }}{{ $e->asn ? ' (AS'.$e->asn.')' : '' }}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12.5px;color:#6b7280;">{{ count(json_decode((string) $e->v4, true) ?: []) }} adres bloğu · {{ $e->ekleyen }} · {{ \Illuminate\Support\Carbon::parse($e->created_at)->format('d.m.Y H:i') }}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;">
              <form method="POST" action="{{ route('admin.bot-engel.destroy', $e->id) }}" style="display:inline;" onsubmit="return confirm(@js($e->firma.' için engel kaldırılsın mı?'))">
                @csrf
                <button type="submit" style="{{ $btn }}background:#f3f4f6;color:#374151;border:1px solid #d1d5db;">Kaldır</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- 1 Kasım 2026: bot kalkanının engelledikleri (son 7 gün). --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10" id="engellenen-botlar">
  <h2 class="font-bold text-lg">🛡 Engellenen Botlar — Son 7 Gün</h2>
  <p style="font-size:12.5px;color:#6b7280;margin:6px 0 12px;line-height:1.55;">
    Veri kazıyan şüpheli botlar siteye sokulmaz. <b>Engelli ağ</b>: kazıyıcıların kiraladığı sunucu ağları (ör. Contabo) tamamen kapalı.
    <b>Hız sınırı</b>: 10 dakikada {{ config('bot_kalkani.ip_siniri') ?: 150 }}'den fazla sayfa isteyen IP ya da {{ config('bot_kalkani.ag_siniri') ?: 600 }}'den fazla isteyen ağ 1 saat bekletilir.
    Arama motorları (Google, Bing vb.) hiç engellenmez.
    @if($botEngelGunluk->isNotEmpty()) Günlük engellenen istek: @foreach($botEngelGunluk as $g => $n){{ \Illuminate\Support\Carbon::parse($g)->format('d.m') }}: <b>{{ number_format($n, 0, ',', '.') }}</b>@if(! $loop->last) · @endif @endforeach @endif
  </p>
  @if($botEngel->isEmpty())
    <p style="font-size:14px;color:#9ca3af;">Son 7 günde engellenen bot yok.</p>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;min-width:720px;">
        <thead><tr>
          <th style="padding:8px 10px;text-align:left;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;">IP / Ağ</th>
          <th style="padding:8px 10px;text-align:left;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;">Neden</th>
          <th style="padding:8px 10px;text-align:left;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;">Site</th>
          <th style="padding:8px 10px;text-align:right;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;">Engellenen istek</th>
          <th style="padding:8px 10px;text-align:right;font-size:12px;color:#6b7280;font-weight:800;border-bottom:1px solid #e5e7eb;">Son deneme</th>
        </tr></thead>
        <tbody>
          @foreach($botEngel as $b)
            <tr>
              <td style="padding:7px 10px;font-size:13.5px;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $b->anahtar }}</td>
              <td style="padding:7px 10px;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ ['liste' => 'Engelli ağ', 'hiz-ip' => 'Hız sınırı (IP)', 'hiz-ag' => 'Hız sınırı (ağ)', 'kotu-bot' => 'İstenmeyen bot'][$b->neden] ?? $b->neden }}</td>
              <td style="padding:7px 10px;font-size:13px;border-bottom:1px solid #f1f5f9;color:#6b7280;">{{ collect(explode(',', (string) $b->markalar))->filter()->map(fn ($m) => $m.'.com')->join(', ') }}</td>
              <td style="padding:7px 10px;font-size:13.5px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:800;color:#b91c1c;">{{ number_format($b->sayi, 0, ',', '.') }}</td>
              <td style="padding:7px 10px;font-size:13px;border-bottom:1px solid #f1f5f9;text-align:right;color:#6b7280;white-space:nowrap;">{{ $b->son ? \Illuminate\Support\Carbon::parse($b->son)->format('d.m H:i') : '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($botEngel->hasPages())<div class="mt-4">{{ $botEngel->links() }}</div>@endif
  @endif
</div>

{{-- 26 Ekim 2026: kullanıcının talebi - "bot mu insan mı" ayrıntılı tablo, dönem seçmeli. --}}
@php
  $markaAd = ['bakimevleri' => 'bakimevleri.com', 'bakimevibul' => 'bakimevibul.com', 'bakimeviara' => 'bakimeviara.com'];
  $turAd = array_merge(\App\Support\TrafikAnaliz::TURLER, ['insan' => 'İnsan']);
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
    <b style="color:#15803d;">Gerçek ziyaretçi</b> sütunu kişi sayısıdır (üstteki kartlarla ve grafikle aynı rakam). Sağdaki sütunlar kişi değil, sunucuya gelen <b>sayfa açılışı</b> sayısıdır: bir kişi 5 sayfa gezerse 5 açılış olur.
    <b>Şüpheli tarayıcı</b> = kendini tarayıcı gibi gösteren botlar.
    @if($trafikBaslangic) Sayfa kayıtları {{ \Illuminate\Support\Carbon::parse($trafikBaslangic)->format('d.m.Y') }} tarihinde başladı. @endif
  </p>
  @if(! $trafik)
    <p style="font-size:14px;color:#9ca3af;">Bu dönem için henüz kayıt yok.</p>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;min-width:820px;">
        <thead>
          <tr><th style="{{ $th }}border-bottom:0;"></th><th style="{{ $th }}border-bottom:0;"></th><th colspan="{{ count($turAd) + 2 }}" style="{{ $th }}text-align:center;color:#374151;background:#f8fafc;">Sayfa açılışları (kişi değil)</th></tr>
          <tr><th style="{{ $th }}text-align:left;">Site</th><th style="{{ $th }}color:#15803d;background:#f0fdf4;">Gerçek ziyaretçi (kişi)</th>@foreach($turAd as $k => $ad)<th style="{{ $th }}color:{{ $turRenk[$k] }};">{{ $ad }}</th>@endforeach<th style="{{ $th }}">Toplam</th><th style="{{ $th }}">İnsan payı</th></tr>
        </thead>
        <tbody>
          @foreach($markaAd as $m => $ad)
            @php $satir = $trafik[$m] ?? []; $top = collect($satir)->sum('istek'); @endphp
            <tr>
              <td style="{{ $td }}text-align:left;font-weight:800;">{{ $ad }}</td>
              <td style="{{ $td }}font-weight:900;color:#15803d;background:#f0fdf4;">{{ number_format($gercekDonem[$m] ?? 0, 0, ',', '.') }}</td>
              @foreach($turAd as $k => $x)
                @php $d = $satir[$k] ?? ['istek' => 0, 'ip' => 0]; $toplamlar[$k]['istek'] = ($toplamlar[$k]['istek'] ?? 0) + $d['istek']; $toplamlar[$k]['ip'] = ($toplamlar[$k]['ip'] ?? 0) + $d['ip']; @endphp
                <td style="{{ $td }}">{{ number_format($d['istek'], 0, ',', '.') }}</td>
              @endforeach
              <td style="{{ $td }}font-weight:800;">{{ number_format($top, 0, ',', '.') }}</td>
              <td style="{{ $td }}font-weight:800;color:#15803d;">%{{ $top ? round(($satir['insan']['istek'] ?? 0) / $top * 100) : 0 }}</td>
            </tr>
          @endforeach
          @php $genel = collect($toplamlar)->sum('istek'); @endphp
          <tr style="background:#f8fafc;">
            <td style="{{ $td }}text-align:left;font-weight:900;">Toplam</td>
            <td style="{{ $td }}font-weight:900;color:#15803d;background:#f0fdf4;">{{ number_format(array_sum($gercekDonem), 0, ',', '.') }}</td>
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
            <thead><tr><th style="{{ $th }}text-align:left;">Gün</th><th style="{{ $th }}color:#15803d;">Gerçek ziyaretçi (kişi)</th>@foreach($turAd as $k => $ad)<th style="{{ $th }}color:{{ $turRenk[$k] }};">{{ $ad }}</th>@endforeach<th style="{{ $th }}">Toplam</th></tr></thead>
            <tbody>
              @foreach($trafikGunluk->sortKeysDesc() as $g => $rows)
                @php $gTop = $rows->sum('istek'); $byTur = $rows->keyBy('tur'); @endphp
                <tr><td style="{{ $td }}text-align:left;">{{ \Illuminate\Support\Carbon::parse($g)->format('d.m.Y') }}</td><td style="{{ $td }}font-weight:800;color:#15803d;">{{ number_format($gercekGunluk[$g] ?? 0, 0, ',', '.') }}</td>@foreach($turAd as $k => $x)<td style="{{ $td }}">{{ number_format((int) ($byTur[$k]->istek ?? 0), 0, ',', '.') }}</td>@endforeach<td style="{{ $td }}font-weight:800;">{{ number_format($gTop, 0, ',', '.') }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </details>
    @endif
  @endif
</div>
  </div>
</details>

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
