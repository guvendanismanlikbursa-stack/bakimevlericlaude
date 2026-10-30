{{-- 30 Ekim 2026: kullanıcının talebi - doktor görüşme rehberi; yalnızca admin CRM'inde
     (pazarlamacı paneline eklenmedi). Temel paket fiyatı paket ayarından gelir. --}}
@php
  $temelFiyat = \App\Http\Controllers\Public\HekimPaketController::fiyat('temel');
  $temelSayi = (int) preg_replace('/\D+/', '', explode('₺', $temelFiyat)[0]);
  $aylik = $temelSayi > 0 ? number_format((int) ceil($temelSayi / 12 / 50) * 50, 0, ',', '.') : null;
  $adim = 'background:#f8fafc;border-left:4px solid #0d6e6a;border-radius:10px;padding:10px 14px;';
  $baslik = 'display:block;font-size:12px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;color:#0d6e6a;margin-bottom:4px;';
  $soz = 'font-size:14.5px;color:#111827;line-height:1.55;';
  $not = 'display:block;font-size:12.5px;color:#92400e;margin-top:4px;font-weight:700;';
@endphp
<details style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:14px 16px;margin-bottom:14px;border:1.5px solid #cfe7e4;">
  <summary style="cursor:pointer;font-weight:900;font-size:15px;color:#0d6e6a;">📋 Görüşme rehberi — doktorla konuşurken açık tutun</summary>
  <div style="display:grid;gap:10px;margin-top:12px;">
    <div style="{{ $adim }}"><span style="{{ $baslik }}">1 · Giriş</span><div style="{{ $soz }}">"Merhaba Hocam, ben Güven, bakimevleri.com'dan arıyorum. Bursa'da hasta bakım hizmeti veren bir firmayız, ofisimiz Şehir Hastanesi'nin batı girişinde. Müsaitseniz 2 dakikanızı rica edeceğim; uygun değilse sizi ne zaman arayayım?"</div><span style="{{ $not }}">"Hastane adına" ya da "hastanemiz" demeyin.</span></div>
    <div style="{{ $adim }}"><span style="{{ $baslik }}">2 · Rehberdeki yeri (ücretsiz)</span><div style="{{ $soz }}">"bakimevleri.com, bakimevibul.com ve bakimeviara.com'da aileler için bir hekim rehberi var. Profiliniz burada ücretsiz yer alıyor: adresiniz, branşınız, telefonunuz. Sıralama alfabetik, yorum ve puan yok; TTB kılavuzunu esas alarak hazırladık. Bilgileriniz doğru mu, bir kontrol etmek istedik."</div></div>
    <div style="{{ $adim }}"><span style="{{ $baslik }}">3 · Soru</span><div style="{{ $soz }}">"Muayenehaneniz bu adreste mi? Şu an kendinize ait bir internet siteniz var mı?"</div><span style="{{ $not }}">Cevabı not formundaki "Web sitesi var mı?" kutusuna işaretleyin.</span></div>
    <div style="{{ $adim }}"><span style="{{ $baslik }}">4 · Teklif (sitesi yoksa / sadece rehber veya Instagram varsa)</span><div style="{{ $soz }}">"Hekimler için kendi alan adınızla, TTB kurallarına uygun, sade internet siteleri de hazırlıyoruz. Alan adı, barındırma ve güvenlik sertifikası dahil. Sizin bilgilerinizle üç farklı tasarımda örneğini hazırladık bile. WhatsApp'tan göndermeme izin verir misiniz? Siteyi almasanız da rehberdeki yeriniz ücretsiz olarak devam eder."</div><span style="{{ $not }}">İzin alınmadan link gönderilmez. İzin verirse kartın altındaki "💬 Demo site gönder".</span></div>
    <div style="{{ $adim }}"><span style="{{ $baslik }}">İyilik Hareketi (yalnızca gerçekten yapılıyorsa)</span><div style="{{ $soz }}">"Bu işten elde ettiğimiz gelirin bir kısmı, platformumuzdaki İyilik Hareketi kapsamında ihtiyaç sahibi yaşlı ve çocukların bakımına gidiyor."</div></div>
    <div style="{{ $adim }}"><span style="{{ $baslik }}">5 · Kapanış</span><div style="{{ $soz }}">"Örnekleri bugün gönderiyorum. Perşembe günü sizi arayıp beğendiğiniz tasarımı sorsam uygun olur mu, hangi saat iyi?"</div><span style="{{ $not }}">İlk görüşmede sipariş istenmez. Verdiği gün/saati "Ne zaman tekrar aranacak" alanına yazın.</span></div>

    <div style="background:#fffbeb;border-radius:10px;padding:10px 14px;">
      <span style="{{ $baslik }}color:#92400e;">İtirazlara cevap</span>
      <div style="{{ $soz }}display:grid;gap:6px;">
        <div><b>"Doktortakvimi'nde varım":</b> "Orada sizin adınıza bir sayfa var, ama alan adı sizin değil. Kendi adınızla bir adres, Google'da adınız arandığında en üstte sizin sitenizin çıkmasını sağlar."</div>
        <div><b>"Hastanede çalışıyorum":</b> "Hastanenin sitesinde sayfanız varsa ona ihtiyaç duymayabilirsiniz; muayenehaneniz yoksa sizi oyalamayayım." — kibarca kapatın.</div>
        <div><b>"Sitem var":</b> "Çok güzel. O zaman sadece rehberdeki bilgilerinizi kontrol edelim." — sitesini CRM'e işleyin.</div>
        <div><b>"Pahalı":</b> "Temel paketimiz yıllık {{ $temelFiyat }}{{ $aylik ? ', yani ayda '.$aylik.' liraya yakın' : '' }}. Alan adı ve barındırma dahil."</div>
        <div><b>"TTB'den sorun olur mu?":</b> "Sitede yorum, puan ve reklam yok; kılavuzun istediği zorunlu cümle sayfanın en üstünde duruyor."</div>
        <div><b>"Listeden çıkarın":</b> "Elbette, hemen işleme alıyorum." — durumu "Platformdan Kaldırılmasını İstiyor" seçin.</div>
        <div><b>Sekreter:</b> "Hocaya ileteceğiniz kısa bir bilgi; hangi gün ve saatte aramam uygun olur?"</div>
      </div>
    </div>
    <div style="font-size:12.5px;color:#6b7280;">Söylenmeyecekler: "seçkin hekimler", "bugün 5 hekim sipariş verdi" gibi doğrulanamayan ifadeler, 7/24 destek sözü, paket sayfasında olmayan bir özellik.</div>
  </div>
</details>
