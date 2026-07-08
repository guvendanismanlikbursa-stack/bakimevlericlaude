<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>"{{ $registration->name }}" kurum kaydı başvurunuzda düzeltme gerekiyor</h2>
  <p>Merhaba {{ $registration->applicant_name }},</p>
  <p>Başvurunuzu inceledik, yayına alabilmemiz için aşağıdaki düzeltmeyi yapmanız gerekiyor:</p>
  <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;margin:16px 0;color:#92400e;">
    {{ $adminNote }}
  </div>
  <p>Aşağıdaki bağlantıdan başvurunuzu düzenleyip tekrar gönderebilirsiniz:</p>
  <p><a href="{{ $editUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Başvurumu Düzenle</a></p>
  <p style="color:#6b7280;font-size:12px;">Bu bağlantı 14 gün süreyle geçerlidir.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
