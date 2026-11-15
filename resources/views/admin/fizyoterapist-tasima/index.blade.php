@extends('admin.layout')
@section('title', 'Fizyoterapist Taşıması')

@section('content')
{{-- 15 Kasım 2026: kullanıcının talebi - bireysel fizyoterapistler Uzmanlar'a taşındı; bu sayfadan tek tuşla kurum haline
     geri döndürülebilir ya da yeniden taşınabilir (bkz. Admin\FizyoterapistTasimaController). --}}
@php
  $kart = 'background:#fff;border:1px solid #eef0f3;border-radius:14px;padding:18px 20px;margin-bottom:20px;';
  $girdi = 'border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:14px;width:140px;';
@endphp
<h1 class="text-2xl font-bold mb-2">Fizyoterapist Taşıması</h1>
<p style="font-size:14px;color:#4b5563;line-height:1.6;margin-bottom:18px;max-width:820px;">
  Bireysel fizyoterapistler kurum listesinden <b>Uzmanlar</b> bölümüne (doktorlar gibi: reklam yok, sade bilgi, doğrudan arama) taşındı.
  Kurum kayıtları silinmedi; fotoğraf ve yorumları saklanıyor. Buradan tek tuşla eski haline döndürebilir ya da yeniden taşıyabilirsiniz.
</p>

@if(! $hazir)
  <div style="{{ $kart }}background:#fffbeb;border-color:#fde68a;">Veritabanı güncellemesi henüz yapılmamış.</div>
@else
  <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px;">
    <div style="{{ $kart }}margin:0;"><div style="font-size:12.5px;font-weight:800;color:#6b7280;">Uzmanlar bölümünde</div><div style="font-size:28px;font-weight:900;color:#0b5d8c;">{{ number_format($durum['uzmanda'], 0, ',', '.') }}</div><div style="font-size:12px;color:#9ca3af;">fizyoterapist</div></div>
    <div style="{{ $kart }}margin:0;"><div style="font-size:12.5px;font-weight:800;color:#6b7280;">Kurum olarak kalan</div><div style="font-size:28px;font-weight:900;color:#15803d;">{{ number_format($durum['kurumda'], 0, ',', '.') }}</div><div style="font-size:12px;color:#9ca3af;">sahiplenilmiş ya da taşınmamış</div></div>
    <div style="{{ $kart }}margin:0;"><div style="font-size:12.5px;font-weight:800;color:#6b7280;">Yönlendirilen eski adres</div><div style="font-size:28px;font-weight:900;color:#374151;">{{ number_format($durum['yonlendirme'], 0, ',', '.') }}</div><div style="font-size:12px;color:#9ca3af;">eski kurum sayfası → yeni sayfa</div></div>
  </div>
  @if($durum['son_tasima'])
    <p style="font-size:13px;color:#6b7280;margin:-8px 0 18px;">Taşıma tarihi: {{ \Illuminate\Support\Carbon::parse($durum['son_tasima'])->format('d.m.Y H:i') }}</p>
  @endif

  <div style="{{ $kart }}border-color:#fecaca;">
    <h2 style="font-size:17px;font-weight:800;margin:0 0 6px;">↩ Kurum haline geri döndür</h2>
    <p style="font-size:13.5px;color:#4b5563;line-height:1.6;margin:0 0 12px;">
      Uzmanlar bölümündeki taşınmış fizyoterapistler silinir, kurum kayıtları fotoğraf ve yorumlarıyla eski haline döner,
      eski kurum adresleri yeniden açılır. Uzmanlar bölümünde bu fizyoterapistler için sonradan yapılan değişiklikler kaybolur.
    </p>
    <form method="POST" action="{{ route('admin.fizyo-tasima.geri-al') }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;"
          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Geri alınıyor, 1-2 dk sürebilir…';">
      @csrf
      <label style="font-size:13px;color:#374151;">Onay için <b>GERİ AL</b> yazın:</label>
      <input name="onay" required autocomplete="off" style="{{ $girdi }}">
      <button type="submit" style="background:#b91c1c;color:#fff;border:0;border-radius:8px;padding:9px 14px;font-weight:800;font-size:14px;cursor:pointer;" @disabled(! $durum['uzmanda'])>Kuruma geri döndür</button>
    </form>
  </div>

  <div style="{{ $kart }}">
    <h2 style="font-size:17px;font-weight:800;margin:0 0 6px;">→ Uzmanlar bölümüne taşı</h2>
    <p style="font-size:13.5px;color:#4b5563;line-height:1.6;margin:0 0 12px;">
      Kurum olarak duran bireysel fizyoterapistleri (sahiplenilmiş olanlar hariç) Uzmanlar bölümüne taşır, eski adreslerini yeni sayfaya yönlendirir.
    </p>
    <form method="POST" action="{{ route('admin.fizyo-tasima.tasi') }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;"
          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Taşınıyor, 1-2 dk sürebilir…';">
      @csrf
      <label style="font-size:13px;color:#374151;">Onay için <b>TAŞI</b> yazın:</label>
      <input name="onay" required autocomplete="off" style="{{ $girdi }}">
      <button type="submit" style="background:#0b5d8c;color:#fff;border:0;border-radius:8px;padding:9px 14px;font-weight:800;font-size:14px;cursor:pointer;">Uzmanlara taşı</button>
    </form>
  </div>

  <div style="{{ $kart }}">
    <h2 style="font-size:17px;font-weight:800;margin:0 0 6px;">🗄 Kalıcı yedekler</h2>
    <p style="font-size:13.5px;color:#4b5563;line-height:1.6;margin:0 0 10px;">
      Taşımadan hemen önce alınan tam veritabanı yedeği otomatik temizliğe girmeyen ayrı bir klasörde ve bilgisayarınızda (C:\Yedekler\bakimevleri_kalici) saklanıyor.
      Yukarıdaki düğme yeterli değilse bu yedekten tam geri dönüş yapılabilir.
    </p>
    @forelse($yedekler as $y)
      <div style="font-size:13.5px;padding:6px 0;border-top:1px solid #f1f5f9;"><b>{{ $y['ad'] }}</b> · {{ $y['boyut'] }} MB · {{ $y['tarih'] }}</div>
    @empty
      <div style="font-size:13.5px;color:#9ca3af;">Kalıcı yedek bulunamadı.</div>
    @endforelse
  </div>
@endif
@endsection
