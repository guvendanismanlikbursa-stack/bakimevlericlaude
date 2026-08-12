<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>"{{ $claim->facility->name }}" için sahiplenme başvurunuz onaylanmadı</h2>
  <p>Merhaba {{ $claim->applicant_name }},</p>
  <p>"{{ $claim->facility->name }}" kurumunu sahiplenme başvurunuzu inceledik, şu an onaylayamadık.</p>
  @if($claim->admin_note)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 16px;margin:16px 0;color:#991b1b;">
      {{ $claim->admin_note }}
    </div>
  @endif
  <p>Bilgilerin yanlış olduğunu düşünüyorsanız veya ek belge/açıklama ile tekrar başvurmak isterseniz, kurum sayfasından yeniden başvuru yapabilirsiniz.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
