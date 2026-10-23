{{-- 23 Ekim 2026: kullanıcının talebi - kurum sitelerinin ayrıntılı özeti
     (admin ana sayfası ve Kurum Siteleri sayfası). Kartlar ilgili filtreli
     listeye gider. Sınıflar yerine inline stil: Tailwind FTP'de derlenmiyor. --}}
@php
  $oz = \App\Models\FacilityWebsite::summary();
  $liste = fn (array $p = []) => route('admin.facility-websites.index', $p);
  $kart = 'display:block;text-decoration:none;border-radius:14px;padding:14px 16px;border:1.5px solid #e5e7eb;background:#fff;color:#111827;';
  $sayi = 'display:block;font-size:26px;font-weight:900;line-height:1.1;';
  $etk = 'display:block;font-size:12.5px;font-weight:700;color:#6b7280;margin-top:4px;';
@endphp
<div style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:22px;">
  <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:8px;margin-bottom:12px;">
    <h2 style="font-size:18px;font-weight:900;margin:0;color:#111827;">🌐 Kurum Siteleri</h2>
    @if($baglanti ?? true)<a href="{{ $liste() }}" style="font-size:14px;font-weight:700;color:#0d6e6a;">Tüm siteler ve ayrıntılı rapor →</a>@endif
  </div>
  <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
    <a href="{{ $liste(['paket' => 'profesyonel']) }}" style="{{ $kart }}border-color:#e9d3ab;background:#fffdf8;"><span style="{{ $sayi }}color:#8a6a35;">{{ $oz['pro_aktif'] }}</span><span style="{{ $etk }}">★ Profesyonel (aktif)</span></a>
    <a href="{{ $liste(['paket' => 'standart']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#0d6e6a;">{{ $oz['standart_yayinda'] }}</span><span style="{{ $etk }}">Standart (yayında)</span></a>
    <a href="{{ $liste(['durum' => 'taslak']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#92400e;">{{ $oz['taslak'] }}</span><span style="{{ $etk }}">Taslak (yayında değil)</span></a>
    <a href="{{ $liste(['paket' => 'bitiyor']) }}" style="{{ $kart }}{{ $oz['pro_bitiyor'] ? 'border-color:#fca5a5;background:#fff7f7;' : '' }}"><span style="{{ $sayi }}color:#b91c1c;">{{ $oz['pro_bitiyor'] }}</span><span style="{{ $etk }}">Profesyonel · 30 gün içinde bitiyor</span></a>
    <a href="{{ $liste(['paket' => 'dolmus']) }}" style="{{ $kart }}"><span style="{{ $sayi }}color:#6b7280;">{{ $oz['pro_dolmus'] }}</span><span style="{{ $etk }}">Profesyonel · süresi dolmuş</span></a>
  </div>
  <div style="margin-top:14px;border-top:1px solid #f1f5f9;padding-top:12px;">
    <div style="font-size:13px;font-weight:800;color:#374151;margin-bottom:8px;">Son 30 gün (tüm siteler, kurumun kendi ve yönetici girişleri hariç)</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px 22px;font-size:15px;color:#111827;">
      <span>👀 <b>{{ number_format($oz['son30']['ziyaret'], 0, ',', '.') }}</b> ziyaret</span>
      <span>💬 <b>{{ number_format($oz['son30']['whatsapp'], 0, ',', '.') }}</b> WhatsApp</span>
      <span>📞 <b>{{ number_format($oz['son30']['arama'], 0, ',', '.') }}</b> telefon</span>
      <span>✉️ <b>{{ number_format($oz['son30']['form'], 0, ',', '.') }}</b> form</span>
    </div>
    @if($oz['en_cok']->isNotEmpty())
      <div style="font-size:13px;font-weight:800;color:#374151;margin:12px 0 6px;">En çok ziyaret alan siteler</div>
      @foreach($oz['en_cok'] as $s)
        <div style="display:flex;flex-wrap:wrap;gap:4px 14px;align-items:center;padding:6px 0;border-bottom:1px solid #f8fafc;font-size:14px;">
          <a href="{{ route('admin.facility-websites.edit', $s->facility_id) }}" style="font-weight:700;color:#111827;flex:1 1 200px;min-width:0;overflow-wrap:anywhere;">{{ $s->ad }}</a>
          <span style="white-space:nowrap;">👀 {{ (int) $s->ziyaret }}</span><span style="white-space:nowrap;">💬 {{ (int) $s->whatsapp }}</span><span style="white-space:nowrap;">📞 {{ (int) $s->arama }}</span><span style="white-space:nowrap;">✉️ {{ (int) $s->form }}</span>
        </div>
      @endforeach
    @endif
  </div>
</div>
