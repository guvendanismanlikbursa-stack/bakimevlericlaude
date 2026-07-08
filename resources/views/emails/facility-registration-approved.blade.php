<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Tebrikler, "{{ $facility->name }}" kurum kaydı başvurunuz onaylandı!</h2>
  <p>Kurum panelinize aşağıdaki bilgilerle giriş yapabilirsiniz:</p>
  <table style="margin:16px 0;">
    <tr><td style="padding:4px 12px 4px 0;"><strong>E-posta:</strong></td><td>{{ $email }}</td></tr>
    <tr><td style="padding:4px 12px 4px 0;"><strong>Geçici Şifre:</strong></td><td><code>{{ $temporaryPassword }}</code></td></tr>
  </table>
  <p>Bu geçici şifre tek seferliktir; ilk girişte sizden yeni bir şifre belirlemeniz istenecektir.</p>
  <p><a href="{{ $loginUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Kurum Paneline Git</a></p>
  <p>Hesabınıza, talep eden ailelere ücret teklifi gönderebilmeniz için <strong>{{ $facility->free_quote_credits }} ücretsiz teklif hakkı</strong> tanımlanmıştır.</p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
