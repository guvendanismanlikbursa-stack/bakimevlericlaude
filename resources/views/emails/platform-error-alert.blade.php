<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Platformda bir hata tespit edildi</h2>
  <p><strong>Ne hatası:</strong> {{ $title }}</p>
  <p><strong>Kaynak:</strong> {{ $source }}</p>
  <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; white-space:pre-wrap; font-family:monospace; font-size:13px; color:#374151;">{{ $errorMessage }}</div>
  <p style="margin-top:20px;"><a href="{{ route('admin.platform-errors.index') }}" style="background:#dc2626;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Admin Panelinde Görüntüle</a></p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta, otomatik hata izleme sistemi tarafından gönderilmiştir.</p>
</body>
</html>
