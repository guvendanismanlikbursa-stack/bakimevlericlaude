<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Kurum paneli giriş bilgileriniz güncellendi</h2>
  <p>"{{ $facilityUser->facility->name }}" kurumu için giriş bilgileriniz yönetici tarafından yenilendi. Kurum panelinize aşağıdaki bilgilerle giriş yapabilirsiniz:</p>
  <table style="margin:16px 0;">
    <tr><td style="padding:4px 12px 4px 0;"><strong>E-posta:</strong></td><td>{{ $facilityUser->email }}</td></tr>
  </table>
  {{-- 25 Agustos 2026: kullanicinin bildirdigi gercek hata - eski satiriçi
       <code>, karisik (sembol iceren) sifreyle birlikte cift-tiklamada
       TAMAMI secilemiyordu (kullanici PC'de kopyala-yapistirda bile hatali
       sifre aliyordu). Sifre artik ayri, tek basina bir satirda, genis
       tiklama/dokunma alanli, harf araligi acilmis bir kutuda - hem
       secmek/kopyalamak hem telefonda okuyup elle yazmak kolaylassin diye.
       Ayrica bkz. Str::password() cagrilarindaki symbols:false degisikligi
       (Admin\UserController::resetFacilityUserPassword) - artik sifre
       zaten sadece harf+rakam, tek kelime olarak tam secilir. --}}
  <p style="margin:4px 0 0;"><strong>Geçici Şifre:</strong></p>
  <p style="margin:6px 0 16px;"><code style="display:inline-block;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;padding:10px 16px;font-size:18px;letter-spacing:2px;font-weight:bold;">{{ $temporaryPassword }}</code></p>
  <p>Bu geçici şifre tek seferliktir; ilk girişte sizden yeni bir şifre belirlemeniz istenecektir.</p>
  <p><a href="{{ $loginUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Kurum Paneline Git</a></p>
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir. Bu değişikliği siz talep etmediyseniz lütfen platform yöneticisiyle iletişime geçin.</p>
</body>
</html>
