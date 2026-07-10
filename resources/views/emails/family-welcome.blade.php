<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937; max-width:600px; margin:0 auto;">
  <h2>Hoş geldiniz, {{ $family->name }}!</h2>
  <p>{{ $brandName }} ailesine katıldığınız için teşekkür ederiz. Hesabınız hazır — işte platformu en verimli şekilde kullanmanız için kısa bir rehber:</p>

  <h3 style="margin-top:24px;">1. Kurum Arayın ve Karşılaştırın</h3>
  <p style="color:#374151;">İl, ilçe ve bakım kategorisine göre filtreleyerek size uygun kurumları listeleyebilir, profillerini (fotoğraf, kapasite, fiyat aralığı, değerlendirmeler) inceleyebilirsiniz.</p>

  <h3 style="margin-top:20px;">2. Ücret/Bilgi Talebi Gönderin</h3>
  <p style="color:#374151;">Beğendiğiniz bir kurumun sayfasından tek tıkla ücret talebi gönderebilirsiniz. İsterseniz "Toplu Teklif Talebi" ile <strong>aynı anda birden fazla kuruma</strong> talep gönderip fiyat ve hizmetleri kolayca karşılaştırabilirsiniz.</p>

  <h3 style="margin-top:20px;">3. Cevapları Takip Edin ve Mesajlaşın</h3>
  <p style="color:#374151;">Kurumlar size fiyat teklifiyle birlikte cevap verdiğinde hem hesabınıza giriş yaptığınızda hem de e-posta ile haberdar olursunuz. Teklif sayfası üzerinden kurumla doğrudan mesajlaşmaya devam edebilir, sorularınızı sorabilirsiniz.</p>

  <h3 style="margin-top:20px;">4. Bildirimlerinizi Kontrol Edin</h3>
  <p style="color:#374151;">Panelinizdeki "Bildirimler" sayfasında yeni teklif, mesaj ve güncellemeleri anlık olarak görebilirsiniz; önemli bildirimler ayrıca e-posta ile de size ulaşır.</p>

  <p style="margin-top:28px;"><a href="{{ $panelUrl }}" style="background:#1e6f5c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Panelime Git</a></p>

  <p style="color:#6b7280;font-size:12px;margin-top:28px;">Herhangi bir sorunuz olursa bize ulaşmaktan çekinmeyin. İyi günler dileriz.</p>
</body>
</html>
