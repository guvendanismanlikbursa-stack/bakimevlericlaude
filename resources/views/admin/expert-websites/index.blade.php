@extends('admin.layout')
@section('title', 'Doktor Siteleri')

@section('content')
{{-- 25 Ekim 2026: kullanıcının talebi - Kurum Siteleri ile aynı yapıda doktor
     siteleri listesi: tek tıkla standart / profesyonel, iki ayrı önizleme,
     paket ve bitiş tarihi filtreleri. Kart düzeni: her ekrana sığar.
     Sınıflar yerine inline stil: yeni Tailwind sınıfları FTP'de derlenmiyor. --}}
@php
  $btn = 'display:inline-block;border:1.5px solid #d6dde0;background:#fff;border-radius:10px;padding:5px 11px;font-weight:700;font-size:13px;text-decoration:none;color:#111827;white-space:nowrap;';
  $sec = 'border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;max-width:100%;';
  $liste = fn (array $p = []) => route('admin.expert-websites.index', $p);
  $kart = 'display:block;text-decoration:none;border-radius:14px;padding:14px 16px;border:1.5px solid #e5e7eb;background:#fff;color:#111827;';
  $sayi = 'display:block;font-size:26px;font-weight:900;line-height:1.1;';
  $etk = 'display:block;font-size:12.5px;font-weight:700;color:#6b7280;margin-top:4px;';
@endphp
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
  <h1 class="text-2xl font-bold">🩺 Doktor Siteleri</h1>
  <span data-live-count style="display:inline-block;background:#111827;color:#fff;border-radius:9999px;font-size:13px;font-weight:700;padding:3px 12px;">{{ number_format($experts->total(), 0, ',', '.') }} doktor bulundu</span>
</div>
<p style="color:#6b7280;font-size:14px;margin-bottom:16px;">Doktor siteleri yalnızca buradan yönetilir. Hukuki nedenle sitelerde hasta yorumu yer almaz.</p>
@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:10px 14px;font-weight:700;margin-bottom:12px;">{{ session('success') }}</div>@endif

<div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:16px;">
  <a href="{{ $liste(['paket' => 'profesyonel']) }}" style="{{ $kart }}border-color:#e9d3ab;background:#fffdf8;"><span style="{{ $sayi }}color:#8a6a35;">{{ $ozet['pro_aktif'] }}</span><span style="{{ $etk }}">★ Profesyonel (aktif)</span></a>
  <a href="{{ $liste(['paket' => 'standart']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#0d6e6a;">{{ $ozet['standart_yayinda'] }}</span><span style="{{ $etk }}">Standart (yayında)</span></a>
  <a href="{{ $liste(['durum' => 'taslak']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#92400e;">{{ $ozet['taslak'] }}</span><span style="{{ $etk }}">Taslak (yayında değil)</span></a>
  <a href="{{ $liste(['paket' => 'bitiyor']) }}" style="{{ $kart }}{{ $ozet['pro_bitiyor'] ? 'border-color:#fca5a5;background:#fff7f7;' : '' }}"><span style="{{ $sayi }}color:#b91c1c;">{{ $ozet['pro_bitiyor'] }}</span><span style="{{ $etk }}">Profesyonel · 30 gün içinde bitiyor</span></a>
  <a href="{{ $liste(['paket' => 'dolmus']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#6b7280;">{{ $ozet['pro_dolmus'] }}</span><span style="{{ $etk }}">Profesyonel · süresi dolmuş</span></a>
</div>

<form method="GET" style="background:#fff;border-radius:14px;padding:14px;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;border:1px solid #f1f5f9;">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Doktor adıyla ara..." data-live-search autocomplete="off" style="{{ $sec }}flex:1;min-width:160px;padding:8px 12px;">
  <select name="durum" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Site durumu: tümü</option>
    <option value="yayinda" @selected(request('durum') === 'yayinda')>Yayında</option>
    <option value="taslak" @selected(request('durum') === 'taslak')>Taslak</option>
    <option value="hic" @selected(request('durum') === 'hic')>Hiç açılmamış</option>
  </select>
  <select name="paket" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Paket: tümü</option>
    <option value="profesyonel" @selected(request('paket') === 'profesyonel')>★ Profesyonel (aktif)</option>
    <option value="standart" @selected(request('paket') === 'standart')>Standart (yayında)</option>
    <option value="bitiyor" @selected(request('paket') === 'bitiyor')>Profesyonel · 30 gün içinde bitiyor</option>
    <option value="dolmus" @selected(request('paket') === 'dolmus')>Profesyonel · süresi dolmuş</option>
  </select>
  <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;flex-wrap:wrap;">Bitiş tarihi
    <input type="date" name="bitis_bas" value="{{ request('bitis_bas') }}" onchange="this.form.submit()" style="{{ $sec }}max-width:150px;">
    –
    <input type="date" name="bitis_son" value="{{ request('bitis_son') }}" onchange="this.form.submit()" style="{{ $sec }}max-width:150px;">
  </label>
  <select name="kendi_sitesi" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Kendi sitesi: tümü</option>
    <option value="yok" @selected(request('kendi_sitesi') === 'yok')>Kendi sitesi yok</option>
    <option value="var" @selected(request('kendi_sitesi') === 'var')>Kendi sitesi var</option>
    <option value="bilinmiyor" @selected(request('kendi_sitesi') === 'bilinmiyor')>Kontrol edilmemiş</option>
  </select>
  <select name="branch" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Tüm branşlar</option>
    @foreach($branches as $b)<option value="{{ $b }}" @selected(request('branch') === $b)>{{ $b }}</option>@endforeach
  </select>
  <select name="city" onchange="this.form.submit()" style="{{ $sec }}">
    <option value="">Tüm iller</option>
    @foreach($cities as $city)<option value="{{ $city->id }}" @selected((string) request('city') === (string) $city->id)>{{ $city->name }}</option>@endforeach
  </select>
  @if(request()->hasAny(['q', 'durum', 'paket', 'kendi_sitesi', 'city', 'branch', 'bitis_bas', 'bitis_son']))
    <a href="{{ $liste() }}" style="{{ $btn }}align-self:center;">Temizle</a>
  @endif
</form>

<div data-live-results>
<div style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;">
  @forelse($experts as $e)
    @php
      $proAcik = $e->site_plan === 'profesyonel' && (! $e->site_pro_bitis || \Illuminate\Support\Carbon::parse($e->site_pro_bitis)->endOfDay()->isFuture());
      $yayinda = $e->site_durum === 'yayinda';
    @endphp
    <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:flex-start;justify-content:space-between;{{ $loop->first ? '' : 'border-top:1px solid #f1f5f9;' }}">
      <div style="min-width:0;flex:1 1 260px;">
        <div style="font-weight:700;color:#111827;overflow-wrap:anywhere;">{{ trim(($e->title ? $e->title.' ' : '').$e->name) }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $e->city?->name }}{{ $e->district ? ' / '.$e->district : '' }} · {{ $e->branch_label ?: $e->branch }}</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;align-items:center;">
          @if(! $e->site_durum)
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#f3f4f6;color:#6b7280;">Bizim site: açılmamış</span>
          @elseif($yayinda)
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#dcfce7;color:#166534;">Bizim site: Yayında{{ $proAcik ? '' : ' (Standart)' }}</span>
          @else
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#fef3c7;color:#92400e;">Bizim site: Taslak</span>
          @endif
          @if($proAcik)<span style="display:inline-block;font-size:11px;font-weight:800;padding:2px 9px;border-radius:9999px;background:#fdf4e3;color:#8a6a35;">★ Profesyonel{{ $e->site_pro_bitis ? ' · '.\Illuminate\Support\Carbon::parse($e->site_pro_bitis)->format('d.m.Y').'’e kadar' : '' }}</span>@endif
          @if($e->website_status)@include('partials.website-status-badge', ['facility' => $e])@else<span style="font-size:11px;color:#9ca3af;">Kendi sitesi: kontrol edilmemiş</span>@endif
        </div>
        @if($e->site_durum)<div style="font-size:12px;color:#6b7280;margin-top:6px;overflow-wrap:anywhere;">{{ $e->site_domain ?: 'bakimevleri.com/dr/'.$e->site_slug }}</div>@endif
        @php $ks = $kartStat[$e->id] ?? null; @endphp
        <div style="display:flex;flex-wrap:wrap;gap:4px 14px;font-size:12.5px;color:#374151;margin-top:6px;"><span style="color:#6b7280;">Doktorlar sayfası kartı (30 gün):</span><span>📋 {{ (int) ($ks->listelenme ?? 0) }} listelenme</span><span>👁 {{ (int) ($ks->acilma ?? 0) }} açılma</span><span>📞 {{ (int) ($ks->arama ?? 0) }} arama</span></div>
        @if($e->site_id)
          @php $st = $siteStat[$e->site_id] ?? null; @endphp
          <div style="display:flex;flex-wrap:wrap;gap:4px 14px;font-size:12.5px;color:#374151;margin-top:6px;"><span style="color:#6b7280;">Son 30 gün:</span><span>👀 {{ (int) ($st->ziyaret ?? 0) }} ziyaret</span><span>💬 {{ (int) ($st->whatsapp ?? 0) }}</span><span>📞 {{ (int) ($st->arama ?? 0) }}</span><span>✉️ {{ (int) ($st->form ?? 0) }} form</span></div>
        @endif
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;align-items:center;">
        <a href="{{ route('admin.experts.website.preview', ['expert' => $e->id, 'tasarim' => 'standart']) }}" target="_blank" rel="noopener" style="{{ $btn }}">👁 Standart önizle</a>
        <a href="{{ route('admin.experts.website.preview', ['expert' => $e->id, 'tasarim' => 'pro']) }}" target="_blank" rel="noopener" style="{{ $btn }}border-color:#e9d3ab;color:#8a6a35;">★ Profesyonel önizle</a>
        <a href="{{ route('admin.experts.website.preview', ['expert' => $e->id, 'tasarim' => 'hareketli']) }}" target="_blank" rel="noopener" style="{{ $btn }}border-color:#c4b5fd;color:#6d28d9;">✨ Hareketli önizle</a>
        <a href="{{ route('admin.experts.website.edit', $e->id) }}" style="{{ $btn }}border-color:#0d6e6a;color:#0d6e6a;">Düzenle</a>
        <a href="{{ route('admin.site-sales.create', ['tur' => 'doktor', 'id' => $e->id, 'plan' => $proAcik ? 'profesyonel' : 'standart']) }}" style="{{ $btn }}">💰 Satış</a>
        @unless($proAcik)
          <form method="POST" action="{{ route('admin.expert-websites.toggle-standard', $e->id) }}" onsubmit="return confirm('{{ $yayinda ? 'Standart site yayından kaldırılsın mı?' : 'Standart site açılıp yayına alınsın mı?' }}')" style="display:inline;margin:0;">
            @csrf
            @if($yayinda)
              <button type="submit" style="{{ $btn }}cursor:pointer;color:#6b7280;">Yayından kaldır</button>
            @else
              <button type="submit" style="{{ $btn }}cursor:pointer;background:#0d6e6a;border-color:#0d6e6a;color:#fff;">Standart siteyi aç</button>
            @endif
          </form>
        @endunless
        <form method="POST" action="{{ route('admin.expert-websites.toggle-pro', $e->id) }}" onsubmit="return confirm('{{ $proAcik ? 'Profesyonel Site kapatılsın mı?' : 'Profesyonel Site 1 yıllığına açılsın ve site yayına alınsın mı?' }}')" style="display:inline;margin:0;">
          @csrf
          @if($proAcik)
            <button type="submit" style="{{ $btn }}cursor:pointer;color:#6b7280;">Profesyoneli kapat</button>
          @else
            <button type="submit" style="{{ $btn }}cursor:pointer;background:#b08d57;border-color:#b08d57;color:#fff;">★ Profesyonel'e geçir</button>
          @endif
        </form>
      </div>
    </div>
  @empty
    <div style="padding:24px;text-align:center;color:#6b7280;">Bu filtreye uyan doktor yok.</div>
  @endforelse
</div>
<div style="margin-top:16px;">{{ $experts->links() }}</div>
</div>

@include('admin.partials.live-search-script')
@endsection
