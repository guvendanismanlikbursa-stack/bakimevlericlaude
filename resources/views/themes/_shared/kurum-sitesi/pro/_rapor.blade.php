{{-- 23 Ekim 2026: kurum sitesi raporu - son 30 gün ve son 6 ay (ziyaret,
     WhatsApp, arama, form). Kurumun kendi ve adminin bakışları sayılmaz. --}}
@php
  $rapor = $site->statsReport();
  $kalemler = ['ziyaret' => ['Ziyaret', '👀'], 'whatsapp' => ['WhatsApp tıklaması', '💬'], 'arama' => ['Telefon tıklaması', '📞'], 'form' => ['Form talebi', '✉️']];
@endphp
<div style="background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
  <h2 style="font-size:18px;font-weight:900;margin:0 0 4px;color:#111827;">Site raporu</h2>
  <div style="font-size:13px;color:#6b7280;margin-bottom:14px;">Son 30 gün. Kurumun kendi ve yöneticilerin girişleri sayılmaz.</div>
  <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
    @foreach($kalemler as $k => [$etiket, $ikon])
      <div style="background:#f8fafc;border-radius:12px;padding:14px;">
        <div style="font-size:13px;color:#6b7280;font-weight:700;">{{ $ikon }} {{ $etiket }}</div>
        <div style="font-size:28px;font-weight:900;color:#111827;">{{ number_format($rapor['son30'][$k], 0, ',', '.') }}</div>
      </div>
    @endforeach
  </div>
  <div style="margin-top:16px;border-top:1px solid #f1f5f9;">
    @foreach($rapor['aylar'] as $ay)
      <div style="display:flex;flex-wrap:wrap;gap:4px 18px;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;">
        <b style="min-width:120px;color:#111827;">{{ $ay['ay'] }}</b>
        @foreach($kalemler as $k => [$etiket, $ikon])
          <span title="{{ $etiket }}" style="white-space:nowrap;color:#374151;">{{ $ikon }} {{ number_format($ay[$k], 0, ',', '.') }}</span>
        @endforeach
      </div>
    @endforeach
    <div style="font-size:12px;color:#6b7280;margin-top:8px;">👀 Ziyaret · 💬 WhatsApp · 📞 Telefon · ✉️ Form</div>
  </div>
</div>
