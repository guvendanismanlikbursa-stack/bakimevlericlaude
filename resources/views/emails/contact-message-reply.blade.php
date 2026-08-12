<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Mesajınıza yanıt geldi</h2>
  <p>Sayın {{ $contactMessage->name }}, {{ $brandName }} üzerinden gönderdiğiniz mesaja yanıt verildi:</p>
  <div style="background:#f3f4f6;border-radius:8px;padding:16px;margin:16px 0;white-space:pre-line;">{{ $contactMessage->admin_reply }}</div>
  <p style="color:#6b7280;font-size:13px;">Gönderdiğiniz orijinal mesaj:</p>
  <div style="border-left:3px solid #d1d5db;padding-left:12px;color:#6b7280;font-size:13px;white-space:pre-line;">{{ $contactMessage->message }}</div>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta, {{ $brandName }} iletişim formuna gönderdiğiniz mesaja istinaden yanıt olarak gönderilmiştir.</p>
</body>
</html>
