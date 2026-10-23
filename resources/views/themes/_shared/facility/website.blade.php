@extends('layouts.brand')
@section('title', 'Web Sitem')
@section('robots_meta', 'noindex,nofollow')
@section('content')
@include('themes._shared.partials.facility-panel-header', ['title' => 'Web Sitem'])
{{-- 23 Ekim 2026: kullanıcının talebi - kurum panelinde "Web Sitem".
     Yayında değilse: sitesi olmayan kuruma "siteniz hazır", sitesi olan
     kuruma "ikinci siteniz" teşviki gösterilir. --}}
@php
  $onizleme = $site->isLive() && ! $site->custom_domain ? $site->publicUrl() : url('/siteler/'.$site->slug).'?onizleme=1';
  $kendiSitesiVar = $facility->website_status === 'var';
@endphp
<div style="max-width:960px;margin:0 auto;padding:32px 16px 48px;display:grid;gap:20px;">
  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:12px 16px;font-weight:700;">{{ session('success') }}</div>@endif

  <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:2px solid {{ $site->isLive() ? '#86efac' : '#e5e7eb' }};">
    @if($site->isLive())
      <div style="font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#15803d;">✓ Siteniz yayında</div>
      <div style="font-size:20px;font-weight:900;color:#111827;margin-top:4px;word-break:break-all;"><a href="{{ $site->publicUrl() }}" target="_blank" rel="noopener" style="color:#0d6e6a;">{{ $site->publicUrl() }}</a></div>
      <p style="color:#4b5563;margin:8px 0 0;">Aşağıdan yaptığınız değişiklikler sitenize hemen yansır. Fotoğraf, hizmet, menü ve telefon bilgileri <a href="{{ brand_route('facility.profile.edit') }}" style="color:#0d6e6a;font-weight:700;">profil sayfanızdan</a> güncellenir.</p>
    @elseif($hasSitePackage)
      <div style="font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#b45309;">Siteniz hazırlanıyor</div>
      <p style="color:#374151;margin:8px 0 0;">Üyeliğiniz internet sitesini kapsıyor. Ekibimiz sitenizi kontrol edip yayına alacak. Bu sırada aşağıdan başlığınızı ve yazılarınızı düzenleyebilir, önizleyebilirsiniz.</p>
    @elseif($kendiSitesiVar)
      <div style="font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#0d6e6a;">İkinci siteniz hazır</div>
      <div style="font-size:20px;font-weight:900;color:#111827;margin-top:4px;">Bir siteniz daha olsun, daha çok aileye ulaşın</div>
      <p style="color:#374151;margin:8px 0 0;">Mevcut sitenize ek olarak bu ikinci site, kurumunuzu Google'da farklı bir adresten de buldurur. Aileler sizi iki ayrı yoldan bulur; gelen talepler doğrudan bu panele düşer. Bilgilerinizden hazırladığımız sitenizi aşağıdan önizleyin.</p>
      <a href="{{ brand_route('facility.packages.index') }}" style="display:inline-block;margin-top:12px;background:#0d6e6a;color:#fff;font-weight:800;border-radius:12px;padding:10px 18px;text-decoration:none;">Standart veya Premium üyelikle yayına alın</a>
    @else
      <div style="font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#0d6e6a;">İnternet siteniz hazır</div>
      <div style="font-size:20px;font-weight:900;color:#111827;margin-top:4px;">Kurumunuzun bilgilerinden sitenizi hazırladık</div>
      <p style="color:#374151;margin:8px 0 0;">Fotoğraflarınız, hizmetleriniz, telefonunuz ve adresinizle hazırlanmış sitenizi önizleyin. Başlığını ve yazılarını aşağıdan kendiniz düzenleyebilirsiniz. Yayına almak için Standart veya Premium üyelik yeterli; alan adı, barındırma ve güvenlik sertifikası dahildir.</p>
      <a href="{{ brand_route('facility.packages.index') }}" style="display:inline-block;margin-top:12px;background:#0d6e6a;color:#fff;font-weight:800;border-radius:12px;padding:10px 18px;text-decoration:none;">Üyelik paketlerini inceleyin</a>
    @endif
    <div style="margin-top:14px;">
      <a href="{{ $onizleme }}" target="_blank" rel="noopener" style="display:inline-block;border:2px solid #0d6e6a;color:#0d6e6a;font-weight:800;border-radius:12px;padding:9px 16px;text-decoration:none;">👁 Sitemi {{ $site->isLive() ? 'görüntüle' : 'önizle' }}</a>
    </div>
  </div>

  <div style="background:#fff;border-radius:18px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
    <h2 style="font-size:18px;font-weight:900;color:#111827;margin:0 0 14px;">Sitemi düzenle</h2>
    @include('themes._shared.kurum-sitesi._form', ['site' => $site, 'facility' => $facility, 'images' => $images, 'action' => brand_route('facility.website.update')])
  </div>
</div>
@endsection
