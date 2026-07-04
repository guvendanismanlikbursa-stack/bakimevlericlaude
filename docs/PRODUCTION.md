# Production Kurulum ve Deploy Kontrol Listesi

Bu proje 3 markayi tek Laravel uygulamasi ve tek veritabani uzerinden servis eder:

- bakimevibul.com
- bakimeviara.com
- bakimevleri.com

Local gelistirme ve QA tamamlandiktan sonra kalan islemler sunucu uzerinde yapilir.

## 1. Sunucu Gereksinimleri

- PHP 8.3 veya uzeri
- Composer 2
- MySQL 8 veya MariaDB 10.6+
- Web server: Nginx veya Apache
- Web root: projenin `public/` klasoru
- PHP eklentileri: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip`, `curl`, `gd` veya `imagick`
- Python 3 **opsiyonel** — sadece Veri Cekici'nin "Canli API" ozelligi icin gerekir, bkz. bolum 11. Excel import bu olmadan da calisir.

## 2. Sunucuya Yuklenecekler

Yuklenecek ana dosyalar:

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `docs/`
- `public/`
- `resources/`
- `routes/`
- `storage/app`, `storage/framework`, `storage/logs` klasor yapisi
- `tests/` istege bagli, canliya sart degil
- `tools/veri-cekici/`
- `artisan`
- `composer.json`
- `composer.lock`
- `.env.production.example` sadece ornek olarak

Yuklenmemesi gerekenler:

- `.env`
- `.env.codex-backup`
- `database/database.sqlite`
- `database/codex_clean.sqlite`
- `.phpunit.result.cache`
- `composer.phar`
- `tmp_*` dosyalari
- `storage/logs/*`
- `storage/framework/cache/data/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`

## 3. Production .env

Sunucuda `.env.production.example` dosyasini temel alip `.env` olusturun.

Mutlaka degisecek alanlar:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bakimevibul.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

`APP_KEY` sunucuda uretilecek:

```bash
php artisan key:generate --force
```

## 4. Kurulum Komutlari

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Mevcut veritabani korunarak deploy yapiliyorsa `db:seed --force` sadece bilincli calistirilmalidir.

## 5. Dosya Izinleri

Web server kullanicisi su klasorlere yazabilmelidir:

```bash
storage/
bootstrap/cache/
```

Upload kontrolu:

- Kurum galerisi gorsel yukleme
- Sahiplenme basvurusu evrak yukleme
- Bakiye yukleme dekont yukleme
- Veri cekici import gorselleri

## 6. Domain ve HTTPS

Uc domain ayni Laravel `public/` klasorune yonlenmelidir.

Kontrol:

- `https://bakimevibul.com`
- `https://bakimeviara.com`
- `https://bakimevleri.com`

HTTPS zorunlu olmalidir. HTTP istekleri HTTPS'e yonlendirilmelidir.

## 7. Cron / Scheduler

Sunucuda cron eklenmelidir:

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

Su an queue `sync` calisabilir. Yogun trafik veya mail islemleri artarsa queue worker ayrica kurulur.

## 8. Canli Smoke Test

Deploy sonrasi bu akislari canlida test edin:

- 3 ana site aciliyor mu?
- Bolum secimleri calisiyor mu?
- Ana sayfa filtreleri calisiyor mu?
- Kurum detay sayfalari aciliyor mu?
- Aile kayit/giris/panel calisiyor mu?
- Kurum giris/panel/profil/gorsel yukleme calisiyor mu?
- Admin panel aciliyor mu?
- Veri cekici Excel import calisiyor mu?
- On kayitli kurum kartlari gorunuyor mu?
- Sahiplenme basvurusu evrak yukleme calisiyor mu?
- Admin sahiplenme onayi ve tek kullanimlik sifre maili gidiyor mu?
- Teklif talebi, teklif verme, kabul ve mesajlasma calisiyor mu?
- Mobilde yatay tasma veya kirik gorsel var mi?
- SSS sayfasi (/sss) aciliyor mu, admin panelden soru eklenip goruntuleniyor mu?
- Aile/kurum panelinde Bildirimler sayfasi aciliyor mu, okundu isaretleme calisiyor mu?
- Admin Cop Kutusu: silinen kurum/talep/basvuru/bakiye yuklemesi listede gorunuyor mu, geri yukleme calisiyor mu?
- Admin Islem Gunlugu kritik aksiyonlari (onay/red, silme) kaydediyor mu?
- Kurum panelinde Paketler sayfasindan dekont yuklenip admin onayinda bakiye+bonus hak dogru isliyor mu?
- Ucret Rehberi (/fiyat-rehberi) il+bolum secip dogru istatistik ve kurum listesi getiriyor mu?
- Kesif sayfalari aciliyor mu: /dogrulanmis-kurumlar, /son-guncellenen-kurumlar, /yeni-eklenen-kurumlar, /son-sahiplenilen-kurumlar, /en-cok-goruntulenen-kurumlar, /son-eklenen-fotograflar?
- Kurum detay sayfasi acildiginda views_count artiyor mu (sayfayi 2 kez acip kontrol edin)?
- Favori butonuna basinca /kurumlar/{slug}/favori-say istegi 200 donuyor mu (tarayici devtools > Network)?
- "Kontenjan Sor" formu gonderilince visit_requests tablosuna type=kontenjan ile kayit dusuyor ve kurum kullanicisina bildirim gidiyor mu?
- Kurum detayinda "Aile Sorulari" formundan soru sorulabiliyor mu, kurum panelinde "Sorular" sekmesinden cevaplanabiliyor mu, cevap public sayfada goruniyor mu?
- Istatistikler sayfasi (/istatistikler) il bazli dagilimi dogru gosteriyor mu?
- Bakim Rehberi (/bakim-rehberi) admin panelden eklenen makaleleri listeliyor mu, makale detayi /sayfa/{slug} uzerinden aciliyor mu?
- "Yakinimdaki Kurumlar" butonu tarayici konum izni isteyip en yakin ile yonlendiriyor mu (HTTPS gerektirir, localhost'ta da calisir)?
- Admin > Statik Sayfalar ekraninda "Bakim Rehberi Makalesi" tipiyle yeni icerik eklenebiliyor mu?
- Admin > Ziyaret Talepleri ekraninda Kontenjan Sorusu / Ziyaret Talebi filtresi dogru ayirt ediyor mu?
- Admin > Aile Sorulari ekraninda uygunsuz bir soru/cevap silinebiliyor mu?
- Cron ile ayda bir calisan `admin-events:prune` hem eski islem gunlugunu hem okunmus eski bildirimleri temizliyor mu (server loglarindan kontrol edin)?
- Aile kayit formunda acik riza kutusu isaretlenmeden form gonderilebiliyor mu (gonderilmemeli)?
- Acik riza kutusu isaretlenince tarayici konum izni istiyor mu, izin verilince kayitta signup_lat/signup_lng/signup_city_name doluyor mu?
- Konum izni reddedilince kayit yine de tamamlaniyor mu (engellenmemeli)?
- Admin > Site Istatistikleri ekraninda 3 marka icin giris sayilari ve aile konum dagilimi goruntuleniyor mu?
- Ayni tarayicidan bir kurumu birden fazla kez favoriye ekleyip cikarinca favorites_count sadece ilk eklemede 1 artiyor mu (DB'den kontrol edin)?
- `.env`'e `THROTTLE_PUBLIC_FORM=5` gibi bir deger eklenip `config:cache` calistirildiginda ilgili formlarin limiti degisiyor mu?

## 9. Guvenlik

- `APP_DEBUG=false`
- `.env` public erisime kapali
- Dizin listeleme kapali
- Admin sifresi guclu
- SMTP sifresi ve DB sifresi `.env` disinda paylasilmamali
- Gunluk veritabani yedegi alinmali
- Upload klasoru yedek planina dahil edilmeli
- Duzenli calistirilacak kontrol: `composer audit --locked`
- Tum public form'lar (teklif, iletisim, yorum, ziyaret, kontenjan, soru, sahiplenme, favori sayaci, yakinimdaki kurumlar) throttle middleware ile korunuyor; canliya cikmadan once limitlerin trafiginize uygun oldugunu (routes/web.php icindeki `throttle:X,1` degerleri) kontrol edin

## 10. Local Son Durum

Localde son dogrulamalar:

- Temiz kurulum provasi gecti — bu turda ayrica `migrate:fresh --seed` + `config:cache`/`route:cache`/`view:cache`
  komutlarinin tamami hatasiz calistigi dogrulandi (81 il, kurumlar, 1 admin dogru seed edildi)
- Gercek Excel ile veri cekici testi gecti
- Gercek gorsel upload testi gecti
- Mobil/desktop QA yapildi
- **Composer audit bu ortamda calistirilamadi** (composer kurulu degil) — sunucuya gecmeden once
  `composer audit --locked` mutlaka calistirilmali, sonucu bu dosyaya not edilmeli
- PHPUnit: 26 test, 205 assertion basarili (guncel calistirma; GPS/harita/arama analitigi/galeri sikistirma/sahiplenme konumu/whatsapp/ayarlar testleri dahil)

## 11. Veri Cekici (opsiyonel canli API) — sunucu gereksinimi

Admin panelindeki "Veri Cekici" ekraninin **Excel import** kismi ek bagimlilik gerektirmez, dogrudan calisir.
"Canli API" (admin panelden butona basip Google Maps'i anlik taratma) ise sunucuda ayrica **Python 3**
gerektirir ve varsayilan sunucu kurulumunda genelde eksiktir:

```bash
python3 -m pip install playwright openpyxl requests beautifulsoup4
python3 -m playwright install chromium --with-deps
```

Bu kurulum yapilmadan "Canli API" butonuna basilirsa artik acik bir hata mesaji doner (once sessizce
500/tanimsiz hata veriyordu). Bu ozellik canliya cikis icin **zorunlu degildir** — Excel import yeterlidir;
"Canli API" sadece bu kurulum yapilan sunucularda kullanilabilir.

Veri cekici artik Google Maps yer sayfasinin URL'inden kurumun enlem/boylamini da cekip Excel'e
(`Enlem`/`Boylam` kolonlari) ve canli API onay akisina ekliyor — bkz. bolum 13.

## 12. Bu turda duzeltilenler (onceki self-review'da bulunan 3 madde)

- **Kurum paneli e-posta dogrulama tikanikligi**: `FacilityUserAuth` middleware'i her kurum-panel islemini
  (teklif verme, galeri yukleme, profil duzenleme, bakiye yukleme) `email_verified_at` dolu olmasina bagliyor.
  Demo tohum verisindeki kurum hesabi (`kurum@bakimplatform.test`) bu alani hic set etmiyordu ve hicbir
  dogrulama maili almiyordu — yani demo hesap kurulumdan hemen sonra panelde hicbir islem yapamiyordu.
  `database/seeders/DatabaseSeeder.php` icinde bu hesaba `email_verified_at => now()` eklendi.
- **E-posta dogrulama linki `/site/{brand}/...` modunda 404/yanlis kullanici veriyordu**: `Facility\EmailVerificationController::verify()`
  ve `Family\EmailVerificationController::verify()` route parametrelerini `int $id, string $hash` seklinde
  pozisyonel aliyordu; `/site/{brand}` on ekli route'larda araya giren `{brand}` parametresi kaydırmaya
  yol acip `$id` alanina marka adini ("bakimevleri" gibi) tasiyordu, gercek hash hic kullanilmiyordu.
  Gercek domain uzerinden (bakimevibul.com/bakimeviara.com/bakimevleri.com) gelen istekler bu hataya
  girmiyordu (o route'larda `{brand}` parametresi yok) ama yerel/staging `/site/{brand}` testinde her
  dogrulama linki kirikti. Artik her iki controller de `$request->route('id')` / `$request->route('hash')`
  ile parametreleri isimle okuyor, brand parametresi olsun olmasin dogru calisiyor.
- **Testler ve bu dosyadaki rakamlar guncellenmedi**: test suite gercekte 4 basarisizlik + 2 hata veriyordu
  (yukaridaki iki maddenin sonucu); test fixture'lari ve beklenen sitemap URL formatı guncellendi, tum
  18 test yesil.

## 13. Bu turda eklenenler: gercek GPS, gercek Turkiye haritasi, arama analitigi

- **Gercek GPS / "Yakinimdaki Kurumlar"**: `facilities` tablosuna `lat`/`lng` (decimal) eklendi
  (migration `0001_01_01_000040`). Admin kurum formundan elle girilebilir; veri cekici Google Maps
  yer sayfasinin URL'inden otomatik cekiyor; Excel import da `Enlem`/`Boylam` kolonlarini taniyor.
  Koordinati olan kurumlar icin `GeoLookupService::nearestFacilities()` gercek Haversine mesafesiyle
  siralar (100km ust siniri var) ve kurum listesi sayfasinda "Size En Yakin Kurumlar" seridi cikar;
  koordinatsiz kurumlar icin eski il-merkezi yonlendirmesi hala yedek olarak calisir. **Mevcut/eski
  kurum kayitlarinda bu alan bos olacaktir** — geriye donuk toplu doldurma yapilmadi, sadece yeni
  veri cekici cekimleri ve admin formundan elle girilenler dolar.
- **Gercek Turkiye haritasi**: `public/images/turkiye-harita.svg` — 81 ilin gercek sinirlarini iceren,
  Apache-2.0 lisansli acik kaynak GeoJSON'dan (`alpers/Turkey-Maps-GeoJSON`) uretilmis interaktif SVG.
  `/istatistikler` sayfasi bunu satir ici gomup il basina kurum sayisina gore renklendiriyor (hover'da
  sayi gosterir, tiklayinca o ilin kurum listesine gider). Bu dosya `public/` altinda oldugu icin
  normal deploy akisiyla zaten sunucuya gidiyor, ayrica bir islem gerekmez. Haritayi yeniden uretmek
  gerekirse (orn. daha yuksek cozunurluk): `php tools/turkiye-haritasi/generate_svg.php`
  (`tools/turkiye-haritasi/tr-cities.json` kaynak dosyasi gerekir, repo'da mevcut).
- **Arama analitigi / "En Cok Aranan Bolgeler"**: yeni `search_queries` tablosu (migration
  `0001_01_01_000041`). Sitede serbest metin arama kutusu olmadigi icin, kurum listesinde
  il/kategori/hizmet filtresi her kullanildiginda bu kaydediliyor — kelime bazli arama loglama
  DEGILDIR. Ayni gun ayni kombinasyon tekrar aranirsa yeni satir acilmaz, sayac artar (site_visits
  tablosuyla ayni buyume kontrolu mantigi). `/en-cok-aranan-bolgeler` son 30 gunun en cok filtrelenen
  il+kategori kombinasyonlarini listeler.

Smoke test listesine (bolum 8) eklenmesi gerekenler:

- Bir kurumun admin formundan enlem/boylam girilip kaydedildiginde, o ilin kurum listesinde
  `?lat=..&lng=..` ile "Size En Yakin Kurumlar" seridi dogru mesafeyle cikiyor mu?
- `/istatistikler` sayfasinda gercek Turkiye haritasi goruntuleniyor mu, il uzerine gelince
  tooltip cikiyor mu, tiklayinca o ilin kurum listesine gidiyor mu?
- Kurum listesinde il/kategori filtresi kullanildiktan sonra `/en-cok-aranan-bolgeler` sayfasinda
  bu arama beliriyor mu?

## 14. Kurum galerisi sikistirma, sahiplenme konumu, WhatsApp butonu

- **Galeri gorseli sikistirma**: `ImageCompressionService` — admin ve kurum panelinden yuklenen
  YENI kurum galerisi gorselleri (10 gorsel siniri ayni kaliyor) otomatik olarak max 1600px'e
  kucultulup WebP'ye (kalite 78) cevrilip oyle saklaniyor; boylece sayfa yuklenme hizi/bant genisligi
  onemli olcude iyilesir. Imagick varsa o, yoksa GD, o da yoksa (veya webp encode destegi yoksa)
  orijinal dosya oldugu gibi saklanir — yukleme hicbir sekilde basarisiz olmaz. **Bu ortamda (yerel
  gelistirme makinesi) ne GD ne Imagick kurulu oldugu icin gercek sikistirma kodu calistirilip test
  edilemedi**; sadece PHP syntax kontrolu ve "eklenti yoksa orijinali kaydet" yedek yolu (mevcut
  testlerle) dogrulandi. Sunucuda `gd` veya `imagick` zaten zorunlu (bkz. bolum 1).
  **Deploy sonrasi bir kere calistirin:**
  ```bash
  php artisan diagnostics:image-compression
  ```
  Bu komut sunucuda gercekten hangi surucunun (imagick/gd) calistigini ve gercek bir test
  goruntusunu sikistirip sikistiramadigini raporlar — "ok" demeden gercek bir yukleme yapip
  dosyanin `.webp` ve kucuk geldigini de ayrica goz ile kontrol edin.
  Sadece yeni yuklemelere uygulanir, storage'daki mevcut gorseller elle degistirilmedi.
- **Sahiplenme basvurusunda konum/mesafe**: Basvuru formu (tarayici izin verirse, zorunlu degil —
  reddedilirse basvuru yine de tamamlanir) konum alip `facility_claims.applicant_lat/lng` +
  `applicant_city_name` + `distance_km` (kurumun kendi koordinati varsa) kaydeder. Admin inceleme
  ekraninda (`admin/sahiplenme-basvurulari/{id}`) ve liste ekraninda bu mesafe gosterilir, 50km
  uzeri sari renkle isaretlenir — bu sadece admin'e ek bir sahtecilik kontrolu sinyalidir, onay/red
  karari yine admin'e ait, otomatik reddetme yoktur.
- **WhatsApp butonu**: 3 sitede de sag altta sabit, hafif "ziplayarak" beliren yesil WhatsApp ikonu
  (`themes/_shared/partials/whatsapp-button.blade.php`, `layouts/brand.blade.php` icinden her
  sayfaya dahil). Tiklaninca hattiniza hazir mesajla `wa.me` sohbeti yeni sekmede acilir; ayrica
  arka planda (WhatsApp acilmasini geciktirmeden) tarayici konumu izne bagli olarak alinip
  `whatsapp_clicks` tablosuna kaydedilir, admin panelde **WhatsApp Tiklamalari** ekranindan
  (`admin/whatsapp-tiklamalari`) marka/tarih/yaklasik konum bilgisiyle goruntulenir.
  Numara ve hazir mesaj artik kodda sabit degil — **Admin > Ayarlar** sayfasindan
  (`admin/ayarlar`) kod degistirmeden/deploy gerekmeden guncellenebilir (varsayilan: +90 850 308
  79 91). Mesaj metninde `{marka}` yazarsaniz otomatik olarak siteyi adiyla degistirilir.
  **Onemli:** WhatsApp'in kendisine otomatik bildirim/mesaj gitmiyor — bu, resmi ve ucretli WhatsApp
  Business API entegrasyonu gerektirir, basit bir buton tiklamasiyla yapilamaz. Admin bilgiyi panel
  ekranindan takip eder.
- **KVKK sayfasi guncellendi**: `/sayfa/kvkk` ornek/demo metnine sahiplenme basvurusu ve WhatsApp
  butonu icin yapilan konum toplama aciklamalari eklendi. Bu metin hala "canliya almadan once
  hukuki danismaniniza onaylatin" notuyla isaretli demo icerik — admin panelden (Statik Sayfalar)
  duzenlenebilir, gercek yayina almadan mutlaka bir avukata kontrol ettirin.

Smoke test listesine eklenmesi gerekenler:

- Kurum panelinden/admin'den yeni bir galeri gorseli yuklendiginde dosya gercekten kuculuyor mu
  (orijinal boyutla kiyaslayin) ve `.webp` uzantili mi geliyor? (`php artisan diagnostics:image-compression`
  ile once surucu kontrolu yapin)
- Sahiplenme basvurusu formunda konum izni verilince admin inceleme ekraninda mesafe (km) dogru
  gorunuyor mu; izin verilmeyince basvuru yine de basariyla tamamlaniyor mu?
- Herhangi bir sayfada sag alttaki WhatsApp ikonuna tiklaninca gercek WhatsApp sohbeti aciliyor mu
  (hazir mesajla) ve `admin/whatsapp-tiklamalari` ekraninda kayit beliriyor mu?
- Admin > Ayarlar'dan WhatsApp numarasi/mesaji degistirilince butonda hemen yansiyor mu?
- `/sayfa/kvkk` icerigini gozden gecirip gercek KVKK/hukuk metniyle degistirdiniz mi?
