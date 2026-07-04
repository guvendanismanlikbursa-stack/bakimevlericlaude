<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Merhaba {{ $family->name }},</h2>
  <p>{{ $brandName }} üzerinde oluşturduğunuz aile hesabının e-posta adresini doğrulamanız gerekiyor.</p>
  <p><a href="{{ $verificationUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">E-postamı Doğrula</a></p>
  <p style="color:#6b7280;font-size:13px;">Bu bağlantı 60 dakika geçerlidir. Buton çalışmazsa şu adresi tarayıcınıza yapıştırın:</p>
  <p style="color:#6b7280;font-size:12px;word-break:break-all;">{{ $verificationUrl }}</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu hesabı siz oluşturmadıysanız bu e-postayı yok sayabilirsiniz.</p>
</body>
</html>
