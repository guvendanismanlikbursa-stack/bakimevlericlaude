<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>"{{ $registration->name }}" kurum kayıt başvurunuz onaylanmadı</h2>
  <p>Merhaba {{ $registration->applicant_name }},</p>
  <p>"{{ $registration->name }}" için gönderdiğiniz kurum kayıt başvurusunu inceledik, şu an onaylayamadık.</p>
  @if($registration->admin_note)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 16px;margin:16px 0;color:#991b1b;">
      {{ $registration->admin_note }}
    </div>
  @endif
  <p>Bilgilerinizi düzelterek veya eksik belge/açıklama ekleyerek yeniden başvuru yapabilirsiniz.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
