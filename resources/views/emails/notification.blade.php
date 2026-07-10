<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937; max-width:600px; margin:0 auto;">
  <h2>{{ $title }}</h2>
  @if($bodyText)
    <p style="color:#374151;">{{ $bodyText }}</p>
  @endif

  @if($actionUrl)
    <p style="margin-top:20px;"><a href="{{ $actionUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">İlgili Sayfaya Git</a></p>
  @endif

  <p style="color:#6b7280;font-size:12px;margin-top:28px;">Bu, hesabınızla ilgili otomatik bir bildirimdir.</p>
</body>
</html>
