<!DOCTYPE html>
<html lang="tr">
<body style="font-family: Arial, sans-serif; color:#1f2937;">
  <h2>Fiyat talebiniz kapatıldı</h2>
  <p>Merhaba {{ $offerRequest->full_name }},</p>
  <p>{{ $offerRequest->facility->name ?? 'İlgilendiğiniz kurum(lar)' }} için gönderdiğiniz fiyat talebini kapattık.</p>
  @if($offerRequest->accepted_quote_id)
    <p>Daha önce kabul ettiğiniz teklif ve mesajlaşmanız bundan etkilenmez, hesabınızdan erişmeye devam edebilirsiniz.</p>
  @else
    <p>Hâlâ ilgileniyorsanız kurum sayfasından yeni bir fiyat talebi oluşturabilirsiniz.</p>
  @endif
  <p style="color:#6b7280;font-size:12px;margin-top:24px;">Bu e-posta otomatik olarak gönderilmiştir.</p>
</body>
</html>
