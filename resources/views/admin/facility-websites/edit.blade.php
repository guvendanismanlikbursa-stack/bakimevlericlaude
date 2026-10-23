@extends('admin.layout')
@section('title', 'Kurum Sitesi')

@section('content')
{{-- 23 Ekim 2026: kullanıcının talebi - admin her kurumun sitesini düzenler,
     yayına alır/kaldırır, adresini ve alan adını belirler, eski hale döner. --}}
@php
  $alanAdlari = ['hero_title' => 'Başlık', 'hero_subtitle' => 'Alt yazı', 'about_text' => 'Hakkımızda', 'hero_image_id' => 'Öne çıkan fotoğraf', 'theme' => 'Renk', 'sections' => 'Bölümler',
    'logo_path' => 'Logo', 'brand_color' => 'Kurum rengi', 'font_style' => 'Yazı stili', 'video_url' => 'Video', 'stats' => 'Rakamlar', 'values_list' => 'Neden biz', 'team' => 'Ekip', 'faqs' => 'SSS', 'announcements' => 'Duyurular'];
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
  <div>
    <h1 class="text-2xl font-bold">Kurum Sitesi</h1>
    <div style="color:#6b7280;font-size:14px;">{{ $facility->name }} · <a href="{{ route('admin.facilities.edit', $facility) }}" style="text-decoration:underline;">Kurum kaydına dön</a></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="{{ url('/siteler/'.$site->slug) }}?onizleme=1" target="_blank" rel="noopener" style="border:2px solid #0d6e6a;color:#0d6e6a;font-weight:800;border-radius:12px;padding:9px 16px;text-decoration:none;">👁 Siteyi önizle</a>
    @unless($site->isPro())<a href="{{ url('/siteler/'.$site->slug) }}?onizleme=1&tasarim=pro" target="_blank" rel="noopener" style="border:2px solid #b08d57;color:#8a6a35;font-weight:800;border-radius:12px;padding:9px 16px;text-decoration:none;">★ Profesyonel tasarımı önizle</a>@endunless
  </div>
</div>

@if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:16px;">{{ session('success') }}</div>@endif

<div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
  @include('themes._shared.kurum-sitesi._form', ['site' => $site, 'facility' => $facility, 'images' => $images, 'action' => route('admin.facility-websites.update', $facility), 'ustAlanlar' => 'admin.facility-websites._ayarlar', 'proAlanlar' => true])
</div>

<div style="margin-top:18px;">@include('themes._shared.kurum-sitesi.pro._rapor', ['site' => $site])</div>

<div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-top:18px;">
  <h2 style="font-size:18px;font-weight:900;margin:0 0 10px;">Değişiklik geçmişi</h2>
  @forelse($revisions as $rev)
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;padding:10px 0;border-top:1px solid #f1f5f9;">
      <div style="font-size:14px;">
        <b>{{ $rev->created_at->format('d.m.Y H:i') }}</b> · {{ $rev->editor_type === 'kurum' ? 'Kurum' : 'Admin' }}: {{ $rev->editor_name ?: '—' }}
        <div style="font-size:12px;color:#6b7280;">Değişen: {{ collect($rev->before)->keys()->map(fn ($k) => $alanAdlari[$k] ?? $k)->implode(', ') }}</div>
      </div>
      <form method="POST" action="{{ route('admin.facility-websites.revert', [$facility, $rev]) }}">
        @csrf
        <button type="submit" style="border:1.5px solid #d6dde0;background:#fff;border-radius:10px;padding:6px 12px;font-weight:700;font-size:13px;cursor:pointer;">Bu değişiklikten önceki hale dön</button>
      </form>
    </div>
  @empty
    <div style="color:#6b7280;font-size:14px;">Henüz değişiklik yok.</div>
  @endforelse
</div>
@endsection
