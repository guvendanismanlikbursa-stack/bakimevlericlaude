<?php

namespace App\Support;

/**
 * 25 Ekim 2026: kullanıcının talebi - doktor Profesyonel sitesi "çok sade, içerik yok".
 * Doktorun ek bilgi girmesine gerek kalmadan, branşa göre otomatik dolan bilgilendirme
 * içerikleri: başvuru durumları, muayeneye hazırlık listesi, genel SSS.
 * Hukuki not: içerikler genel sağlık bilgilendirmesidir; üstünlük/başarı iddiası,
 * hasta yorumu, "en iyi/garantili" gibi ifadeler BİLEREK yoktur (hekim reklam yasağı).
 */
class DoktorIcerik
{
    /** Doktorun branş adından içerik türünü bulur (sıra önemli: "çocuk nörolojisi" pediatri'den önce). */
    public static function tur(?string $brans, ?string $etiket = null): string
    {
        $m = mb_strtolower(trim(($brans ?? '').' '.($etiket ?? '')), 'UTF-8');
        $varMi = fn (array $kelimeler) => collect($kelimeler)->contains(fn ($k) => str_contains($m, $k));

        return match (true) {
            $varMi(['çocuk nöroloji', 'çocuk beyin']) => 'cocuk-noroloji',
            $varMi(['psikiyatri', 'çocuk gelişim', 'ergen']) => 'cocuk-psikiyatri',
            $varMi(['diş', 'ortodonti', 'dt.']) => 'dis',
            $varMi(['geriatri', 'yaşlılık']) => 'geriatri',
            $varMi(['fizik', 'rehabilitasyon']) => 'ftr',
            $varMi(['nöroloji', 'beyin ve sinir']) => 'noroloji',
            $varMi(['ortopedi', 'kemik', 'eklem']) => 'ortopedi',
            $varMi(['pediatri', 'çocuk']) => 'pediatri',
            default => 'genel',
        };
    }

    /** "Hangi durumlarda başvurabilirsiniz?" kartları: [başlık, açıklama]. */
    public static function durumlar(string $tur): array
    {
        return match ($tur) {
            'pediatri' => [
                ['Ateş ve enfeksiyonlar', 'Yüksek ateş, öksürük, boğaz ve kulak ağrısı gibi çocukluk döneminde sık görülen şikâyetler.'],
                ['Aşı takibi', 'Sağlık Bakanlığı aşı takvimine göre aşıların zamanında planlanması ve izlenmesi.'],
                ['Büyüme ve gelişme', 'Boy, kilo ve gelişim basamaklarının yaşa göre düzenli olarak değerlendirilmesi.'],
                ['Beslenme', 'Anne sütü, ek gıdaya geçiş ve çocukluk dönemi beslenmesiyle ilgili sorular.'],
                ['Yenidoğan kontrolleri', 'Doğumdan sonraki ilk haftalarda yapılan rutin muayeneler.'],
                ['Okul öncesi değerlendirme', 'Kreşe ya da okula başlamadan önce genel sağlık kontrolü.'],
            ],
            'dis' => [
                ['Diş ağrısı ve hassasiyet', 'Sıcak-soğuk hassasiyeti, çiğnerken ağrı ya da zonklama şikâyetleri.'],
                ['Düzenli kontrol', 'Yılda en az bir kez önerilen ağız ve diş sağlığı kontrolü ve temizliği.'],
                ['Diş eti şikâyetleri', 'Diş eti kanaması, şişlik ya da çekilmesi.'],
                ['Çocuklarda diş sağlığı', 'Süt dişlerinin takibi ve çocuklarda koruyucu uygulamalar.'],
                ['Diş eksiklikleri', 'Eksik dişler nedeniyle çiğneme ve konuşmada yaşanan güçlükler.'],
                ['Çene ve çiğneme sorunları', 'Çene ekleminde ağrı, ses gelmesi ya da ağız açmada kısıtlılık.'],
            ],
            'geriatri' => [
                ['Birden fazla kronik hastalık', 'Tansiyon, şeker, kalp gibi hastalıkların bir arada değerlendirilmesi.'],
                ['İlaç düzenlemesi', 'Çok sayıda ilaç kullanan yaşlılarda ilaçların birlikte gözden geçirilmesi.'],
                ['Unutkanlık', 'Bellek ve dikkatle ilgili değişikliklerin değerlendirilmesi.'],
                ['Düşme ve denge', 'Sık düşme, denge kaybı ve yürüme güçlüğü şikâyetleri.'],
                ['Beslenme ve kilo kaybı', 'İştahsızlık, istemsiz kilo kaybı ve beslenme sorunları.'],
                ['Uyku sorunları', 'Uykuya dalma güçlüğü, sık uyanma ya da gündüz uyuklama.'],
            ],
            'ftr' => [
                ['Bel ve boyun ağrıları', 'Uzun süren ya da tekrarlayan bel, sırt ve boyun ağrıları.'],
                ['Eklem ağrıları', 'Diz, kalça ve omuzda ağrı, kireçlenmeye bağlı hareket kısıtlılığı.'],
                ['Spor yaralanmaları', 'Burkulma, zorlanma ve spor sonrası ağrılar.'],
                ['İnme sonrası rehabilitasyon', 'İnme sonrasında hareket ve günlük yaşam becerilerinin desteklenmesi.'],
                ['Ameliyat sonrası iyileşme', 'Ortopedik ameliyatlardan sonra hareket açıklığının ve gücün geri kazanılması.'],
                ['Duruş ve hareket sorunları', 'Yanlış duruşa bağlı ağrılar ve hareket kısıtlılıkları.'],
            ],
            'cocuk-noroloji' => [
                ['Havale ve nöbetler', 'Ateşli ya da ateşsiz nöbetlerin değerlendirilmesi ve takibi.'],
                ['Baş ağrıları', 'Çocuklarda sık tekrarlayan ya da şiddetli baş ağrıları.'],
                ['Gelişim geriliği', 'Yürüme, konuşma gibi gelişim basamaklarında gecikme.'],
                ['Dikkat ve öğrenme', 'Dikkat, öğrenme ve okul başarısıyla ilgili nörolojik değerlendirme.'],
                ['Kas güçsüzlüğü', 'Kas gücünde azalma, sık düşme ya da çabuk yorulma.'],
                ['Tik ve istemsiz hareketler', 'Tekrarlayan istemsiz hareket ya da seslerin değerlendirilmesi.'],
            ],
            'cocuk-psikiyatri' => [
                ['Dikkat ve hareketlilik', 'Dikkat sürdürmede güçlük ve aşırı hareketlilik şikâyetleri.'],
                ['Kaygı ve korkular', 'Ayrılık kaygısı, yoğun korkular ve endişeler.'],
                ['Uyku ve yeme sorunları', 'Uyku düzeninde ve yeme alışkanlıklarında belirgin değişiklikler.'],
                ['Okul uyumu', 'Okula başlama, okul reddi ve arkadaş ilişkileriyle ilgili güçlükler.'],
                ['Davranış sorunları', 'Öfke nöbetleri, kurallara uymada güçlük gibi davranış değişiklikleri.'],
                ['Gelişimsel değerlendirme', 'Dil, sosyal ve duygusal gelişimin yaşa göre değerlendirilmesi.'],
            ],
            'noroloji' => [
                ['Baş ağrısı ve migren', 'Sık tekrarlayan ya da günlük yaşamı etkileyen baş ağrıları.'],
                ['Baş dönmesi', 'Baş dönmesi, sersemlik ve denge kaybı şikâyetleri.'],
                ['Uyuşma ve karıncalanma', 'El, kol ya da bacaklarda uyuşma, karıncalanma ve güçsüzlük.'],
                ['Unutkanlık', 'Bellek ve dikkatle ilgili değişikliklerin değerlendirilmesi.'],
                ['Titreme', 'Ellerde titreme ve hareketlerde yavaşlama gibi şikâyetler.'],
                ['Uyku bozuklukları', 'Uykusuzluk, uykuda hareket ya da gündüz aşırı uyku hali.'],
            ],
            'ortopedi' => [
                ['Eklem ağrıları', 'Diz, kalça, omuz ve el bileğinde ağrı ve hareket kısıtlılığı.'],
                ['Kırık ve çıkıklar', 'Düşme ya da kaza sonrası kemik ve eklem yaralanmaları.'],
                ['Spor sakatlıkları', 'Bağ, menisküs ve kas yaralanmaları.'],
                ['Bel ve boyun', 'Bel ve boyun ağrıları, fıtık şüphesi.'],
                ['Omuz ve diz sorunları', 'Kireçlenme, yırtık ve tekrarlayan ağrılar.'],
                ['Çocuklarda kemik gelişimi', 'Çocuklarda yürüme, duruş ve kemik gelişimiyle ilgili sorular.'],
            ],
            default => [
                ['Genel muayene', 'Şikâyetlerinizin dinlenmesi ve genel sağlık değerlendirmesi.'],
                ['Kontrol muayenesi', 'Devam eden tedavi ve takiplerin değerlendirilmesi.'],
                ['Tahlil değerlendirmesi', 'Daha önce yapılmış tahlil ve görüntüleme sonuçlarının yorumlanması.'],
                ['İlaç düzenlemesi', 'Kullandığınız ilaçların birlikte gözden geçirilmesi.'],
                ['Koruyucu sağlık', 'Hastalık oluşmadan önce yapılabilecek kontroller hakkında bilgilendirme.'],
                ['Yönlendirme', 'Gerektiğinde ilgili branşa ya da merkeze yönlendirme.'],
            ],
        };
    }

    /** "Muayeneye gelirken yanınızda bulunsun" listesi. */
    public static function hazirlik(string $tur): array
    {
        $ek = match ($tur) {
            'pediatri' => ['Aşı kartı', 'Ateş ve şikâyetlerin ne zaman başladığına dair notlar'],
            'dis' => ['Varsa önceki diş röntgenleri', 'Kan sulandırıcı ilaç kullanıyorsanız bilgisi'],
            'geriatri' => ['Kullanılan tüm ilaçlar (mümkünse kutularıyla)', 'Hastaneden çıkış raporları (epikriz)', 'Bir yakınının eşlik etmesi'],
            'ftr', 'ortopedi' => ['Röntgen, MR gibi görüntüleme sonuçları', 'Varsa ameliyat raporları', 'Rahat bir kıyafet'],
            'cocuk-noroloji' => ['EEG ve MR raporları', 'Varsa nöbet anına ait video kaydı', 'Gelişimle ilgili notlarınız'],
            'cocuk-psikiyatri' => ['Öğretmen ya da okul gözlemleri', 'Önceki değerlendirme raporları', 'Gözlemlerinize dair notlar'],
            'noroloji' => ['MR, BT, EMG ya da EEG sonuçları', 'Şikâyetlerinizin seyrine dair notlar'],
            default => ['Önceki tahlil ve görüntüleme sonuçları'],
        };

        return array_values(array_unique(array_merge(['Kimlik kartınız'], $ek, ['Düzenli kullandığınız ilaçların listesi', 'Daha önceki tahlil ve raporlarınız'])));
    }

    /** Muayene süreci adımları: [başlık, açıklama]. */
    public static function surec(bool $whatsapp): array
    {
        return [
            ['Randevu', ($whatsapp ? 'Telefonla ya da WhatsApp’tan yazarak' : 'Telefonla arayarak').' size uygun gün ve saati birlikte belirleyelim.'],
            ['Ön görüşme', 'Şikâyetlerinizi, geçmiş sağlık durumunuzu ve kullandığınız ilaçları konuşuruz.'],
            ['Muayene', 'Muayene sonrasında gerekirse tahlil ya da görüntüleme planlanır.'],
            ['Takip', 'Gerekli durumlarda kontrol randevusu verilir, süreç birlikte izlenir.'],
        ];
    }

    /** Doktor kendi sorularını girmediyse gösterilen genel SSS. */
    public static function varsayilanSss(bool $whatsapp): array
    {
        return [
            ['soru' => 'Randevu nasıl alabilirim?', 'cevap' => ($whatsapp ? 'Telefonla arayarak ya da WhatsApp’tan yazarak' : 'Telefonla arayarak').' randevu alabilirsiniz; size uygun gün ve saat birlikte belirlenir.'],
            ['soru' => 'Muayeneye gelirken neler getirmeliyim?', 'cevap' => 'Kimliğiniz, varsa daha önceki tahlil ve raporlarınız ile düzenli kullandığınız ilaçların listesi yeterlidir.'],
            ['soru' => 'Kontrol muayenesi gerekiyor mu?', 'cevap' => 'Gerekli görülen durumlarda muayene sonunda kontrol randevusu planlanır.'],
            ['soru' => 'Acil bir durumda ne yapmalıyım?', 'cevap' => 'Acil durumlarda vakit kaybetmeden 112’yi arayın ya da en yakın acil servise başvurun.'],
        ];
    }
}
