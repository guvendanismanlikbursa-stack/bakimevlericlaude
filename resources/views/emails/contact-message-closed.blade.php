<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Mesajınız kapatıldı</h2>
  <p>Merhaba {{ $contactMessage->name }},</p>
  <p>"{{ $contactMessage->subject ?: 'İletişim' }}" konulu mesajınızı inceledik, ek bir işlem gerektirmediğine karar verip kapattık.</p>
  <p>Sorunuz devam ediyorsa bize tekrar yazabilirsiniz.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
