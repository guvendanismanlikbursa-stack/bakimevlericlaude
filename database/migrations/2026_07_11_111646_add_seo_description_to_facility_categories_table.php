<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kategori sayfalarina (il/ilce+kategori rehber/fiyat sayfalari) gercek,
// tanimlayici icerik eklemek icin - programatik SEO sayfalarinin duplicate/
// thin-content riskini azaltan uculcu farklilastirma katmani (bkz.
// guide_page_content() app/helpers.php). Sadece 7 kategori oldugu icin
// (elle yazilabilir kapsam - binlerce sayfa degil) icerik bu migration
// icinde bir kerelik dolduruluyor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_categories', function (Blueprint $table) {
            $table->text('seo_description')->nullable()->after('brand_scope');
        });

        $descriptions = [
            'yasli-bakim-evi' => 'Yaşlı bakım evleri, günlük yaşam aktivitelerinde desteğe ihtiyaç duyan yaşlı bireylere konaklama, beslenme, hijyen ve sosyal aktivite hizmeti sunan kurumlardır. Hemşire/bakıcı desteği, oda düzeni ve ziyaret saatleri kurumdan kuruma farklılık gösterir; seçim öncesi kapasite ve hizmet kapsamının kontrol edilmesi önerilir.',
            'huzurevi' => 'Huzurevleri, bağımsız yaşam becerisi görece daha yüksek yaşlı bireyler için sosyal yaşam alanları, düzenli sağlık kontrolü ve topluluk içinde vakit geçirme imkanı sunan konaklama kurumlarıdır. Genellikle ortak alanlar, etkinlik programları ve düzenli doktor kontrolü öne çıkan hizmetlerdendir.',
            'cocuk-bakim-merkezi' => 'Çocuk bakım merkezleri, ebeveynlerin çalışma saatlerinde veya ihtiyaç duydukları zaman dilimlerinde çocuklarına güvenli, gözetimli ve gelişimlerini destekleyici bir ortam sunan kurumlardır. Yaş grubuna uygun aktivite programı, güvenlik önlemleri ve personel oranı kurum seçiminde dikkat edilmesi gereken kriterlerdir.',
            'kres-ve-anaokulu' => 'Kreş ve anaokulları, okul öncesi yaş grubundaki çocuklara erken çocukluk eğitimi, sosyalleşme ve temel beceri gelişimi sağlayan kurumlardır. Müfredat yaklaşımı, sınıf mevcudu, beslenme programı ve açık/kapalı oyun alanları kurumlar arasında farklılık gösterebilir.',
            'ozel-egitim-ve-gelisim-merkezi' => 'Özel eğitim ve gelişim merkezleri, gelişimsel farklılığı veya öğrenme güçlüğü olan çocuk ve bireylere bireyselleştirilmiş eğitim programı (BEP), terapi ve gelişim takibi sunan kurumlardır. Uzman kadro (özel eğitim öğretmeni, psikolog, dil ve konuşma terapisti vb.) ve bireysel/grup seans oranı kurum seçiminde önemli kriterlerdir.',
            'fizik-tedavi-ve-rehabilitasyon' => 'Fizik tedavi ve rehabilitasyon merkezleri, ameliyat sonrası, ortopedik veya kronik ağrı gibi durumlarda hareket kabiliyetini geri kazandırmaya yönelik fizyoterapi hizmeti sunan kurumlardır. Cihaz/ekipman çeşitliliği, fizyoterapist uzmanlık alanı ve seans planı kurumdan kuruma değişir.',
            'norolojik-rehabilitasyon-merkezi' => 'Nörolojik rehabilitasyon merkezleri; inme, travmatik beyin hasarı, MS gibi nörolojik rahatsızlıklar sonrası fonksiyon kaybını azaltmaya yönelik özel rehabilitasyon programları sunan kurumlardır. Multidisipliner ekip yapısı (nörolog, fizyoterapist, ergoterapist) ve vaka bazlı program süresi kurum değerlendirmesinde önemlidir.',
        ];

        foreach ($descriptions as $slug => $description) {
            DB::table('facility_categories')->where('slug', $slug)->update(['seo_description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('facility_categories', function (Blueprint $table) {
            $table->dropColumn('seo_description');
        });
    }
};
