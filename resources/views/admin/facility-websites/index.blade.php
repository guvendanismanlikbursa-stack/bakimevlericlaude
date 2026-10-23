@extends('admin.layout')
@section('title', 'Kurum Siteleri')

@section('content')
{{-- 23 Ekim 2026: kullanıcının talebi - bütün kurum sitelerinin tek listesi:
     yayında / taslak / hiç açılmamış, kurumun kendi sitesi olan / olmayan,
     tek tıkla önizleme ve düzenleme. Sınıflar yerine inline stil: yeni
     Tailwind sınıfları FTP deploy'da derlenmiyor. --}}
@php
  $durumRozet = [
    'yayinda' => ['Yayında', 'background:#dcfce7;color:#166534;'],
    'taslak' => ['Taslak', 'background:#fef3c7;color:#92400e;'],
  ];
  $btn = 'display:inline-block;border:1.5px solid #d6dde0;background:#fff;border-radius:10px;padding:5px 11px;font-weight:700;font-size:13px;text-decoration:none;color:#111827;white-space:nowrap;';
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
  <div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <h1 class="text-2xl font-bold">🌐 Kurum Siteleri</h1>
      <span data-live-count style="display:inline-block;background:#111827;color:#fff;border-radius:9999px;font-size:13px;font-weight:700;padding:3px 12px;">{{ number_format($facilities->total(), 0, ',', '.') }} kurum bulundu</span>
    </div>
    <p style="color:#6b7280;font-size:14px;margin-top:4px;">Yayında: <b>{{ $sayilar['yayinda'] }}</b> · Taslak: <b>{{ $sayilar['taslak'] }}</b> · ★ Profesyonel: <b>{{ $sayilar['profesyonel'] }}</b> · Henüz açılmamış siteler "Önizle"ye basınca taslak olarak oluşturulur.</p>
  </div>
  <form method="POST" action="{{ route('admin.facility-websites.price') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#fffdf8;border:1.5px solid #e9d3ab;border-radius:12px;padding:8px 10px;">
    @csrf
    <label for="pro-fiyat" style="font-size:13px;font-weight:800;color:#8a6a35;">★ Profesyonel Site fiyatı</label>
    <input id="pro-fiyat" name="fiyat" value="{{ $proFiyat }}" maxlength="80" style="border:1px solid #d1d5db;border-radius:8px;padding:6px 10px;font-size:14px;width:190px;max-width:100%;">
    <button type="submit" style="{{ $btn }}">Kaydet</button>
  </form>
</div>
@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:10px 14px;font-weight:700;margin-bottom:12px;">{{ session('success') }}</div>@endif

<form method="GET" style="background:#fff;border-radius:14px;padding:14px;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;border:1px solid #f1f5f9;">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara..." data-live-search autocomplete="off" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 12px;font-size:14px;flex:1;min-width:160px;">
  <select name="durum" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;">
    <option value="">Site durumu: tümü</option>
    <option value="yayinda" @selected(request('durum') === 'yayinda')>Yayında</option>
    <option value="taslak" @selected(request('durum') === 'taslak')>Taslak</option>
    <option value="hic" @selected(request('durum') === 'hic')>Hiç açılmamış</option>
  </select>
  <select name="paket" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;">
    <option value="">Paket: tümü</option>
    <option value="profesyonel" @selected(request('paket') === 'profesyonel')>★ Profesyonel Site</option>
  </select>
  <select name="kendi_sitesi" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;">
    <option value="">Kendi sitesi: tümü</option>
    <option value="yok" @selected(request('kendi_sitesi') === 'yok')>Kendi sitesi yok</option>
    <option value="var" @selected(request('kendi_sitesi') === 'var')>Kendi sitesi var</option>
    <option value="bilinmiyor" @selected(request('kendi_sitesi') === 'bilinmiyor')>Kontrol edilmemiş</option>
  </select>
  <select name="city" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;">
    <option value="">Tüm iller</option>
    @foreach($cities as $city)
      <option value="{{ $city->id }}" @selected((string) request('city') === (string) $city->id)>{{ $city->name }}</option>
    @endforeach
  </select>
  @if(request()->hasAny(['q', 'durum', 'kendi_sitesi', 'city', 'paket']))
    <a href="{{ route('admin.facility-websites.index') }}" style="{{ $btn }}align-self:center;">Temizle</a>
  @endif
</form>

<div data-live-results>
{{-- 23 Ekim 2026: kullanıcının talebi - "her ölçüye ayarlı olsun, sağa sola
     kaydırmadan tüm içeriği göreyim" (Kurumlar listesiyle aynı yaklaşım):
     tablo yerine her kurum bir kart; ekran daraldıkça içerik aşağı doğru
     satır kırar, yana taşmaz. --}}
<div style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;">
  @forelse($facilities as $f)
    @php $proAcik = $f->site_plan === 'profesyonel' && (! $f->site_pro_bitis || \Illuminate\Support\Carbon::parse($f->site_pro_bitis)->endOfDay()->isFuture()); @endphp
    <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:flex-start;justify-content:space-between;{{ $loop->first ? '' : 'border-top:1px solid #f1f5f9;' }}">
      <div style="min-width:0;flex:1 1 260px;">
        <div style="font-weight:700;color:#111827;overflow-wrap:anywhere;">{{ $f->name }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $f->city?->name }} · {{ $f->category?->name }}@if($f->is_claimed) · <span style="color:#0d6e6a;font-weight:700;">Sahipli</span>@endif</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;align-items:center;">
          @if($f->site_durum)
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;{{ $durumRozet[$f->site_durum][1] ?? '' }}">Bizim site: {{ $durumRozet[$f->site_durum][0] ?? $f->site_durum }}</span>
          @else
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;background:#f3f4f6;color:#6b7280;">Bizim site: açılmamış</span>
          @endif
          @if($proAcik)<span style="display:inline-block;font-size:11px;font-weight:800;padding:2px 9px;border-radius:9999px;background:#fdf4e3;color:#8a6a35;">★ Profesyonel{{ $f->site_pro_bitis ? ' · '.\Illuminate\Support\Carbon::parse($f->site_pro_bitis)->format('d.m.Y').'’e kadar' : '' }}</span>@endif
          @if($f->website_status)@include('partials.website-status-badge', ['facility' => $f])@else<span style="font-size:11px;color:#9ca3af;">Kendi sitesi: kontrol edilmemiş</span>@endif
        </div>
        @if($f->site_durum)<div style="font-size:12px;color:#6b7280;margin-top:6px;overflow-wrap:anywhere;">{{ $f->site_domain ?: 'bakimevleri.com/siteler/'.$f->site_slug }}</div>@endif
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;align-items:center;">
        <a href="{{ route('admin.facility-websites.preview', $f->id) }}" target="_blank" rel="noopener" style="{{ $btn }}">👁 Önizle</a>
        <a href="{{ route('admin.facility-websites.edit', $f->id) }}" style="{{ $btn }}border-color:#0d6e6a;color:#0d6e6a;">Düzenle</a>
        <form method="POST" action="{{ route('admin.facility-websites.toggle-pro', $f->id) }}" onsubmit="return confirm('{{ $proAcik ? 'Profesyonel Site kapatılsın mı? Site hazır tasarıma döner.' : 'Profesyonel Site 1 yıllığına açılsın ve site yayına alınsın mı?' }}')" style="display:inline;margin:0;">
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
    <div style="padding:24px;text-align:center;color:#6b7280;">Bu filtreye uyan kurum yok.</div>
  @endforelse
</div>
<div style="margin-top:16px;">{{ $facilities->links() }}</div>
</div>

@include('admin.partials.live-search-script')
@endsection
