{{-- 1 Kasım 2026: 7 günlük çalışma saati tablosu; bugünün satırı ve "şu an açık/kapalı" tarayıcıda hesaplanır (bkz. saat-script). --}}
@if($saatSatirlari = $kayit->openingHoursRows())
  <div class="js-saat-kutu" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;{{ $stil ?? '' }}">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
      <div style="font-weight:900;color:#0f172a;font-size:15.5px;">🕒 Çalışma saatleri</div>
      <span class="js-saat-durum" style="display:none;font-size:12px;font-weight:900;padding:3px 10px;border-radius:999px;"></span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
      @foreach($saatSatirlari as $gun => $saat)
        <tr class="js-saat-satir" data-gun="{{ $loop->index }}" data-saat="{{ $saat }}" style="border-top:1px solid #f3f4f6;">
          <td style="padding:6px 8px;font-weight:700;color:#374151;">{{ $gun }}</td>
          <td style="padding:6px 8px;text-align:right;color:{{ $saat === 'Kapalı' ? '#9ca3af' : '#111827' }};font-weight:600;">{{ $saat }}</td>
        </tr>
      @endforeach
    </table>
    <p style="margin-top:8px;font-size:12px;color:#6b7280;">{{ $not ?? 'Saatler değişebilir; gitmeden önce arayarak teyit etmenizi öneririz.' }}</p>
  </div>
@endif
