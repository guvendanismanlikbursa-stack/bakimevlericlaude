{{-- 1 Kasım 2026: kart üzerinde tek satırlık çalışma saati + "şu an açık/kapalı" (bkz. saat-script). --}}
@if($saatOzeti = $kayit->openingHoursSummary())
  <span class="js-kart-saat" data-saatler="{{ json_encode(array_values($kayit->openingHoursRows()), JSON_UNESCAPED_UNICODE) }}" style="display:block;background:#ecfeff;border:1px solid #67e8f9;border-radius:8px;padding:3px 8px;margin-top:6px;font-size:12px;line-height:1.5;font-weight:800;color:#0e4f5c;white-space:normal;">
    <span class="js-kart-saat-durum" style="display:none;font-size:10.5px;font-weight:900;padding:0 7px;border-radius:999px;white-space:nowrap;margin-right:5px;"></span><span aria-hidden="true">🕒</span> {{ $saatOzeti }}
  </span>
@endif
