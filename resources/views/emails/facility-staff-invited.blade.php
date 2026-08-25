<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>"{{ $facility->name }}" kurum paneline ekip üyesi olarak eklendiniz</h2>
  <p>Kurum paneline aşağıdaki bilgilerle giriş yapabilirsiniz:</p>
  <table style="margin:16px 0;">
    <tr><td style="padding:4px 12px 4px 0;"><strong>E-posta:</strong></td><td>{{ $email }}</td></tr>
  </table>
  {{-- 25 Agustos 2026: bkz. facility-password-manually-reset.blade.php ayni
       tarihli yorum - sifre cift-tiklamayla tam secilebilsin/kopyalanabilsin
       diye ayri, genis, harf araligi acilmis bir kutuya alindi. --}}
  <p style="margin:4px 0 0;"><strong>Geçici Şifre:</strong></p>
  <p style="margin:6px 0 16px;"><code style="display:inline-block;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;padding:10px 16px;font-size:18px;letter-spacing:2px;font-weight:bold;">{{ $temporaryPassword }}</code></p>
  <p>Bu geçici şifre tek seferliktir; ilk girişte sizden yeni bir şifre belirlemeniz istenecektir.</p>
  <p><a href="{{ $loginUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Kurum Paneline Git</a></p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
