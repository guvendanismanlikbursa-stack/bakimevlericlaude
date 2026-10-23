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
    <p style="color:#6b7280;font-size:14px;margin-top:4px;">Yayında: <b>{{ $sayilar['yayinda'] }}</b> · Taslak: <b>{{ $sayilar['taslak'] }}</b> · Henüz açılmamış siteler "Önizle"ye basınca taslak olarak oluşturulur.</p>
  </div>
</div>

<form method="GET" style="background:#fff;border-radius:14px;padding:14px;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;border:1px solid #f1f5f9;">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Kurum adıyla ara..." data-live-search autocomplete="off" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 12px;font-size:14px;flex:1;min-width:160px;">
  <select name="durum" onchange="this.form.submit()" style="border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;">
    <option value="">Site durumu: tümü</option>
    <option value="yayinda" @selected(request('durum') === 'yayinda')>Yayında</option>
    <option value="taslak" @selected(request('durum') === 'taslak')>Taslak</option>
    <option value="hic" @selected(request('durum') === 'hic')>Hiç açılmamış</option>
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
  @if(request()->hasAny(['q', 'durum', 'kendi_sitesi', 'city']))
    <a href="{{ route('admin.facility-websites.index') }}" style="{{ $btn }}align-self:center;">Temizle</a>
  @endif
</form>

<div data-live-results>
<div style="background:#fff;border-radius:14px;border:1px solid #f1f5f9;overflow-x:auto;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="background:#f9fafb;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;">
        <th style="padding:10px 12px;">Kurum</th>
        <th style="padding:10px 12px;">Konum</th>
        <th style="padding:10px 12px;">Kendi sitesi</th>
        <th style="padding:10px 12px;">Bizim site</th>
        <th style="padding:10px 12px;"></th>
      </tr>
    </thead>
    <tbody>
    @forelse($facilities as $f)
      <tr style="border-top:1px solid #f1f5f9;">
        <td style="padding:10px 12px;">
          <div style="font-weight:700;">{{ $f->name }}</div>
          <div style="font-size:12px;color:#6b7280;">{{ $f->category?->name }}@if($f->is_claimed) · <span style="color:#0d6e6a;font-weight:700;">Sahipli</span>@endif</div>
        </td>
        <td style="padding:10px 12px;color:#4b5563;">{{ $f->city?->name }}</td>
        <td style="padding:10px 12px;">
          @if($f->website_status)@include('partials.website-status-badge', ['facility' => $f])@else<span style="font-size:12px;color:#9ca3af;">Kontrol edilmemiş</span>@endif
        </td>
        <td style="padding:10px 12px;">
          @if($f->site_durum)
            <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;border-radius:9999px;{{ $durumRozet[$f->site_durum][1] ?? '' }}">{{ $durumRozet[$f->site_durum][0] ?? $f->site_durum }}</span>
            <div style="font-size:12px;color:#6b7280;margin-top:3px;">{{ $f->site_domain ?: '/siteler/'.$f->site_slug }}</div>
          @else
            <span style="font-size:12px;color:#9ca3af;">Açılmamış</span>
          @endif
        </td>
        <td style="padding:10px 12px;text-align:right;">
          <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
            <a href="{{ route('admin.facility-websites.preview', $f->id) }}" target="_blank" rel="noopener" style="{{ $btn }}">👁 Önizle</a>
            <a href="{{ route('admin.facility-websites.edit', $f->id) }}" style="{{ $btn }}border-color:#0d6e6a;color:#0d6e6a;">Düzenle</a>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="5" style="padding:24px;text-align:center;color:#6b7280;">Bu filtreye uyan kurum yok.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:16px;">{{ $facilities->links() }}</div>
</div>

@include('admin.partials.live-search-script')
@endsection
