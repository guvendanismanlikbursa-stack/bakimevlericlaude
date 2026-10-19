{{-- 19 Ekim 2026: kurumun internet sitesi durumu rozeti (CRM + admin kurum listesi).
     Sınıflar yerine inline stil: yeni Tailwind sınıfları FTP deploy'da derlenmiyor. --}}
@php
  $wsStatus = $facility->website_status;
  $wsStyles = [
    'yok' => 'background:#fee2e2;color:#991b1b;',
    'sosyal' => 'background:#fef3c7;color:#92400e;',
    'rehber' => 'background:#fef3c7;color:#92400e;',
    'var' => 'background:#dcfce7;color:#166534;',
  ];
@endphp
@if($wsStatus)
  <span title="{{ $facility->website_source === 'pazarlamaci' ? 'Pazarlamacı görüşmesinde işaretlendi' : 'Google Haritalar kaydından kontrol edildi' }}{{ $facility->website_checked_at ? ' · '.$facility->website_checked_at->format('d.m.Y') : '' }}"
        style="display:inline-block;font-size:11px;font-weight:600;padding:2px 8px;border-radius:9999px;{{ $wsStyles[$wsStatus] ?? '' }}">🌐 {{ \App\Models\Facility::WEBSITE_STATUSES[$wsStatus] ?? $wsStatus }}@if($wsStatus === 'var' && $facility->website_url) · <a href="{{ $facility->website_url }}" target="_blank" rel="noopener nofollow" style="text-decoration:underline;color:inherit;">aç</a>@endif</span>
@endif
