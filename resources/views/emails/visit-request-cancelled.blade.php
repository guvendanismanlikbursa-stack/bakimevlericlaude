<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>"{{ $visitRequest->facility->name ?? 'Kurum' }}" için talebiniz iptal edildi</h2>
  <p>Merhaba {{ $visitRequest->full_name }},</p>
  <p>"{{ $visitRequest->facility->name ?? 'İlgili kurum' }}" için gönderdiğiniz talebi inceledik, bu talebi kapattık.</p>
  <p>Hâlâ ilgileniyorsanız kurum sayfasından yeniden talep oluşturabilir veya kurumla doğrudan iletişime geçebilirsiniz.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
