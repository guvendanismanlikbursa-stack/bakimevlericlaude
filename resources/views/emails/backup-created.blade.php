<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937; max-width:600px; margin:0 auto;">
  <h2>Günlük veritabanı yedeği oluşturuldu</h2>
  <p style="color:#374151;">Dosya: <strong>{{ $filename }}</strong> ({{ $sizeLabel }})</p>
  @if($attachmentPath)
    <p style="color:#374151;">Yedek dosyası bu e-postaya eklidir.</p>
  @else
    <p style="color:#b45309;">Yedek dosyası çok büyük olduğu için bu e-postaya eklenmedi, sunucudaki <code>storage/app/private/backups</code> klasöründen elle indirin.</p>
  @endif
  @if($filesZipPath)
    <p style="color:#374151;">Yüklenen kurum görselleri, sahiplenme belgeleri ve .env dosyasını içeren <strong>{{ basename($filesZipPath) }}</strong> da bu e-postaya eklidir.</p>
  @else
    <p style="color:#6b7280;">Yüklenen görseller/belgeler ve .env için ayrı bir dosya yedeği de sunucudaki <code>storage/app/private/backups</code> klasöründe oluşturuldu (e-postaya sığmadıysa elle indirin).</p>
  @endif
  <p style="color:#6b7280;font-size:12px;margin-top:28px;">Bu, sunucu dışında bir yedek kopyası oluşturmak için otomatik gönderilen bir bildirimdir.</p>
</body>
</html>
