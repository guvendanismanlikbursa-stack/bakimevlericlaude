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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@bakimplatform.test'],
            ['name' => 'Süper Admin', 'password' => Hash::make('Admin12345!'), 'role' => 'superadmin']
        );

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

        if (Facility::count() === 0) {
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

        foreach (['bakimevibul', 'bakimeviara', 'bakimevleri'] as $brand) {
            ContentPage::updateOrCreate(
                ['brand' => $brand, 'slug' => 'hakkimizda'],
                ['title' => 'Hakkımızda', 'body' => '<p>Bu marka, aile/kullanıcıları ihtiyaca uygun bakım kurumlarıyla buluşturan eşleştirme platformudur. (örnek içerik, admin panelden düzenlenebilir)</p>']
            );
            ContentPage::updateOrCreate(
                ['brand' => $brand, 'slug' => 'kvkk'],
                ['title' => 'KVKK Aydınlatma Metni ve Açık Rıza Metni', 'body' =>
                    '<p>Kişisel verileriniz 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında, aşağıda belirtilen amaçlarla sınırlı olarak işlenmektedir. (örnek/demo içerik, admin panelden düzenlenebilir; canlıya almadan önce hukuki danışmanınıza onaylatmanız önerilir.)</p>'
                    .'<p><strong>İşlenen veriler:</strong> ad-soyad, e-posta, telefon; hesap oluşturma sırasında tarafınızca izin verilmesi hâlinde yaklaşık konum bilginiz (il düzeyinde).</p>'
                    .'<p><strong>Konum verisi:</strong> Hesap oluşturma ekranındaki onay kutusunu işaretlemeniz üzerine, tarayıcınız izin verirse cihazınızın konumu alınır; bu bilgi size en yakın hizmet sağlayan kurumları önermek ve platform istatistikleri için kullanılır. Konum paylaşımını reddetmeniz hesap oluşturmanızı engellemez.</p>'
                    .'<p><strong>Kurum sahiplenme başvurusunda konum:</strong> Bir kurumu sahiplenmek için başvuru yaptığınızda, tarayıcınız izin verirse konumunuz alınır ve başvurunuzun ilgili kurumun adresine yaklaşık uzaklığı hesaplanarak admin incelemesinde bir kontrol bilgisi olarak kullanılır. İzin vermemeniz başvurunuzu engellemez.</p>'
                    .'<p><strong>WhatsApp butonu:</strong> Sitedeki WhatsApp butonuna tıkladığınızda, tarayıcınız izin verirse yaklaşık konumunuz (il düzeyinde) ve tıklama zamanı platform yöneticisine iletişim talebi kaydı olarak iletilir. WhatsApp sohbetinin kendisi cihazınızdan doğrudan açılır, bu bilgiler WhatsApp\'a değil platforma kaydedilir.</p>'
                    .'<p><strong>Amaç:</strong> hesabınızı oluşturmak, aile-kurum eşleştirmesi yapmak, teklif/mesajlaşma akışını sağlamak, sahiplenme başvurularının gerçekliğini kontrol etmek ve hizmet kalitesini ölçmek.</p>'
                    .'<p>Bu onay kutusunu işaretleyerek veya ilgili formu/butonu kullanarak yukarıdaki işleme faaliyetlerine açık rızanızı vermiş olursunuz. Rızanızı dilediğiniz zaman hesabınızdan veya bizimle iletişime geçerek geri çekebilirsiniz.</p>'
                ]
            );

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
}