<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\BalanceLog;
use App\Models\City;
use App\Models\ContentPage;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// UYARI: bu seeder sabit/bilinen demo sifreleri (Admin12345!, Kurum12345!,
// Aile12345!) olusturur. Bunlar repo'da/README'de aciktir; canliya asla
// tasinmamalidir. Sehir/kategori/sayfa/SSS/paket gibi GERCEK referans
// verileri her ortamda seed edilir; sadece bilinen-sifreli demo HESAPLAR
// ve demo kurum/soru verisi asagida $seedDemoAccounts ile local/testing
// disinda atlanir. Production admin hesabi ayrica, gercek bir sifreyle
// (ornegin `php artisan tinker` ile tek seferlik) olusturulmalidir.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seedDemoAccounts = app()->environment(['local', 'testing']);

        if (! $seedDemoAccounts) {
            $this->command?->warn('DatabaseSeeder: bu ortamda demo hesaplar (bilinen sifreler) atlandi; sadece sehir/kategori/sayfa/SSS/paket gibi referans veriler seed edildi. Admin hesabini ayrica, gercek bir sifreyle olusturun.');
        }

        if ($seedDemoAccounts) {
            Admin::updateOrCreate(
                ['email' => 'admin@bakimplatform.test'],
                ['name' => 'Süper Admin', 'password' => Hash::make('Admin12345!'), 'role' => 'superadmin']
            );
        }

        Setting::set('bank_name', 'Örnek Banka A.Ş.');
        Setting::set('bank_account_holder', 'Bakım Platformu A.Ş.');
        Setting::set('bank_iban', 'TR12 0006 4000 0011 2345 6789 01');
        Setting::set('quote_price', 50);

        $cityNames = array_keys(config('turkiye.provinces', []));
        $cities = collect($cityNames)->map(fn ($name) => City::updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        ));

        $categories = collect([
            ['name' => 'Yaşlı Bakım Evi', 'brand_scope' => 'yasli-bakim'],
            ['name' => 'Huzurevi', 'brand_scope' => 'yasli-bakim'],
            ['name' => 'Çocuk Bakım Merkezi', 'brand_scope' => 'cocuk-bakim'],
            ['name' => 'Kreş ve Anaokulu', 'brand_scope' => 'cocuk-bakim'],
            ['name' => 'Özel Eğitim ve Gelişim Merkezi', 'brand_scope' => 'ozel-egitim'],
            ['name' => 'Fizik Tedavi ve Rehabilitasyon', 'brand_scope' => 'rehabilitasyon'],
            ['name' => 'Nörolojik Rehabilitasyon Merkezi', 'brand_scope' => 'fizik-tedavi'],
        ])->map(fn ($c) => FacilityCategory::updateOrCreate(
            ['slug' => Str::slug($c['name'])],
            $c
        ));

        $servicesPoolBySection = [
            'yasli-bakim' => ['7/24 hemşire', 'Doktor kontrolü', 'Alzheimer bakımı', 'Demans bakımı', 'Palyatif bakım', 'Gündüz bakım', 'Tam zamanlı bakım', 'Fizik tedavi', 'Fiziksel aktivite', 'Diyetisyen', 'Sosyal etkinlik', 'Özel oda', 'Bahçe alanı'],
            'cocuk' => ['Yaş grubu', 'Oyun alanı', 'Rehberlik servisi', 'Servis imkanı', 'Yemek programı', 'Uyku odası', 'Özel eğitim', 'Dil/atölye programı'],
            'rehabilitasyon' => ['Fizyoterapist', 'Nörolojik rehabilitasyon', 'Ortopedik rehabilitasyon', 'Hidroterapi', 'Ergoterapi', 'Konuşma terapisi', 'Evde takip', 'Cihaz desteği'],
        ];

        if ($seedDemoAccounts && Facility::count() === 0) {
            $createdFacilities = collect();

            foreach (range(1, 24) as $i) {
                $category = $categories->random();
                $city = $cities->random();
                $districts = districts_for_city($city->name);
                $name = $category->name.' - '.$city->name.' Merkez '.$i;
                $section = service_section_for_scope($category->brand_scope);
                $servicesPool = $servicesPoolBySection[$section['slug'] ?? 'yasli-bakim'] ?? $servicesPoolBySection['yasli-bakim'];

                $facility = Facility::create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'city_id' => $city->id,
                    'facility_category_id' => $category->id,
                    'district' => $districts[0] ?? 'Merkez',
                    'address' => $city->name.' Merkez Mahallesi No: '.$i,
                    'phone' => '0212 555 '.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                    'description' => 'Deneyimli kadro ve modern olanaklarla hizmet veren '.$category->name.' kurumudur. (örnek/demo veridir, ön kayıtlıdır)',
                    'capacity' => rand(15, 80),
                    'price_min' => rand(8, 20) * 1000,
                    'price_max' => rand(21, 40) * 1000,
                    'services' => collect($servicesPool)->shuffle()->take(rand(3, 6))->values()->all(),
                    'is_published' => true,
                    'is_featured' => $i % 5 === 0,
                    'rating' => round(rand(35, 50) / 10, 1),
                ]);

                $createdFacilities->push($facility);
            }

            $demoFacility = $createdFacilities->first();
            $demoFacility->update([
                'is_claimed' => true,
                'claimed_at' => now(),
                'free_quote_credits' => 5,
            ]);

            FacilityUser::create([
                'facility_id' => $demoFacility->id,
                'name' => 'Demo Kurum Yetkilisi',
                'email' => 'kurum@bakimplatform.test',
                'phone' => '0532 000 00 00',
                'password' => Hash::make('Kurum12345!'),
                'must_change_password' => false,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            BalanceLog::create([
                'facility_id' => $demoFacility->id,
                'type' => 'claim_bonus_credits',
                'amount' => 0,
                'credits_amount' => 5,
                'balance_after' => 0,
                'credits_after' => 5,
                'note' => 'Demo veri: sahiplenme bonus hakkı.',
            ]);

            \App\Models\FacilityQuestion::create([
                'facility_id' => $demoFacility->id,
                'brand' => 'bakimevibul',
                'asker_name' => 'Demo Ziyaretçi',
                'question' => 'Alzheimer hastası kabul ediyor musunuz?',
                'answer' => 'Evet, uzman ekibimiz Alzheimer hastalarına özel bakım planı hazırlıyor.',
                'answered_by' => null,
                'answered_at' => now(),
                'status' => 'answered',
            ]);
        }

        if ($seedDemoAccounts) {
            FamilyUser::updateOrCreate(
                ['email' => 'aile@bakimplatform.test'],
                [
                    'registered_brand' => 'bakimevibul',
                    'name' => 'Demo Aile',
                    'phone' => '0533 000 00 00',
                    'password' => Hash::make('Aile12345!'),
                    'consent_accepted_at' => now(),
                    'consent_ip' => '127.0.0.1',
                    'email_verified_at' => now(),
                    'signup_lat' => 40.1826,
                    'signup_lng' => 29.0669,
                    'signup_city_name' => 'Bursa',
                ]
            );
        }

        foreach (['bakimevibul', 'bakimeviara', 'bakimevleri'] as $brand) {
            ContentPage::updateOrCreate(
                ['brand' => $brand, 'slug' => 'hakkimizda'],
                ['title' => 'Hakkımızda', 'body' => '<p>Bu marka, aile/kullanıcıları ihtiyaca uygun bakım kurumlarıyla buluşturan eşleştirme platformudur. (örnek içerik, admin panelden düzenlenebilir)</p>']
            );

            $domain = config("brands.brands.{$brand}.name", $brand.'.com');
            foreach ($this->legalPages($domain) as $slug => $page) {
                ContentPage::updateOrCreate(
                    ['brand' => $brand, 'slug' => $slug],
                    ['title' => $page['title'], 'body' => $page['body']]
                );
            }

            if (Faq::where('brand', $brand)->count() === 0) {
                $faqs = [
                    ['q' => 'Platform üzerinden teklif almak ücretli mi?', 'a' => 'Aile/ziyaretçi için tamamen ücretsizdir. Kurumlar teklif başına ücretlendirilir.'],
                    ['q' => 'Kurumumu nasıl sahiplenebilirim?', 'a' => 'Kurum detay sayfasındaki "Sahiplen" butonundan başvuru yapıp evrak yükleyebilirsiniz, admin onayından sonra hesabınız aktifleşir.'],
                    ['q' => 'Bakiyemi nasıl yükleyebilirim?', 'a' => 'Kurum panelinden banka hesabına havale/EFT yapıp dekont görselini yükleyebilirsiniz.'],
                ];
                foreach ($faqs as $i => $faq) {
                    Faq::create([
                        'brand' => $brand,
                        'question' => $faq['q'],
                        'answer' => $faq['a'],
                        'sort_order' => ($i + 1) * 10,
                        'is_active' => true,
                    ]);
                }
            }

            if (ContentPage::guides()->where('brand', $brand)->count() === 0) {
                $guides = [
                    ['title' => 'Huzurevi seçerken dikkat edilmesi gereken 15 konu', 'summary' => 'Ziyaret öncesi hazırlık, personel oranı, sağlık takibi ve sözleşme detayları için pratik kontrol listesi.'],
                    ['title' => 'Kreş seçerken sorulacak sorular', 'summary' => 'Güvenlik, beslenme, gelişim takibi ve iletişim süreçleri hakkında ailelerin sorması gereken sorular.'],
                    ['title' => 'Rehabilitasyon merkezi nasıl seçilir', 'summary' => 'Uzman kadro, cihaz altyapısı ve ev programı takibi açısından değerlendirme kriterleri.'],
                ];
                foreach ($guides as $guide) {
                    ContentPage::create([
                        'brand' => $brand,
                        'type' => 'guide',
                        'title' => $guide['title'],
                        'summary' => $guide['summary'],
                        'slug' => Str::slug($guide['title']),
                        'body' => '<p>'.$guide['summary'].'</p><p>(Örnek/demo içerik, admin panelden genişletilebilir.)</p>',
                    ]);
                }
            }
        }

        if (SubscriptionPackage::count() === 0) {
            SubscriptionPackage::create(['name' => 'Başlangıç Paketi', 'description' => '5 teklif hakkı içerir.', 'price' => 500, 'bonus_quote_credits' => 5, 'sort_order' => 10]);
            SubscriptionPackage::create(['name' => 'Büyüme Paketi', 'description' => '15 teklif hakkı içerir, en çok tercih edilen paket.', 'price' => 1200, 'bonus_quote_credits' => 15, 'sort_order' => 20]);
            SubscriptionPackage::create(['name' => 'Kurumsal Paket', 'description' => '40 teklif hakkı içerir.', 'price' => 3000, 'bonus_quote_credits' => 40, 'sort_order' => 30]);
        }
    }

    /**
     * KVKK, Gizlilik Politikasi, Kullanim Sartlari, Cerez Politikasi — her
     * marka icin ayni icerik, sadece {domain} yer tutucusu marka adiyla
     * degistirilerek kullanilir. Platformun gercek veri akislarina
     * (konum izni, banka havalesiyle bakiye yukleme, sahiplenme surecu,
     * sadece zorunlu oturum/CSRF cerezi kullanimi vb.) gore yazilmistir.
     */
    private function legalPages(string $domain): array
    {
        $kvkkBody = '<h2>1. Veri Sorumlusu</h2>'
            .'<p>İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca, '.$domain.' ("Platform") üzerinden topladığımız kişisel verileriniz hakkında sizi bilgilendirmek amacıyla, veri sorumlusu sıfatıyla Bakım Platformu A.Ş. tarafından hazırlanmıştır.</p>'
            .'<h2>2. İşlenen Kişisel Veri Kategorileri</h2>'
            .'<ul>'
            .'<li><strong>Kimlik ve iletişim bilgileri:</strong> ad-soyad, e-posta adresi, telefon numarası.</li>'
            .'<li><strong>Hesap bilgileri:</strong> kullanıcı adı/şifresi (şifreleriniz geri döndürülemez biçimde saklanır), hesap türü (aile/kurum/admin).</li>'
            .'<li><strong>Konum bilgisi:</strong> hesap oluşturma veya sahiplenme başvurusu sırasında tarafınızca izin verilmesi hâlinde, tarayıcınızın paylaştığı yaklaşık konum (genellikle il düzeyinde); WhatsApp butonuna tıklama anında da aynı şekilde izne bağlı olarak alınabilir.</li>'
            .'<li><strong>İşlem/kullanım verileri:</strong> gönderdiğiniz teklif talepleri, mesajlar, değerlendirme/yorumlar, sorular, kurum sahiplenme başvuruları.</li>'
            .'<li><strong>Finansal veriler:</strong> kurum kullanıcıları için banka havale/EFT dekontu görseli, bakiye/kredi hareket geçmişi (kart bilgisi alınmaz, ödemeler doğrudan banka havalesi ile yapılır).</li>'
            .'<li><strong>Belge verileri:</strong> kurum sahiplenme başvurusunda yüklenen kimlik/yetki belgesi görselleri.</li>'
            .'<li><strong>Teknik veriler:</strong> IP adresi, tarayıcı bilgisi, ziyaret/log kayıtları.</li>'
            .'</ul>'
            .'<h2>3. Kişisel Verilerin İşlenme Amaçları</h2>'
            .'<ul>'
            .'<li>Hesabınızı oluşturmak, doğrulamak ve yönetmek,</li>'
            .'<li>Aile/ziyaretçileri ihtiyaca uygun bakım kurumlarıyla eşleştirmek,</li>'
            .'<li>Teklif talebi, mesajlaşma, değerlendirme ve soru-cevap süreçlerini yürütmek,</li>'
            .'<li>Kurum sahiplenme başvurularının gerçekliğini ve yetkisini kontrol etmek,</li>'
            .'<li>Bakiye/kredi yüklemelerini ve kurum faturalandırmasını yönetmek,</li>'
            .'<li>Platform güvenliğini sağlamak, kötüye kullanımı önlemek,</li>'
            .'<li>Yasal yükümlülüklerimizi yerine getirmek,</li>'
            .'<li>Hizmet kalitesini ölçmek ve platformu geliştirmek.</li>'
            .'</ul>'
            .'<h2>4. Konum Verisi Hakkında Ayrıntılı Bilgi</h2>'
            .'<p><strong>Hesap oluşturma:</strong> Kayıt ekranındaki onay kutusunu işaretlemeniz ve tarayıcınızın izin vermesi hâlinde cihazınızın yaklaşık konumu alınır; bu bilgi size en yakın hizmet sağlayan kurumları önermek ve platform istatistikleri için kullanılır. Konum paylaşımını reddetmeniz hesap oluşturmanızı engellemez.</p>'
            .'<p><strong>Kurum sahiplenme başvurusu:</strong> Bir kurumu sahiplenmek için başvuru yaptığınızda, tarayıcınız izin verirse konumunuz alınır ve başvurunuzun ilgili kurumun adresine yaklaşık uzaklığı hesaplanarak admin incelemesinde bir kontrol bilgisi olarak kullanılır. İzin vermemeniz başvurunuzu engellemez.</p>'
            .'<p><strong>WhatsApp butonu:</strong> Sitedeki WhatsApp butonuna tıkladığınızda, tarayıcınız izin verirse yaklaşık konumunuz (il düzeyinde) ve tıklama zamanı platform yöneticisine iletişim talebi kaydı olarak iletilir. WhatsApp sohbetinin kendisi cihazınızdan doğrudan açılır; bu konum bilgisi WhatsApp\'a değil yalnızca Platform\'a kaydedilir.</p>'
            .'<h2>5. Kişisel Verilerin Aktarılması</h2>'
            .'<p>Kişisel verileriniz, yasal zorunluluklar dışında yurt içindeki barındırma/altyapı hizmeti sağlayıcılarımız (sunucu, e-posta gönderim altyapısı) ile sınırlı olarak ve yalnızca hizmetin sunulabilmesi için gerekli ölçüde paylaşılır. Verileriniz pazarlama amacıyla üçüncü taraflara satılmaz veya kiralanmaz.</p>'
            .'<h2>6. Kişisel Verilerin Saklanma Süresi</h2>'
            .'<p>Kişisel verileriniz, işlenme amacının gerektirdiği süre boyunca ve ilgili mevzuatta öngörülen zamanaşımı süreleri dikkate alınarak saklanır. Hesabınızı sildiğinizde, yasal saklama yükümlülüğü bulunmayan veriler makul bir süre içinde silinir veya anonim hale getirilir.</p>'
            .'<h2>7. Hukuki Sebep</h2>'
            .'<p>Kişisel verileriniz; bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması, hukuki yükümlülüğümüzün yerine getirilmesi, meşru menfaatimiz ve açık rızanızın bulunduğu hâllerde (özellikle konum verisi gibi opsiyonel veriler için) KVKK\'nın 5. ve 6. maddelerine uygun olarak işlenir.</p>'
            .'<h2>8. KVKK Madde 11 Kapsamındaki Haklarınız</h2>'
            .'<p>KVKK\'nın 11. maddesi uyarınca aşağıdaki haklara sahipsiniz:</p>'
            .'<ul>'
            .'<li>Kişisel verinizin işlenip işlenmediğini öğrenme,</li>'
            .'<li>İşlenmişse buna ilişkin bilgi talep etme,</li>'
            .'<li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme,</li>'
            .'<li>Yurt içinde/yurt dışında aktarıldığı üçüncü kişileri bilme,</li>'
            .'<li>Eksik/yanlış işlenmişse düzeltilmesini isteme,</li>'
            .'<li>KVKK\'nın 7. maddesindeki şartlar çerçevesinde silinmesini/yok edilmesini isteme,</li>'
            .'<li>Düzeltme/silme işlemlerinin aktarıldığı üçüncü kişilere bildirilmesini isteme,</li>'
            .'<li>Münhasıran otomatik sistemlerle analiz edilmesi sonucu aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>'
            .'<li>Kanuna aykırı işlenme nedeniyle zarara uğramanız hâlinde zararın giderilmesini talep etme.</li>'
            .'</ul>'
            .'<p>Bu haklarınızı kullanmak için hesabınızın iletişim ayarlarından veya "Soru Sor" formu üzerinden bizimle iletişime geçebilirsiniz. Talepleriniz en geç 30 gün içinde değerlendirilerek sonuçlandırılır.</p>'
            .'<h2>9. Açık Rıza</h2>'
            .'<p>Hesap oluşturma, kurum sahiplenme başvurusu veya WhatsApp butonu gibi opsiyonel konum paylaşımı gerektiren adımlarda ilgili onay kutusunu işaretleyerek veya ilgili butonu kullanarak, yukarıda açıklanan işleme faaliyetlerine açık rızanızı vermiş olursunuz. Rızanızı dilediğiniz zaman hesap ayarlarınızdan veya bizimle iletişime geçerek geri çekebilirsiniz; rızanızı geri çekmeniz, geri çekme tarihinden önceki işlemlerin hukuka uygunluğunu etkilemez.</p>';

        $gizlilikBody = '<h2>1. Genel Bilgilendirme</h2>'
            .'<p>Bu Gizlilik Politikası, '.$domain.' ("Platform") üzerinden kişisel verilerinizin nasıl toplandığını, kullanıldığını, korunduğunu ve haklarınızı açıklar. Kişisel verilerin işlenmesine ilişkin ayrıntılı bilgi için KVKK Aydınlatma Metni\'ni, çerezler için Çerez Politikası\'nı inceleyebilirsiniz.</p>'
            .'<h2>2. Hangi Bilgileri Topluyoruz</h2>'
            .'<p>Hesap oluştururken verdiğiniz ad-soyad, e-posta ve telefon bilgileri; kurum kullanıcıları için sahiplenme başvurusunda yüklenen belgeler ve bakiye yüklemesinde yüklenen banka dekontu görselleri; platformu kullanırken oluşturduğunuz teklif talebi, mesaj, değerlendirme ve soru içerikleri; izin vermeniz hâlinde yaklaşık konum bilginiz; teknik loglar (IP adresi, tarayıcı bilgisi, erişim zamanı).</p>'
            .'<h2>3. Bilgilerinizi Nasıl Kullanıyoruz</h2>'
            .'<ul>'
            .'<li>Hesabınızı yönetmek ve size hizmet sunmak,</li>'
            .'<li>Sizi ihtiyacınıza uygun kurumlarla eşleştirmek,</li>'
            .'<li>Kurum sahiplenme ve bakiye yükleme taleplerini incelemek,</li>'
            .'<li>Platform güvenliğini sağlamak ve kötüye kullanımı tespit etmek,</li>'
            .'<li>Yasal yükümlülüklerimizi yerine getirmek.</li>'
            .'</ul>'
            .'<h2>4. Bilgi Paylaşımı</h2>'
            .'<p>Kişisel verilerinizi reklam veya pazarlama amacıyla üçüncü taraflara satmayız veya kiralamayız. Verileriniz yalnızca; hizmetin sunulması için zorunlu olan barındırma/sunucu ve e-posta altyapısı sağlayıcılarımızla, yasal bir talep söz konusu olduğunda yetkili resmi mercilerle paylaşılabilir. Kurum profilinde görünen bilgiler (kurum adı, adres, telefon, hizmetler) niteliği gereği herkese açıktır; aile/ziyaretçi kullanıcıların kişisel bilgileri kurum profillerinde yayınlanmaz.</p>'
            .'<h2>5. Veri Güvenliği</h2>'
            .'<p>Şifreleriniz geri döndürülemez biçimde (hash\'lenerek) saklanır. Sunucu erişimleri yetkilendirme kontrolünden geçer, oturumlar güvenli çerez ayarlarıyla korunur ve platform HTTPS üzerinden hizmet verir. Buna rağmen internet üzerinden hiçbir aktarımın %100 güvenli olduğu garanti edilemez.</p>'
            .'<h2>6. Veri Saklama Süresi</h2>'
            .'<p>Verileriniz, hesabınız aktif olduğu sürece ve yasal saklama yükümlülüklerimizin gerektirdiği süre boyunca saklanır. Hesap silme talebinizde, yasal zorunluluk bulunmayan veriler makul süre içinde silinir.</p>'
            .'<h2>7. Çocukların Gizliliği</h2>'
            .'<p>Platform, 18 yaş altındaki kişilerin hesap açmasına yönelik değildir. Aile/veli hesapları, bakım ihtiyacı olan kişi adına yetişkin bir aile ferdi veya vasi tarafından oluşturulmalıdır.</p>'
            .'<h2>8. Haklarınız</h2>'
            .'<p>KVKK kapsamındaki haklarınız için KVKK Aydınlatma Metni\'ni inceleyebilir, taleplerinizi "Soru Sor" formu üzerinden iletebilirsiniz.</p>'
            .'<h2>9. Politika Değişiklikleri</h2>'
            .'<p>Bu Gizlilik Politikası zaman zaman güncellenebilir; güncel sürüm her zaman bu sayfada yayınlanır. Önemli değişikliklerde sizi platform içinden bilgilendirmeye çalışırız.</p>';

        $sartlarBody = '<h2>1. Taraflar ve Kabul</h2>'
            .'<p>Bu Kullanım Şartları, '.$domain.' ("Platform") ile Platform\'u kullanan aile/ziyaretçi kullanıcılar ve kurum kullanıcıları arasındaki ilişkiyi düzenler. Platform\'a erişerek veya hesap oluşturarak bu şartları kabul etmiş sayılırsınız.</p>'
            .'<h2>2. Platformun Niteliği</h2>'
            .'<p>Platform, bakım ihtiyacı olan aileleri; yaşlı bakım, çocuk bakım, özel eğitim ve rehabilitasyon alanlarında hizmet veren kurumlarla buluşturan bir <strong>eşleştirme/ilan platformudur</strong>. Platform, listelenen kurumların doğrudan işleteni, çalışanı veya temsilcisi değildir; bakım hizmetinin kendisini sunmaz. Kurum ile aile arasında kurulacak hizmet ilişkisi, tarafların kendi aralarındaki bir sözleşmedir; Platform bu ilişkinin tarafı değildir.</p>'
            .'<h2>3. Ön Kayıtlı Kurumlar</h2>'
            .'<p>Platform\'da listelenen bazı kurumlar, halka açık kaynaklardan (ör. Google Haritalar) derlenerek "ön kayıtlı" statüsünde, henüz kurum tarafından sahiplenilmemiş şekilde yayınlanır. Bu profillerdeki bilgiler kurum tarafından doğrulanmamış olabilir; kesin ve güncel bilgi için kurumla doğrudan iletişime geçmeniz veya kurumun sahiplenme sonrası güncellediği profili esas almanız önerilir. Bir kurumun kendisine veya bir yakınına ait olduğunu düşünen yetkililer, "Sahiplen" başvurusu yaparak profili devralabilir.</p>'
            .'<h2>4. Aile/Ziyaretçi Kullanıcı Yükümlülükleri</h2>'
            .'<ul>'
            .'<li>Hesap oluştururken doğru ve güncel bilgi vermekle,</li>'
            .'<li>Hesap bilgilerinizin gizliliğinden ve hesabınız üzerinden yapılan işlemlerden sorumlu olmakla,</li>'
            .'<li>Platform\'u yalnızca bakım kurumu arama/karşılaştırma ve teklif alma amacıyla, hukuka ve ahlaka uygun şekilde kullanmakla,</li>'
            .'<li>Kurumlara yönelik gerçek dışı, yanıltıcı veya kötü niyetli değerlendirme/yorum paylaşmamakla</li>'
            .'</ul>'
            .'<p>yükümlüsünüz. Aile/ziyaretçi kullanıcılar için Platform üzerinden teklif almak ve kurumları karşılaştırmak ücretsizdir.</p>'
            .'<h2>5. Kurum Kullanıcı Yükümlülükleri</h2>'
            .'<ul>'
            .'<li>Sahiplenme başvurusunda gerçek kimlik/yetki belgelerini sunmakla,</li>'
            .'<li>Profilinizde yer alan bilgilerin (adres, telefon, hizmetler, fiyat aralığı, kapasite) doğru ve güncel olmasını sağlamakla,</li>'
            .'<li>Aile kullanıcılarına yönelik gönderdiğiniz teklif ve mesajlarda dürüst ve profesyonel davranmakla</li>'
            .'</ul>'
            .'<p>yükümlüsünüz. Sahiplenme başvurusu, admin incelemesi ve onayına tabidir; Platform, yetersiz/şüpheli belge sunan başvuruları reddetme hakkını saklı tutar.</p>'
            .'<h2>6. Bakiye/Kredi Sistemi</h2>'
            .'<p>Kurum kullanıcıları, aile taleplerine teklif verebilmek için Platform üzerinden kredi/bakiye kullanır. Bakiye yükleme, kurum panelinden banka havalesi/EFT ile yapılır; kurum, dekont görselini yükler ve yükleme admin onayından sonra bakiyeye yansır. Platform üzerinden kredi kartıyla veya başka bir otomatik ödeme yöntemiyle tahsilat yapılmaz. Yüklenen bakiyenin iadesi, yalnızca yanlışlıkla yapılan işlemler için ve admin ile iletişime geçilerek değerlendirilir.</p>'
            .'<h2>7. Teklif ve Mesajlaşma Sistemi</h2>'
            .'<p>Aile kullanıcıları teklif talebi oluşturduğunda, ilgili bölgedeki uygun kurumlara bildirim gider; kurumlar bakiyelerinden düşülerek teklif verebilir. Taraflar, teklif kabul edildikten sonra Platform üzerinden mesajlaşabilir. Platform, mesajlaşma içeriğini denetlemez ancak kötüye kullanım bildirimlerini inceleme hakkını saklı tutar.</p>'
            .'<h2>8. Değerlendirme (Yorum) Kuralları</h2>'
            .'<p>Kurum profillerine bırakılan değerlendirmelerin gerçek bir deneyime dayanması gerekir. Hakaret, iftira, gerçek dışı bilgi veya rakip kurumları kötülemek amacıyla yazılan yorumlar Platform tarafından kaldırılabilir; tekrar eden kötüye kullanımlarda hesap askıya alınabilir.</p>'
            .'<h2>9. Fikri Mülkiyet</h2>'
            .'<p>Platform\'un tasarımı, yazılımı, logosu ve derlediği içerikler Bakım Platformu A.Ş.\'ye aittir. Kurum kullanıcıları tarafından yüklenen görseller/açıklamalar için, bu içerikleri Platform\'da yayınlama konusunda Platform\'a münhasır olmayan bir kullanım izni vermiş sayılırsınız.</p>'
            .'<h2>10. Sorumluluğun Sınırlandırılması</h2>'
            .'<p>Platform, listelenen kurumların sunduğu bakım hizmetinin kalitesini garanti etmez; kurum seçimi ve hizmet ilişkisinden doğacak anlaşmazlıklarda taraf değildir. Platform, kesintisiz veya hatasız hizmet garantisi vermez; teknik arızalar nedeniyle oluşabilecek dolaylı zararlardan sorumlu tutulamaz. Aile kullanıcılarının bir kurumla ilişkiye geçmeden önce kurumu bizzat ziyaret etmesi, gerekli belgeleri (ruhsat vb.) kontrol etmesi önerilir.</p>'
            .'<h2>11. Hesabın Askıya Alınması/Feshi</h2>'
            .'<p>Bu şartların ihlali, kötüye kullanım şikayeti veya sahte/yanıltıcı bilgi tespiti hâlinde Platform, ilgili hesabı uyarı vererek veya vermeksizin askıya alabilir ya da kapatabilir. Kullanıcılar hesaplarını dilediği zaman kapatma talebinde bulunabilir.</p>'
            .'<h2>12. Şartlarda Değişiklik</h2>'
            .'<p>Platform, bu Kullanım Şartları\'nı zaman zaman güncelleyebilir. Güncel sürüm her zaman bu sayfada yayınlanır; önemli değişikliklerde kullanıcılar bilgilendirilmeye çalışılır.</p>'
            .'<h2>13. Uyuşmazlıkların Çözümü</h2>'
            .'<p>Bu şartlardan doğan uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanır; İstanbul (Merkez) Mahkemeleri ve İcra Daireleri yetkilidir.</p>';

        $cerezBody = '<h2>1. Çerez Nedir</h2>'
            .'<p>Çerezler, bir web sitesini ziyaret ettiğinizde tarayıcınıza kaydedilen küçük metin dosyalarıdır. '.$domain.' ("Platform"), aşağıda açıklanan sınırlı ve zorunlu amaçlar dışında çerez kullanmaz; reklam, pazarlama veya üçüncü taraf analitik/izleme çerezi kullanılmamaktadır.</p>'
            .'<h2>2. Kullandığımız Çerezler</h2>'
            .'<ul>'
            .'<li><strong>Oturum çerezi (bakim_platform_session):</strong> Giriş yaptığınızda oturumunuzun açık kalmasını sağlar; tarayıcı kapatıldığında veya bir süre işlem yapılmadığında sona erer. Bu çerez olmadan giriş yapmış şekilde gezinemezsiniz.</li>'
            .'<li><strong>Güvenlik (CSRF/XSRF) çerezi:</strong> Formlarınızın ve işlemlerinizin sahte isteklere (CSRF saldırılarına) karşı korunmasını sağlar.</li>'
            .'</ul>'
            .'<p>Bu iki çerez de Platform\'un temel işlevlerinin (giriş yapma, form gönderme) çalışabilmesi için <strong>zorunludur</strong>; herhangi bir kişisel profil oluşturma, reklam hedefleme veya üçüncü bir siteye veri aktarma amacı taşımazlar.</p>'
            .'<h2>3. Yerel Depolama (localStorage)</h2>'
            .'<p>Admin panelinde tercih ettiğiniz görünüm (açık/koyu tema) yalnızca tarayıcınızın yerel deposunda (localStorage) tutulur; bu bilgi sunucuya gönderilmez ve bir çerez değildir.</p>'
            .'<h2>4. Çerezleri Nasıl Yönetebilirsiniz</h2>'
            .'<p>Tarayıcınızın ayarlarından çerezleri görüntüleyebilir, silebilir veya engelleyebilirsiniz. Ancak zorunlu oturum ve güvenlik çerezlerini engellemeniz hâlinde Platform\'a giriş yapamaz, form gönderemezsiniz; bu çerezler için ayrı bir rıza mekanizması sunulmamaktadır çünkü hizmetin sunulması için teknik olarak zorunludurlar.</p>'
            .'<h2>5. Değişiklikler</h2>'
            .'<p>Platform ileride analitik veya başka amaçlı çerezler kullanmaya başlarsa, bu politika güncellenerek kullanıcılar bilgilendirilecek ve gerekli onay mekanizmaları eklenecektir.</p>';

        return [
            'kvkk' => ['title' => 'KVKK Aydınlatma Metni ve Açık Rıza Metni', 'body' => $kvkkBody],
            'gizlilik-politikasi' => ['title' => 'Gizlilik Politikası', 'body' => $gizlilikBody],
            'kullanim-sartlari' => ['title' => 'Kullanım Şartları', 'body' => $sartlarBody],
            'cerez-politikasi' => ['title' => 'Çerez Politikası', 'body' => $cerezBody],
        ];
    }
}