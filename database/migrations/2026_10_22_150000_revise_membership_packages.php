<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 22 Ekim 2026: kullanıcının talebi - İyilik Hareketi üyelikleri 3 yıllık
// pakete indirildi:
//   Ekonomik Üyelik  -> "Farkındalık Destekçisi" rozeti (12.000 TL)
//   Standart Üyelik  -> "Kurumsal İyilik Ortağı" rozeti (34.000 TL)
//   Premium Üyelik   -> "Altın Kalpli Kurum" rozeti (49.000 TL)
// Eski Aylık / 6 Aylık / Yıllık paketler bu üç pakete dönüştürülür (id'ler
// korunur, böylece bu paketlere bağlı kurumların bağı kopmaz); Bursa'ya özel
// "Vizyoner İyilik Elçisi" pasife alınır (silinmez).
// Yeni alanlar admin panelinden düzenlenir:
//   audience          "Kimler için?" metni
//   closing_text      paketin kapanış cümlesi
//   photo_limit       üyenin yükleyebileceği fotoğraf sayısı (boş = sınırsız)
//   can_upload_video  panelden tanıtım videosu yükleme hakkı
// features alanında "## Başlık" satırı yeni bir bölüm başlatır, diğer
// satırlar "Madde adı: açıklama" biçimindedir (bkz. featureGroups()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->text('audience')->nullable()->after('tagline');
            $table->text('closing_text')->nullable()->after('features');
            $table->unsignedSmallInteger('photo_limit')->nullable()->after('closing_text');
            $table->boolean('can_upload_video')->default(false)->after('photo_limit');
        });

        $packages = [
            1 => [
                'old_name' => 'Aylık Üyelik',
                'name' => 'Ekonomik Üyelik', 'price' => 12000, 'badge_label' => 'Farkındalık Destekçisi', 'badge_emoji' => '💜',
                'tagline' => 'Görünür olun, aileler sizi bulsun.',
                'audience' => 'Dijitalde ilk adımını atmak isteyen, aileler tarafından bulunmak ve doğrudan talep almak isteyen her kurum.',
                'closing_text' => 'Ekonomik üyelik, uygun bir başlangıçla kurumunuzu üç sitede ailelerin karşısına güvenilir, doğrulanmış bir kurum olarak çıkarır ve bölgenizdeki talepleri size ulaştırır.',
                'photo_limit' => 10, 'can_upload_video' => false, 'is_premium' => false,
            ],
            2 => [
                'old_name' => '6 Aylık Üyelik',
                'name' => 'Standart Üyelik', 'price' => 34000, 'badge_label' => 'Kurumsal İyilik Ortağı', 'badge_emoji' => '💚',
                'tagline' => 'Dijitalde büyüyün: kendi internet siteniz hazır.',
                'audience' => 'Kendi internet sitesi olmayan ya da sitesini yenilemek isteyen, bölgesinde daha çok aileye ulaşmak isteyen kurumlar.',
                'closing_text' => 'Standart üyelik ile kurumunuz hem üç sitede öne çıkan bir kurum olarak hem de kendi alan adıyla internette güçlü bir şekilde yer alır; bölgenizdeki talepler size öncelikli ulaşır.',
                'photo_limit' => null, 'can_upload_video' => true, 'is_premium' => false,
            ],
            3 => [
                'old_name' => 'Yıllık Üyelik',
                'name' => 'Premium Üyelik', 'price' => 49000, 'badge_label' => 'Altın Kalpli Kurum', 'badge_emoji' => '💛',
                'tagline' => 'Bölgenin en güçlü, en çok tercih edilen kurumu olun.',
                'audience' => 'Bölgesinde lider olmak isteyen, ailelere kurumunu ziyaret etmeden en etkileyici şekilde göstermek isteyen kurumlar.',
                'closing_text' => 'Premium üyelik; vitrin yeriniz, kendi internet siteniz, 360° sanal turunuz ve kişisel temsilcinizle kurumunuzu bölgenin en görünür ve en güvenilir adresi yapan eksiksiz pakettir.',
                'photo_limit' => null, 'can_upload_video' => true, 'is_premium' => true,
            ],
        ];

        $now = now();
        $existing = DB::table('subscription_packages')->where('kind', 'membership')
            ->where('name', '!=', 'Yıllık Premium & Prodüksiyon')->orderBy('sort_order')->orderBy('id')->get();

        foreach ($packages as $level => $p) {
            $row = [
                'name' => $p['name'], 'price' => $p['price'], 'list_price' => null, 'months' => 12,
                'badge_label' => $p['badge_label'], 'badge_emoji' => $p['badge_emoji'], 'free_months_label' => null,
                'tagline' => $p['tagline'], 'audience' => $p['audience'], 'closing_text' => $p['closing_text'],
                'features' => $this->features($level), 'photo_limit' => $p['photo_limit'],
                'can_upload_video' => $p['can_upload_video'], 'is_premium' => $p['is_premium'],
                'only_city_slug' => null, 'is_active' => true, 'sort_order' => $level, 'updated_at' => $now,
            ];
            $target = $existing->firstWhere('name', $p['old_name']) ?? $existing->firstWhere('name', $p['name']) ?? $existing->get($level - 1);
            if ($target) {
                DB::table('subscription_packages')->where('id', $target->id)->update($row);
            } else {
                DB::table('subscription_packages')->insert($row + [
                    'kind' => 'membership', 'description' => null, 'bonus_quote_credits' => 0,
                    'duration_days' => null, 'created_at' => $now,
                ]);
            }
        }

        DB::table('subscription_packages')->where('kind', 'membership')->where('name', 'Yıllık Premium & Prodüksiyon')
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn(['audience', 'closing_text', 'photo_limit', 'can_upload_video']);
        });
    }

    /**
     * Paket ayrıntıları - her paket kendi içinde eksiksiz, görüşmede tek başına
     * okunabilsin diye ortak maddeler her pakette tekrar yazılır.
     */
    private function features(int $level): string
    {
        $lines = ['## Profil ve görünürlük'];
        $lines[] = "3 sitede aynı anda yayın: Kurumunuz bakimevleri.com, bakimevibul.com ve bakimeviara.com'da aynı anda yayınlanır. Yaşlı bakım, çocuk ve rehabilitasyon arayan aileler sizi hangi siteden ararsa arasın bulur.";
        $lines[] = 'Tek kurum paneli: Üç sitedeki profilinizi tek bir kullanıcı adı ve şifreyle, tek panelden yönetirsiniz. Panel telefonda da bilgisayarda da rahatça kullanılır.';
        $lines[] = 'Doğrulanmış Üye Kurum rozeti: Profilinizde ve arama sonuçlarındaki kartınızda, kurumunuzun bakimevleri.com tarafından doğrulandığını gösteren rozet yer alır. Aileler önce rozetli kurumlara güvenir.';
        if ($level === 1) {
            $lines[] = '10 fotoğraf: Binanızı, odalarınızı, ortak alanlarınızı, bahçenizi ve etkinliklerinizi 10 fotoğrafla tanıtırsınız.';
        } else {
            $lines[] = 'Sınırsız fotoğraf: Fotoğraf sınırı yoktur; kurumunuzun her köşesini, etkinliklerinizi ve özel günlerinizi istediğiniz kadar fotoğrafla gösterirsiniz.';
            $lines[] = 'Tanıtım videosu: Kurumunuzu anlatan tanıtım videonuzu profilinizde yayınlarsınız.';
        }
        $lines[] = 'Eksiksiz kurum profili: Kurum tanıtım yazınız, adresiniz, telefonunuz, çalışma ve ziyaret saatleriniz, ruhsat bilgileriniz ve konumunuz harita üzerinde gösterilir.';
        $lines[] = 'Hizmet, oda ve fiyat bilgisi: Verdiğiniz hizmetleri, oda tiplerinizi ve fiyat aralığınızı kendiniz girer, dilediğiniz zaman güncellersiniz.';
        $lines[] = 'Boş yer ve kontenjan bilgisi: Boş yer ya da kontenjan durumunuzu panelden güncellersiniz; aileler hemen yer olup olmadığını görür.';
        $lines[] = 'Yemek menüsü: Haftalık yemek menünüzü yükleyerek ailelere beslenmeye verdiğiniz özeni gösterirsiniz.';
        $lines[] = "Google yorumlarınız profilinizde: Google Haritalar'daki yorumlarınız profilinizde otomatik olarak gösterilir; ayrıca bir şey yapmanız gerekmez.";
        $lines[] = "Telefon ve WhatsApp düğmeleri: Aileler profilinizden tek dokunuşla sizi arayabilir ya da WhatsApp'tan yazabilir.";
        if ($level === 2) {
            $lines[] = 'Öne çıkan kurum kartı: Arama sonuçlarında ve il/ilçe sayfalarında daha büyük, dikkat çeken bir kartla görünürsünüz.';
        }
        if ($level === 3) {
            $lines[] = 'Vitrin alanı ve üst sıra: İl ve ilçe sayfalarının vitrin alanında yer alırsınız; arama sonuçlarında üst sıralarda, büyük ve dikkat çeken kartla görünürsünüz.';
            $lines[] = 'Altın Kalpli Kurum özel rozeti: Profilinizde ve kartınızda, en üst seviye üyeliği gösteren özel Altın Kalpli Kurum rozeti yer alır.';
        }

        $lines[] = '## Aile talepleri ve iletişim';
        $lines[] = 'Doğrudan aile talepleri: Ailelerin kurumunuza gönderdiği fiyat bilgisi, ziyaret ve kayıt talepleri anında kurum panelinize düşer.';
        $lines[] = 'Sınırsız ücretsiz teklif: Gelen her talebe, sayı sınırı olmadan ve teklif başına ücret ödemeden teklif verirsiniz.';
        $lines[] = [
            1 => 'Bölgenizdeki taleplerin size yönlendirilmesi: İlçenizde belirli bir kurum seçmeden bakım arayan ailelerin ve başka kurumlara gönderilip yanıtsız kalan uygun taleplerin bildirimi size gelir. Teklif vererek bu ailelere de ulaşırsınız.',
            2 => 'Bölgenizdeki taleplerin size yönlendirilmesi (öncelikli): İlçenizde belirli bir kurum seçmeden bakım arayan ailelerin ve başka kurumlara gönderilip yanıtsız kalan uygun talepler size öncelikli olarak, Ekonomik üyelerden önce iletilir.',
            3 => 'Bölgenizdeki taleplerin size yönlendirilmesi (ilk sırada): İlçenizde belirli bir kurum seçmeden bakım arayan ailelerin ve başka kurumlara gönderilip yanıtsız kalan uygun talepler size ilk sırada iletilir. Aileye ulaşan ilk kurum siz olursunuz.',
        ][$level];
        $lines[] = 'Anlık bildirim: Yeni bir talep ya da mesaj geldiğinde hem panelinizde hem telefonunuzda bildirim alırsınız; hiçbir aileyi kaçırmazsınız.';
        $lines[] = 'Aileyle mesajlaşma: Ailelerle panel üzerinden yazışır, sorularını yanıtlar, ziyaret randevusu ayarlarsınız.';
        $lines[] = 'Yorumlara kurum olarak yanıt: Ailelerin bıraktığı yorumlara kurumunuz adına yanıt vererek ilgili ve şeffaf bir kurum olduğunuzu gösterirsiniz.';

        if ($level >= 2) {
            $lines[] = '## Kurumunuza özel internet sitesi';
            $lines[] = 'Kendi alan adınızla internet sitesi: Kurumunuz adına modern, telefonda kusursuz açılan, profesyonel bir internet sitesi hazırlanır (ör. kurumunuz.com).';
            $lines[] = 'Alan adı, barındırma ve güvenlik dahil: Alan adı, sunucu (barındırma) ve güvenlik sertifikası (SSL) pakete dahildir; ayrıca bir firmayla uğraşmazsınız.';
            $lines[] = 'Panelden güncelleme: Sitenizdeki fotoğrafları, hizmetleri, menüyü ve iletişim bilgilerini ayrı bir yere girmeden aynı kurum panelinden güncellersiniz.';
            $lines[] = 'Siteden gelen talepler panelinizde: Sitenizin iletişim formundan gelen talepler doğrudan cep telefonunuza ve kurum panelinize düşer.';
            $lines[] = 'WhatsApp ve arama düğmesi: Sitenizde ziyaretçilerin tek dokunuşla size ulaşacağı WhatsApp ve telefon düğmeleri bulunur.';
            $lines[] = "Google'da bulunabilirlik: Siteniz Google'ın kurallarına uygun hazırlanır; kurumunuzun adıyla arayan aileler sitenize ulaşır.";
            $lines[] = 'Yorumlar ve galeri sitenizde: Aile yorumlarınız ve fotoğraf galeriniz sitenizde de otomatik olarak görünür.';
        }

        if ($level === 3) {
            $lines[] = '## 360° sanal tur';
            $lines[] = 'Profesyonel 360° çekim: Çekim ekibimiz randevuyla kurumunuza gelir; giriş, salon, odalar, yemekhane, banyo ve bahçe dahil tüm alanlarınızı 360° çeker.';
            $lines[] = 'Odadan odaya gezinti: Aileler telefonlarından ya da bilgisayarlarından kurumunuzu parmaklarıyla çevirerek, odadan odaya geçerek gezer; ziyaret etmeden içini görür.';
            $lines[] = 'Her yerde yayın: Sanal turunuz hem platformdaki profilinizde hem de kendi internet sitenizde yayınlanır.';
            $lines[] = 'Güncelleme: Kurumunuzda yenileme yaptığınızda sanal turunuz yılda bir kez ücretsiz olarak yeniden çekilir.';
        }

        $lines[] = '## Raporlama ve destek';
        $lines = array_merge($lines, [
            1 => [
                'Temel istatistikler: Profilinizin kaç kez görüntülendiğini ve kaç talep aldığınızı panelinizden takip edersiniz.',
                'Panel içi destek: Sorularınız için panelden bize ulaşırsınız.',
            ],
            2 => [
                'İstatistikler: Profilinizin ve sitenizin kaç kez görüntülendiğini, kaç talep aldığınızı panelinizden anlık takip edersiniz.',
                'Aylık rapor: Görüntülenmeler, gelen talepler ve verdiğiniz teklifler her ay özet bir raporla size gönderilir.',
                'Öncelikli destek: Sorularınıza ve taleplerinize öncelikli olarak dönülür.',
            ],
            3 => [
                'İstatistikler: Profilinizin, sitenizin ve sanal turunuzun kaç kez görüntülendiğini, kaç talep aldığınızı panelinizden anlık takip edersiniz.',
                'Aylık detaylı rapor: Görüntülenmeler, gelen talepler, sanal tur ziyaretleri ve bölgenizdeki talep yoğunluğunu içeren ayrıntılı rapor her ay size gönderilir.',
                'Kişisel kurum temsilcisi: Kurumunuza özel bir temsilci atanır; profilinizin, sitenizin ve sanal turunuzun güncel kalmasıyla birebir ilgilenir.',
            ],
        ][$level]);

        return implode("\n", $lines);
    }
};
