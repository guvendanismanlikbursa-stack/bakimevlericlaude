<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937; max-width:600px; margin:0 auto;">
  <h2>Hoş geldiniz, {{ $facility->name }}!</h2>
  <p>Hesabınız aktif. Sistemden en iyi verimi almanız için kısa bir rehber hazırladık:</p>

  <h3 style="margin-top:24px;">1. Nasıl Talep Alırsınız?</h3>
  <p style="color:#374151;">Kurumunuzun kategorisine ve şehrine uygun ailelerin gönderdiği ücret/bilgi talepleri doğrudan panelinize düşer. Yeni bir talep geldiğinde hem panelde hem de e-posta ile bildirim alırsınız.</p>

  <h3 style="margin-top:20px;">2. Hızlı Cevap Verin</h3>
  <p style="color:#374151;">Ailelerin genelde birden fazla kurumdan aynı anda teklif istediğini unutmayın — <strong>ne kadar hızlı cevap verirseniz</strong> tercih edilme ihtimaliniz o kadar artar. Cevap verdikten sonra da mesajlaşmaya devam edip ailenin sorularını yanıtlayabilirsiniz.</p>

  <h3 style="margin-top:20px;">3. Kredi/Kota Sistemi</h3>
  <p style="color:#374151;">Hesabınıza tanımlanan ücretsiz teklif hakları ile talepleri yanıtlayabilirsiniz. Krediniz azaldığında panelinizden bakiye yükleyerek devam edebilirsiniz.</p>

  <h3 style="margin-top:20px;">4. Profilinizi Tamamlayın</h3>
  <p style="color:#374151;">Kaliteli fotoğraflar, detaylı açıklama ve güncel fiyat aralığı eklemek profilinizin arama sonuçlarında öne çıkmasını ve ailelerin size güvenmesini doğrudan etkiler. Profilinizi ne kadar eksiksiz doldurursanız o kadar çok talep alırsınız.</p>

  <h3 style="margin-top:20px;">5. Bildirimlerinizi Takip Edin</h3>
  <p style="color:#374151;">Panelinizdeki "Bildirimler" sayfasından yeni talep, mesaj ve onay güncellemelerini anlık takip edebilirsiniz; önemli bildirimler ayrıca e-posta ile de size ulaşır.</p>

  <p style="margin-top:28px;"><a href="{{ $loginUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Panelime Giriş Yap</a></p>

  <p style="color:#6b7280;font-size:12px;margin-top:28px;">Herhangi bir sorunuz olursa bize ulaşmaktan çekinmeyin. Başarılar dileriz.</p>
</body>
</html>
