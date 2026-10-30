@extends('admin.layout')
@section('title', 'Demo Linki')

@section('content')
{{-- 29 Ekim 2026: kullanıcının talebi - demo siteyi WhatsApp'tan gönderme sayfası.
     Inline stil: yeni Tailwind sınıfları FTP deploy'da derlenmiyor. --}}
@php
  $btn = 'display:inline-flex;align-items:center;gap:6px;border:2px solid #d6dde0;border-radius:12px;padding:9px 16px;font-weight:800;text-decoration:none;cursor:pointer;background:#fff;color:#111827;font-size:14px;';
@endphp
<div style="max-width:760px;">
  <h1 class="text-2xl font-bold">Demo Linki</h1>
  <div style="color:#6b7280;font-size:14px;margin-bottom:16px;">{{ $ad }} · {{ $tur === 'doktor' ? 'Doktor sitesi' : 'Kurum sitesi' }}</div>

  @if(session('success'))<div style="background:#ecfdf5;color:#065f46;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:16px;">{{ session('success') }}</div>@endif

  {{-- 30 Ekim 2026: kullanıcının talebi - habersiz link gönderilmez. --}}
  <div style="background:#fffbeb;border:1.5px solid #fcd34d;color:#92400e;border-radius:12px;padding:12px 16px;font-weight:700;margin-bottom:16px;">⚠️ Önce telefonla görüşüp izin alın, linki sonra gönderin. Tanımadığı numaradan habersiz gelen link dolandırıcılık sanılabilir.</div>

  <form method="GET" action="{{ $formAdresi }}" style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px;">
    <div style="font-weight:800;margin-bottom:8px;">Gönderilecek tasarımlar</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px 18px;margin-bottom:14px;">
      @foreach(\App\Models\ExpertWebsite::DEMO_TASARIMLAR as $t => $etiket)
        <label style="display:inline-flex;align-items:center;gap:6px;font-weight:700;cursor:pointer;"><input type="checkbox" name="tasarim[]" value="{{ $t }}" @checked(in_array($t, $secili, true)) style="width:18px;height:18px;"> {{ $etiket }}</label>
      @endforeach
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
      <button type="submit" name="islem" value="link" style="{{ $btn }}">🔄 Seçimi uygula</button>
      @if($waLink)
        <button type="submit" name="islem" value="whatsapp" formtarget="_blank" onclick="return confirm('Bu kişiyle önce telefonda görüşüp link göndermek için izin aldınız mı?')" style="{{ $btn }}background:#25d366;border-color:#25d366;color:#fff;">💬 WhatsApp'tan gönder ({{ $telefon }})</button>
      @else
        <span style="align-self:center;color:#b45309;font-weight:700;font-size:14px;">Kayıtta cep numarası yok ({{ $telefon ?: 'telefon boş' }}) — mesajı kopyalayıp kendiniz gönderin.</span>
      @endif
    </div>
  </form>

  <div style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px;">
    <div style="font-weight:800;margin-bottom:8px;">Linkler</div>
    @foreach($linkler as $t => $link)
      <div style="margin-bottom:10px;">
        <div style="font-size:13px;color:#6b7280;font-weight:700;">{{ \App\Models\ExpertWebsite::DEMO_TASARIMLAR[$t] }}</div>
        <div style="display:flex;gap:6px;align-items:center;">
          <input type="text" readonly value="{{ $link }}" onclick="this.select()" style="flex:1;min-width:0;border:1.5px solid #d6dde0;border-radius:10px;padding:8px 10px;font-size:13px;">
          <a href="{{ $link }}" target="_blank" rel="noopener" style="{{ $btn }}padding:7px 12px;">Aç</a>
        </div>
      </div>
    @endforeach
    <div style="font-size:13px;color:#6b7280;margin-top:6px;">Linkler {{ $site->demo_until->format('d.m.Y') }} tarihine kadar açık. Google listelemez; site yayında sayılmaz.</div>
  </div>

  <div style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px;">
    <div style="font-weight:800;margin-bottom:8px;">Hazır mesaj</div>
    <textarea id="demo-mesaj" readonly rows="{{ 6 + count($linkler) * 3 }}" style="display:block;width:100%;border:1.5px solid #d6dde0;border-radius:10px;padding:10px 12px;font:inherit;font-size:14px;">{{ $mesaj }}</textarea>
    <button type="button" onclick="var m=document.getElementById('demo-mesaj');m.select();navigator.clipboard&&navigator.clipboard.writeText(m.value);this.textContent='✓ Kopyalandı';" style="{{ $btn }}margin-top:8px;">📋 Mesajı kopyala</button>
  </div>

  <form method="POST" action="{{ $yenileAdresi }}" onsubmit="return confirm('Yeni link oluşturulsun mu? Daha önce gönderilen linkler açılmaz olur.')" style="margin:0;">
    @csrf
    <button type="submit" style="{{ $btn }}color:#6b7280;">♻️ Linki yenile (eskilerini kapat)</button>
  </form>
</div>
@endsection
