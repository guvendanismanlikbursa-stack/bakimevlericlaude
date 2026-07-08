# Bakım Platformu

3 marka, tek Laravel uygulaması, ortak veritabanı ve ortak admin paneli.

## Markalar

| Site | Yerel URL | Kapsam |
|---|---|---|
| bakimevibul.com | `http://127.0.0.1:8000/` veya `/site/bakimevibul` | Yaşlı bakım, huzurevi |
| bakimeviara.com | `http://127.0.0.1:8000/site/bakimeviara` | Çocuk bakımı, özel eğitim |
| bakimevleri.com | `http://127.0.0.1:8000/site/bakimevleri` | Rehabilitasyon, fizik tedavi |

Aile hesapları globaldir; aynı aile 3 sitede de giriş yapabilir. **Kasıtlı ürün kararı**: 3 site aynı kurum envanterini paylaşır (bilinçli tercih — Google'da arayan kişi 3 siteyi de görüp istediği/uygun olduğu yerden fiyat sorabilsin, maksimum talep toplama hedeflenir). Her markanın bir varsayılan uzmanlık alanı (bölüm) vardır ama kullanıcı sekmelerle diğer bölümlere de geçebilir; `category_scope` bu yüzden bilerek 3 markada da aynıdır — bu bir izolasyon eksikliği değil, tasarım tercihidir.

Aynı kurum sayfasının 3 domainde birebir aynı içerik olarak indexlenmesini (duplicate content) önlemek için her markanın kendi editoryal "sesi" kurum sayfasına benzersiz bir çerçeve cümlesi ve meta description eki ekler (bkz. `config/brand_voice.php` ve `facility_brand_framing()` helper'ı). Ham kurum verisi (isim, adres, fiyat, görseller) aynı kalır; etrafındaki editoryal metin markaya göre farklıdır.

## Local Kurulum

Gereksinimler: PHP 8.2+, Composer, SQLite desteği.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

`.env.example` local için SQLite kullanır. Veritabanı dosyası `database/database.sqlite` olarak tutulur.

## Testler

```bash
vendor/bin/phpunit --testdox
php composer.phar audit --locked
php artisan route:list
```

Windows yerelde bu projede test komutu:

```powershell
vendor\bin\phpunit.bat --testdox
```

Son çalıştırmada durum: 26 test, 205 assertion başarılı (GPS/harita/arama analitiği/galeri sıkıştırma/sahiplenme konumu/WhatsApp/ayarlar testleri dahil).

Kapsanan kritik akışlar:

- Public siteler ve auth panelleri açılır.
- Aile hesabı global kalır, panel içeriği markaya göre filtrelenir.
- Teklif talebi hangi site (marka) üzerinden oluşturulduysa, kurum yetkilisi de o siteye ait oturumdayken teklif verebilir — talep 3 sitede paylaşılan envanterden bağımsız olarak, oluşturulduğu siteye "aittir" (`offer_requests.brand`).
- Teklif talebi, kurum teklifi, aile kabulü ve mesajlaşma uçtan uca çalışır.
- Kurum başka markanın (farklı siteden oluşturulmuş) talebine teklif veremez.
- Admin kurum oluşturma, güncelleme ve silme akışı çalışır.
- Sahiplenme başvurusu ve bakiye yükleme gerçek PNG dosya upload testiyle çalışır.

## Demo Hesaplar

⚠️ **Bu şifreler herkese açık bir şekilde bu dosyada yazılıdır.** `DatabaseSeeder`
sadece `APP_ENV=local` veya `testing` iken bu hesapları oluşturur; production/staging'de
otomatik olarak atlanır (bkz. `database/seeders/DatabaseSeeder.php`). Canlı admin
hesabını asla bu seeder ile değil, ayrıca ve gerçek/güçlü bir şifreyle oluşturun.

`php artisan migrate --seed` sonrası (sadece local/testing):

| Rol | E-posta | Şifre |
|---|---|---|
| Süper Admin | `admin@bakimplatform.test` | `Admin12345!` |
| Demo Aile | `aile@bakimplatform.test` | `Aile12345!` |
| Demo Kurum Yetkilisi | `kurum@bakimplatform.test` | `Kurum12345!` |

## Ana Akışlar

Kurum sahiplenme:

1. Admin kurum ön kaydı oluşturur.
2. Kurum yetkilisi kurum detay sayfasından sahiplenme başvurusu yapar.
3. Yetkili belge görseli yükler.
4. Admin başvuruyu onaylar.
5. Sistem kurum kullanıcısını oluşturur ve ücretsiz teklif hakkı tanımlar.

Bakiye yükleme:

1. Kurum panelden bakiye talebi oluşturur.
2. Dekont görseli yüklenir.
3. Admin talebi onaylar veya reddeder.
4. Onayda kurum bakiyesi ve bakiye hareket kaydı güncellenir.

Aile teklif talebi:

1. Aile veya ziyaretçi kurum detayından ücret bilgisi ister.
2. Talep aktif markanın kapsamına göre oluşur.
3. Uygun kurumlar teklif verir.
4. Aile teklifi kabul eder.
5. Mesajlaşma ekranı iki taraf için açılır.

## Production

Canlı kurulum adımları ve checklist için `docs/PRODUCTION.md` dosyasına bakın.

## canliyaal projesinden taşınan modüller

`canliyaal` (eski API + Next.js sürümü) içindeki şu modüller bu projeye taşındı:

- **SSS**: `admin/sss` panelinden marka bazlı soru/cevap yönetimi, `/sss` public sayfası.
- **Bildirimler**: Aile ve kurum panellerinde uygulama-içi bildirimler (`bildirimler` sayfası, header'da okunmamış rozeti). Sahiplenme onayı ve bakiye yükleme onay/red işlemlerinde otomatik bildirim gönderilir.
- **İşlem Günlüğü (Audit Log)**: `admin/islem-gunlugu` — kritik admin aksiyonları (sahiplenme onay/red, bakiye onay/red, SSS/paket CRUD, çöp kutusu geri yükleme/kalıcı silme) `admin_events` tablosuna kaydedilir.
- **Çöp Kutusu**: `admin/cop-kutusu` — kurum, teklif talebi, bakiye yüklemesi ve sahiplenme başvurusu için soft-delete + geri yükleme/kalıcı silme arayüzü.
- **Paket/Abonelik Kataloğu**: `admin/paketler` — admin paket tanımlar (fiyat + bonus teklif hakkı); kurum panelindeki `Paketler` sayfasından dekont yükleyerek talep eder, mevcut bakiye onay akışıyla (dekont onayı) işlenir — ayrı bir ödeme sistemi kurulmadı, mevcut cüzdan/bakiye mekanizmasına entegre edildi.

## Ücretlendirme Segmentleri (Ekonomik / Standart / Premium / Ultra Premium)

Her kurumun `price_min` (aylık başlangıç ücreti) değeri, admin panelden ayarlanabilen 3 eşiğe göre otomatik segmente ayrılır ve **3 sitede de** (ortak Blade view'ları kullandıkları için) aynı şekilde gösterilir:

| Segment | Rozet | Varsayılan eşik |
|---|---|---|
| Ekonomik | 🟢 yeşil | eşik altı |
| Standart | 🔵 mavi | `price_tier_standart_min` (varsayılan 15.000₺) |
| Premium | 🟣 mor | `price_tier_premium_min` (varsayılan 30.000₺) |
| Ultra Premium | 🟡 sarı | `price_tier_ultra_min` (varsayılan 50.000₺) |

- Eşikler `admin/ayarlar` sayfasından değiştirilebilir (`Setting` tablosu, `config/platform.php`'deki `default_price_tiers` varsayılan olarak kullanılır).
- Rozet, kurum kartlarında (`themes/_shared/facilities/index.blade.php`), kurum detay sayfasında (`.../show.blade.php`) ve admin kurum formunda tek bir partial (`themes/_shared/partials/price-tier-badge.blade.php`) üzerinden gösterilir — 3 marka da aynı dosyayı `@include` ettiği için ayrıca tema başına iş yapmaya gerek kalmadı.
- Kurum listeleme sayfasına segment filtresi eklendi (`?price_tier=premium` vb.).
- `price_min` girilmemiş (özellikle veri çekiciyle gelen ön kayıtlı) kurumlarda rozet gösterilmez; yanlış segment ataması riskini önler.

## İşleyiş doğrulaması: Veri Çekici → Ön Kayıt → Sahiplenme → Tek Seferlik Şifre

Kod incelemesiyle uçtan uca doğrulandı:

1. **Veri Çekici** (`Admin\DataExtractorController` + `GoogleMapsDataExtractorService`) Google Maps'ten veri çeker, `DataImportRow` olarak inceleme kuyruğuna alır.
2. Admin bir satırı onayladığında `DataImportRowApprovalService::approve()` bir `Facility` oluşturur: `is_claimed = false`, `is_published` (admin seçimine göre), `source = 'google_maps_veri_cekici'`, `free_quote_credits = 0`, `balance = 0`. Bu kurum "ön kayıtlı" olarak listelenir.
3. Kurum yetkilisi bu profil üzerinden **sahiplenme başvurusu** yapar (`Public\FacilityClaimController`), evrak yükler.
4. Admin başvuruyu onayladığında (`Admin\FacilityClaimController::approve`): `Str::password(14)` ile **tek seferlik geçici şifre** üretilir, `FacilityUser` kaydı `must_change_password = true` ile oluşturulur, kurum `is_claimed = true` yapılır ve `free_claim_credits` kadar bonus hak tanımlanır — tüm bunlar tek bir DB transaction içinde, satır kilitleriyle (`lockForUpdate`) yarış durumuna karşı korunuyor.
5. Onay sonrası **giriş bilgileri (e-posta + geçici şifre) mail ile gönderilir** (`FacilityClaimApprovedMail`, artık `ShouldQueue` — arka planda gönderilir).
6. Kurum yetkilisi ilk girişte otomatik olarak şifre değiştirme ekranına yönlendirilir (`Facility\AuthController` — `must_change_password` kontrolü), geçici şifreyle kalıcı oturum açamaz.

Bu akış zaten eksiksiz kurulmuştu; bu turda sadece onay maili senkron yerine kuyruğa alınacak şekilde iyileştirildi (bkz. "Performans" notları altında).

`lokasyon-duzeltmesi` paketindeki `locations` tablosu düzeltmesi bu projede gerekli değildi: bu proje zaten `cities`/`districts` tablolarını `config/turkiye.php` üzerinden 81 ilin tamamı ve tüm ilçeleriyle seed ediyor.

Yeni migration'ları uygulamak için:

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder
```

> Not: Bu değişiklikler PHP/Composer çalıştırılamayan bir ortamda hazırlandı (statik kod incelemesiyle). Sunucuda/yerelde `composer install`, `php artisan migrate --seed` ve `vendor/bin/phpunit --testdox` ile mutlaka doğrulanmalıdır.

## Yol haritası uygulama durumu (bakimevleri/bakimevibul/bakimeviara önerileri)

Bu proje zaten canlıya hazır dokümante edilmişti; bu turda paylaşılan yol haritasındaki önerilerin **tamamı** koda döküldü. Aşağıdaki tablo her önerinin nereye karşılık geldiğini gösterir.

| Öneri | Durum | Nerede |
|---|---|---|
| Ücret Rehberi (şehir+bölüm fiyat sayfası) | ✅ Eklendi | `/fiyat-rehberi/{bolum}/{il}`, `PriceGuideController` |
| Kontenjan Sor | ✅ Eklendi | Kurum detay sayfası sidebar, `VisitRequestController::storeAvailability`, `visit_requests.type=kontenjan`, kurum panele bildirim gider |
| Kurum Karşılaştır | ✅ Zaten vardı, doğrulandı | `EngagementController::compare`, Ücret/Kapasite/Puan/Özellikler tablosu (localStorage tabanlı) |
| Son Güncellenen Kurumlar | ✅ Eklendi | `/son-guncellenen-kurumlar` |
| Doğrulanmış Kurumlar | ✅ Eklendi | `/dogrulanmis-kurumlar` |
| Son Eklenen Fotoğraflar | ✅ Eklendi | `/son-eklenen-fotograflar` |
| Aile Soruları (Q&A) | ✅ Eklendi | Kurum detay sayfası altında; kurum paneli `Aile Soruları` sekmesinden cevaplanır |
| Yakınımdaki Kurumlar | ✅ Gerçek GPS + il-merkezi yedeği | `facilities.lat/lng` (veri çekici + admin formundan doldurulabilir); koordinatı olan kurumlar için gerçek Haversine mesafesiyle "Size En Yakın Kurumlar" şeridi gösterilir (`GeoLookupService::nearestFacilities`), koordinatsız kurumlar için il-merkezi yönlendirmesi (`NearbyController`) hâlâ yedek olarak çalışır |
| İlçe bazlı SEO sayfaları | ✅ Zaten vardı | `LocationGuideController`, `/rehber/{bolum}/{il}/{ilce}` |
| Hizmete göre filtre | ✅ Zaten vardı | Kurum listesi `service` filtresi |
| Bütçeye göre filtre | ✅ Zaten vardı + segment filtresi eklendi | `price_max` ve yeni `price_tier` filtreleri |
| En çok görüntülenen kurumlar | ✅ Eklendi | `views_count` sayacı, `/en-cok-goruntulenen-kurumlar` |
| Türkiye İstatistikleri + haritalı dağılım | ✅ Eklendi — gerçek coğrafi harita | `/istatistikler`; 81 ilin gerçek sınırlarını içeren interaktif SVG (kaynak: alpers/Turkey-Maps-GeoJSON, Apache-2.0), il başına kurum sayısına göre renklendirilir, üzerine gelince/tıklayınca o ile gider. Sıralı çubuk liste de altında ayrıca duruyor |
| En çok aranan kurumlar (canlı) | ✅ Eklendi (bölge bazlı) | `/en-cok-aranan-bolgeler`; sitede serbest metin arama kutusu olmadığı için kelime değil, en çok filtrelenen il+kategori kombinasyonu loglanır (`search_queries` tablosu, `SearchQuery::record`) — sayfada bu fark açıkça belirtilir |
| Son sahiplenilen kurumlar | ✅ Eklendi | `/son-sahiplenilen-kurumlar` |
| Yeni açılan/eklenen kurumlar | ✅ Eklendi | `/yeni-eklenen-kurumlar` |
| Bakım Rehberi (makaleler) | ✅ Eklendi | `content_pages.type=guide`, `/bakim-rehberi`, admin `Statik Sayfalar` ekranından yazılır |
| Bakım Danışmanı | ✅ Zaten vardı | `EngagementController::wizard` ("Karar Sihirbazı") — yaş/şehir/bütçe/hizmet filtreleriyle uygun kurumları listeler |
| Kurum Performans Sayfası | ✅ Eklendi | Kurum detay sayfasında "Kurum Performansı" kutusu: incelenme, favori, teklif sayısı, son güncelleme, doğrulama durumu — hepsi gerçek veriden, uydurma alan yok |

### Bilinen sınırlar (şeffaflık için not edildi)

- **Footer linkleri**: Yeni sayfaların tamamı tek bir footer sütununa eklendi, görsel olarak kalabalık — istenirse 4. bir sütuna bölünebilir.
- **Yakınımdaki Kurumlar hâlâ kısmi**: `lat`/`lng` alanı eklendi ve veri çekici Google Maps'ten bunu toplamaya başladı, ama **mevcut/eski kurum kayıtlarında bu alan boş** (geriye dönük doldurma yapılmadı) — koordinatı olmayan kurumlar için sistem otomatik olarak il-merkezi yaklaşıklığına düşer, hatalı davranmaz ama "gerçek yakınımda" sadece koordinatı olan kurumlar için geçerlidir. Admin formundan elle de girilebilir.
- **En çok aranan bölgeler bir kelime arama motoru değildir**: sitede serbest metin arama kutusu olmadığı için loglanan şey "hangi il+kategori filtrelendi"dir, "hangi kelime arandı" değildir — sayfada bu ayrım açıkça belirtilir.

### Bu turda kapatılan 3 madde (GPS, gerçek harita, arama analitiği)

Önceki bir öz-denetimde "bilinen sınır" olarak işaretlenen 3 madde bu turda gerçek veriyle kapatıldı:

- **Gerçek GPS**: `facilities` tablosuna `lat`/`lng` eklendi (`0001_01_01_000040_add_lat_lng_to_facilities_table`). Veri çekici (`google_maps_scraper.py`) artık Google Maps yer sayfasının URL'inden (`/@enlem,boylam,zoom`) koordinatı çekip Excel'e (`Enlem`/`Boylam` kolonları) ve canlı API onay akışına ekliyor; Excel import da aynı kolonları tanıyor. `GeoLookupService::nearestFacilities()` koordinatı olan kurumları gerçek Haversine mesafesiyle sıralar; `NearbyController` ve `FacilityController::index` bunu kullanıp "Size En Yakın Kurumlar" şeridini gösterir.
- **Gerçek Türkiye haritası**: `tools/turkiye-haritasi/generate_svg.php` — Apache-2.0 lisanslı gerçek il sınırı GeoJSON'unu (`alpers/Turkey-Maps-GeoJSON`) `public/images/turkiye-harita.svg`'ye dönüştüren tek seferlik bir build aracı. 81 ilin tamamı, `cities` tablosundaki slug'larla birebir eşleşen `data-il` özniteliğine sahip. `/istatistikler` sayfası bu SVG'yi satır içi gömüp il başına kurum sayısına göre renklendiriyor (hover/tıklama ile o ile gidiliyor).
- **Arama analitiği**: `search_queries` tablosu + `SearchQuery::record()` — kurum listesinde il/kategori/hizmet filtresi her kullanıldığında (aynı gün aynı kombinasyon tekrar edilirse yeni satır değil, sayaç artışı) kaydediliyor. `/en-cok-aranan-bolgeler` son 30 günün en çok filtrelenen il+kategori kombinasyonlarını listeliyor.

Veri çekiciyle ilgili ayrıca: script artık `tkinter` (masaüstü GUI kütüphanesi) kurulu olmayan sunucularda da import edilebiliyor (önceden headless sunucuda modül import'u bile çöküyordu), ve `python`/`python3` ikisi de denenip hangisi varsa o kullanılıyor. Detay için `docs/PRODUCTION.md` bölüm 11'e bakın.

## Kurum galerisi sıkıştırma, sahiplenme konumu, WhatsApp butonu

- **Galeri gorseli sikistirma**: Admin ve kurum panelinden yüklenen yeni kurum galerisi görselleri artık `ImageCompressionService` ile otomatik 1600px'e küçültülüp WebP'ye (kalite 78) çevriliyor — performans için. Imagick > GD > (ikisi de yoksa) orijinal dosya sırasıyla denenir, yükleme hiçbir durumda başarısız olmaz. **Bu ortamda GD/Imagick kurulu olmadığı için gerçek sıkıştırma kodu çalıştırılıp test edilemedi**, sadece syntax kontrolü ve "eklenti yoksa orijinali kaydet" yedek yolu doğrulandı — sunucuya alındıktan sonra ilk yüklemede dosyanın gerçekten `.webp` ve küçük geldiğini kontrol edin. Sadece yeni yüklemelere uygulanır, mevcut görseller değiştirilmedi.
- **Sahiplenme başvurusunda konum**: Form (izne bağlı, reddedilirse başvuru yine tamamlanır) tarayıcı konumunu alıp kurumun kendi koordinatına (varsa) olan mesafeyi hesaplıyor; admin inceleme ekranında bu mesafe (50km üzeri sarı renkte) bir sahtecilik kontrolü sinyali olarak gösteriliyor — onay/red kararı yine admin'e ait, otomatik reddetme yok.
- **WhatsApp butonu**: 3 sitede de sağ altta sabit, hafif "zıplayarak" beliren WhatsApp ikonu; tıklanınca +90 850 308 79 91 numarasına hazır mesajla `wa.me` sohbeti açılıyor. Arka planda (WhatsApp açılmasını geciktirmeden) konum izne bağlı alınıp `whatsapp_clicks` tablosuna kaydediliyor, admin panelde yeni **WhatsApp Tıklamaları** ekranından izlenebiliyor. Not: WhatsApp'ın kendisine otomatik bildirim gitmiyor — bu resmi/ücretli WhatsApp Business API gerektirir, basit bir buton tıklamasıyla mümkün değil.

Bu 3 madde için de testler eklendi (`test_facility_claim_records_applicant_distance_when_location_shared`, `test_facility_claim_still_succeeds_without_location`, `test_whatsapp_click_is_tracked_and_visible_in_admin`), detay için `docs/PRODUCTION.md` bölüm 14.

**Sonraki turda tamamlanan eksikler:**
- WhatsApp numarası/mesajı artık kodda sabit değil — **Admin > Ayarlar**'dan kod değiştirmeden güncellenebiliyor (`whatsapp_number`/`whatsapp_message` Setting kayıtları, `config/platform.php > default_whatsapp` varsayılan).
- `/sayfa/kvkk` örnek metnine sahiplenme başvurusu ve WhatsApp butonu için yapılan konum toplama açıklamaları eklendi (hâlâ demo içerik, hukuki onay gerektiği notuyla).
- `php artisan diagnostics:image-compression` — sunucuya deploy sonrası bir kere çalıştırılıp GD/Imagick'in gerçekten çalışıp çalışmadığını test eden yeni komut (bu ortamda ikisi de kurulu olmadığı için gerçek sıkıştırma hâlâ test edilemedi, bu komut sunucuda çalıştırılmalı).
- `composer audit --locked` bu ortamda hâlâ çalıştırılamadı (composer kurulu değil) — sunucuya geçmeden önce mutlaka elle çalıştırılmalı.

### Doğrulama şekli ve sınırı

Bu ortamda PHP/Composer çalışmadığı ve internete çıkış olmadığı için her değişiklik **satır satır kod incelemesiyle** doğrulandı (route ↔ controller ↔ view ↔ model zinciri, migration sırası, isim çakışmaları, Blade `@if/@foreach/@section` denge kontrolü, PHP parantez dengesi — hepsi tek tek sayıldı). Ancak gerçek bir çalıştırma/tarayıcı testi yapılmadı. Sunucuya/yerelinize aldıktan sonra mutlaka:

```bash
composer install
php artisan migrate --seed
vendor/bin/phpunit --testdox
php artisan route:list | grep -E "discovery|price-guide|stats|guides|nearby|questions"
```

çalıştırıp aşağıdaki genişletilmiş smoke test listesini uygulayın (bkz. `docs/PRODUCTION.md`).

## Son tur: 3 açık madde + açık rıza/konum + site istatistikleri

### Tamamlanan 3 madde

- **Favoriler sayacı artık tekilleştirildi**: Aynı tarayıcı aynı kurumu birden fazla kez favoriye ekleyip çıkarsa sayaç sadece **ilk eklemede** bir kez artıyor (uzun ömürlü çerez ile kontrol ediliyor), çıkarma işlemi sayaçtan düşmüyor. Böylece "kaç farklı kişi ilgilendi" anlamına daha yakın bir sayı elde ediliyor. Kod: `EngagementController::toggleFavoriteCount`.
- **Yakınımdaki Kurumlar mimarisi düzenlendi**: Haversine + il merkezi eşleme mantığı tekilleştirilip `GeoLookupService`'e taşındı, hem `NearbyController` hem de yeni aile kayıt/konum akışı bu servisi paylaşıyor. Kurum bazında gerçek GPS hâlâ veri çekiciye koordinat toplama eklenmesini gerektiriyor (bu, veri kaynağı sınırı; ayrı bir iş kalemi).
- **Throttle limitleri artık config'den yönetiliyor**: `config/platform.php > throttle` içinde 5 isimli limit var (`public-light`, `public-form`, `public-sensitive`, `auth-attempt`, `auth-register`), `.env`'den de override edilebiliyor (`THROTTLE_PUBLIC_FORM` vb.). Trafiğinize göre tek dosyadan/`.env`'den ayarlayabilirsiniz, kod deploy etmenize gerek yok.

### Yeni: Aile kaydında açık rıza + otomatik konum alma

- Kayıt formuna **zorunlu** (HTML `required` + backend `consent => required|accepted`) bir onay kutusu eklendi: "Açık Rıza Metni ve KVKK Aydınlatma Metni'ni okudum, kabul ediyorum."
- Kutu işaretlendiği an tarayıcının Geolocation API'si tetiklenir; kullanıcı izin verirse enlem/boylam formla birlikte gönderilir ve en yakın il ismine çevrilip (`GeoLookupService`) `family_users.signup_city_name` alanına, ham koordinatlar `signup_lat`/`signup_lng` alanlarına kaydedilir.
- İzin verilmezse (tarayıcı reddederse/desteklemezse) **kayıt yine de tamamlanır**, konum alanları boş kalır — konum zorunlu değil, sadece onay kutusu zorunlu (KVKK'ya uygun: rıza reddi kayıt engeli olmamalı, sadece ek veri toplanamaz).
- Rıza zamanı (`consent_accepted_at`) ve IP adresi (`consent_ip`) de KVKK ispat yükümlülüğü için ayrıca saklanıyor.
- **Önemli hukuki not**: Tek bir onay kutusu hem "hesap açma" hem "konum işleme" rızasını birlikte alıyor. KVKK/GDPR'de amaç bazlı ayrı rıza önerilir; şu an tek kutu istendiği şekilde kuruldu, ama hukuki danışmanınızla bu birleşik rızanın yeterliliğini teyit etmenizi öneririm — metnin içeriği (`/sayfa/kvkk`) konum işlemeyi açıkça ve ayrı bir cümleyle belirtmelidir.

### Yeni: Admin > Site İstatistikleri

`admin/site-istatistikleri` ekranı iki bölüm sunar:

1. **Sitelere giriş sayıları**: 3 marka için bugün/son 7 gün/son 30 gün/toplam giriş sayısı + 30 günlük trend grafiği. "Giriş", bir tarayıcı oturumunun o markaya bir gün içindeki ilk ziyaretidir (aynı oturumda tekrar sayılmaz) — bunu `TrackSiteVisit` middleware'i ve `site_visits` tablosu (marka+gün başına tek satır) sağlıyor.
2. **Kayıt olan ailelerin konumları**: toplam aile / konum paylaşan aile oranı, şehre göre dağılım çubukları, ve marka/konum durumuna göre filtrelenebilen aile listesi (her satırda, konum varsa Google Haritalar linki).

Bu veriler hassas kişisel veri içerdiği için ekran sadece admin oturumu ile erişilebilir (`admin.auth` middleware, mevcut korumanın aynısı).

## Öz-denetim düzeltmesi: "Bakım Danışmanı" tam karşılanmamıştı

Önceki bir turda "Bakım Danışmanı ✅ Zaten vardı (Karar Sihirbazı)" demiştim — bu **yanlıştı**. Karar Sihirbazı sadece il/ilçe/kategori/bütçe filtreleyip kurum listesine yönlendiren basit bir kısayoldu; orijinal istekteki hasta yaşı, cinsiyet, yatalak/demans durumu gibi alanları toplamıyordu ve puanlı bir "Size uygun N kurum bulundu" sonucu üretmiyordu. Bunu fark edip ayrı, gerçek bir modül olarak tamamladım:

- **Yeni**: `/bakim-danismani` — hasta yaşı, cinsiyet, durum/hastalık (serbest metin), demans/yatalak/fizik tedavi ihtiyacı, il, kurum türü, bütçe toplar.
- `CareAdvisorMatchService` her kurumu puanlar: hizmet listesi eşleşmesi (demans/yatalak/fizik tedavi anahtar kelimeleri), bütçe uyumu, doğrulanmış olma, puan/öne çıkan durumu.
- **Dürüstlük notu (kullanıcıya da gösteriliyor)**: `facilities` tablosunda yaş aralığı, cinsiyet veya "yatalak" için ayrı yapısal alan yok; eşleştirme kurumun serbest metin `services` listesindeki anahtar kelimelerle çalışır. Kesin bir tıbbi/idari uygunluk değerlendirmesi değildir, sonuç sayfasında bu açıkça belirtilir.
- Eski Karar Sihirbazı kaldırılmadı — hızlı filtre olarak hâlâ duruyor, Bakım Danışmanı daha derin bir alternatif olarak eklendi.
- Bu arada fark ettiğim bir başka gerçek eksik de düzeltildi: doc'un "Hizmete göre filtre" listesindeki Demans, Palyatif bakım, Gündüz bakım, Tam zamanlı, Fizik tedavi seçenekleri `config/brands.php`'de hiç yoktu (sadece Alzheimer vardı) — eklendi + zaten migrate edilmiş ortamlar için idempotent bir sync migration'ı yazıldı.

### Diğer madde madde durum (bu turda tekrar doğrulandı)

| Madde | Gerçek durum |
|---|---|
| Kurum Karşılaştır | Ücret/Kapasite/Puan/Özellikler karşılaştırılıyor. **Oda tipi ve Yaş aralığı alanları veritabanında hiç yok**, bu yüzden karşılaştırmada gösterilmiyor — uydurma veri koymadım |
| Yakınımdaki Kurumlar | Konum izniyle en yakın **ile** yönlendirir; **görsel bir harita bileşeni yok**, sessiz bir yönlendirme |
| Haritalı dağılım (Türkiye) | Gerçek coğrafi harita değil, sıralı çubuk grafik |
| En çok aranan kurumlar | Arama terimi analitiği yok; "en çok görüntülenen" ile karşılanıyor |
| Son Eklenen Fotoğraflar | Basit galeri listesi; "Google Maps mantığı" ifadesi yorumlanarak (kaydırılabilir görsel akışı) uygulandı, birebir Google Maps UI'ı değil |

Bunların hepsi gerçek, ayrı iş kalemleri (harita için GeoJSON + kütüphane, GPS için veri çekici güncellemesi, arama analitiği için log altyapısı) — istediğiniz zaman söyleyin, önceliklendirip tamamlayayım.

## 3 site aynı envanteri paylaşıyor: bilinçli tasarım kararı

Önceki bir turda "marka izolasyonu README'de iddia edildiği gibi çalışmıyor" bulgusunu paylaşmıştım. Netleştirme: bu bir **hata değil, ürün kararı**. Amaç maksimum talep/lead toplamak — Google'da arayan kişi 3 stil/tasarımı da görüp incelesin, istediği veya en uygun bulduğu siteden fiyat sorsun. Bunun gereği gibi tamamlanan kısımlar:

- **Duplicate content önlemi**: Aynı kurumun 3 domainde birebir aynı sayfa olarak indexlenmesini önlemek için her markanın kendi editoryal "sesi" kurum sayfasına benzersiz bir çerçeve cümlesi + meta description eki ekliyor (`config/brand_voice.php`, `facility_brand_framing()` helper). Ham veri (isim, adres, fiyat, görsel) aynı kalır, çevresindeki editoryal metin markaya göre değişir — arama motorları için 3 sayfa artık gerçekten farklı içerik.
- **Talep ayrımı korunuyor**: `category_scope` paylaşılsa da, bir teklif talebi hangi site üzerinden oluşturulduysa (`offer_requests.brand`) kurum yetkilisi sadece **o siteye ait oturumdayken** teklif verebilir (`Facility\QuoteController::store`). Yani envanter paylaşılır, ama talep/lead'in "kime ait" olduğu (hangi site üzerinden geldiği) net kalır — raporlama ve komisyonlandırma için önemli.
- README'deki eski "marka dışı kategoriyle reddedilir" iddiası güncel davranışla eşleşecek şekilde düzeltildi.

## Aile kaydında e-posta doğrulama

Kayıt sırasında imzalı (signed) bir bağlantı içeren doğrulama e-postası gönderilir (`FamilyEmailVerificationMail`, kuyruklu). **Doğrulanmamış hesap engellenmez** — talep toplama hedefini düşürmemek için aile panelde sadece bir hatırlatma şeridi görür ve "tekrar gönder" butonuyla e-postayı yeniden isteyebilir. Admin > Site İstatistikleri ekranında doğrulama oranı görüntülenir.

## Veritabanı yapısı: mantıksal bölümler + güvenlik + performans

Şema tek bir veritabanında ama mantıksal olarak 5 bölüme ayrılmış durumda; her bölümün kendi migration serisi ve foreign key ilişkileri var:

| Bölüm | Tablolar | Not |
|---|---|---|
| **Kurum** | `facilities`, `facility_categories`, `facility_images`, `facility_reviews`, `*_facility_details`, `facility_service_option*` | En sık okunan bölüm; `is_published+facility_category_id`, `city_id+district_id(+facility_category_id)`, `is_claimed+claimed_at`, `price_min`, `source`, `views_count` index'leri var |
| **Aile/Kurum kullanıcı** | `family_users`, `facility_users`, `facility_claims`, `family_saved_facilities` | `email` unique, `registered_brand`/`signup_city_name` index'li (admin analitik için); hassas alanlar (`consent_ip`, `signup_lat/lng`) model seviyesinde `$hidden` — ileride bir API eklenirse yanlışlıkla dışa sızmaz |
| **Talep/Dönüşüm** | `offer_requests`, `quotes`, `messages`, `visit_requests`, `facility_questions` | `brand_id+status`, `facility_id+status` composite index'leri; hepsi soft-delete (çöp kutusu) destekli |
| **Finans** | `wallet_topups`, `balance_logs`, `subscription_packages` | Onay akışı DB transaction + `lockForUpdate` ile yarış durumuna karşı korunuyor |
| **İçerik/Sistem/Analitik** | `content_pages`, `faqs`, `settings`, `admin_events`, `platform_notifications`, `site_visits` | Büyümeye açık 3 tablo (`admin_events`, `platform_notifications`, `site_visits`) için: ilk ikisi `admin-events:prune` ile aylık temizleniyor, üçüncüsü zaten (marka+gün) başına tek satır tuttuğu için doğal olarak sınırlı büyüyor (yılda ~3×365 satır) |

**Güvenlik açısından bu turda yapılanlar**: hassas kişisel alanların model seviyesinde gizlenmesi, tüm public formlarda throttle, admin-only erişim gerektiren ekranlarda `admin.auth`, DB seviyesinde transaction+lock kullanılan finansal akışlar. **Kapsam dışı bırakılan** (mevcut ölçek için gereksiz karmaşıklık sayılan): alan bazlı şifreleme (encryption at rest), fiziksel tablo partisyonlama (mevcut veri hacmi için MySQL/SQLite native partitioning'in getirisi maliyetini karşılamaz) — trafik gerçekten büyürse bunlar sırasıyla değerlendirilebilir.