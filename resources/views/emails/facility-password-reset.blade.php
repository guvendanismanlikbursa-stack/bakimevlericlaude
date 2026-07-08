<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Merhaba {{ $user->name }},</h2>
  <p>{{ $brandName }} kurum hesabınız için bir şifre sıfırlama talebi aldık.</p>
  <p><a href="{{ $resetUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Şifremi Sıfırla</a></p>
  <p style="color:#6b7280;font-size:13px;">Bu bağlantı 60 dakika geçerlidir. Buton çalışmazsa şu adresi tarayıcınıza yapıştırın:</p>
  <p style="color:#6b7280;font-size:12px;word-break:break-all;">{{ $resetUrl }}</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz; şifreniz değişmeyecektir.</p>
</body>
</html>
