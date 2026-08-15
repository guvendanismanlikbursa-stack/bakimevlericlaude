{{--
  15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine yapilan
  spam/kotuye kullanim denetiminde bulundu - hicbir herkese acik formda
  CAPTCHA/honeypot yoktu, IP rate-limit tek basina IP-rotasyonlu botlara karsi
  yetersiz. Ucuncu taraf servis (reCAPTCHA vb.) hesap/anahtar gerektirdigi
  icin bagimsiz bir cozum: klasik honeypot alani. Botlar formu otomatik
  doldururken genelde TUM inputlari (gorunmez olsa bile) doldurur, gercek
  kullanici ise CSS ile ekrandan tasindigi/gizlendigi icin bu alani hic
  gormez, bos birakir. Sunucu tarafinda 'website' => 'max:0' kurali ile
  kontrol edilir (bkz. ilgili controller'larin validate() cagrisi).
--}}
<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
  <label for="hp-website">Web siteniz</label>
  <input type="text" id="hp-website" name="website" tabindex="-1" autocomplete="off" value="{{ old('website') }}">
</div>
