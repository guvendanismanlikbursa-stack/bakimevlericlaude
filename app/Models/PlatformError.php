<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformError extends Model
{
    protected $fillable = ['source', 'title', 'message', 'context', 'resolved_at'];

    protected $casts = [
        'context' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * 14 Agustos 2026: kullanicinin talebi - "Hatalar" ekrani ham teknik
     * yigin izini (stack trace) gosteriyordu, kod bilmeyen kullanici icin
     * anlamsizdi. Bilinen hata desenlerini sade Turkce'ye cevirir; taniyamadigi
     * bir desen icin durustce "cozemedim, teknik detayi bana gosterin" der -
     * her seyi aciklayabiliyormus gibi yapmaz.
     *
     * 11 Eylul 2026: kullanicinin talebi - "hata ciddi mi gecici mi
     * farkina varamiyorum" - her aciklamaya bir 'severity' (onem
     * derecesi) eklendi ki kullanici tek bakista "bu bana yazmali mi
     * yoksa kendiliginden mi gecer" sorusuna cevap bulabilsin.
     *
     * @return array{summary: string, detail: string, severity: 'dusuk'|'orta'|'yuksek'}
     */
    public function plainExplanation(): array
    {
        $exceptionClass = $this->context['exception_class'] ?? null;
        $msg = (string) $this->message;

        if ($exceptionClass === 'Illuminate\\Database\\UniqueConstraintViolationException' && str_contains($msg, 'CheckUserFlows')) {
            return [
                'summary' => 'Günlük otomatik test kontrolü sırasında geçici bir çakışma yaşandı.',
                'detail' => '3 site aynı veritabanını paylaştığı ve aynı saatte otomatik kontrol çalıştırdığı için, iki site aynı test kaydını aynı anda oluşturmaya çalışmış. Gerçek ziyaretçi/kullanıcı verisiyle ilgisi yok, sitenin çalışmasını etkilemez. Bu tür çakışmalar bir daha olmaması için düzeltildi.',
                'severity' => 'dusuk',
            ];
        }

        // 11 Eylul 2026: kullanicinin bildirdigi tekrarlayan hata -
        // paylasimli sunucunun veritabani baglanti sayisi (300) anlik
        // olarak dolduğunda hem PDOException (dogrudan baglanti kurulamadi)
        // hem QueryException (baglanti varken sorgu calistirilamadi) BU AYNI
        // metni iceriyor, ikisini de tek yerde yakaliyoruz.
        if (str_contains($msg, 'Too many connections')) {
            return [
                'summary' => 'Veritabanı sunucusu o an fazla sayıda bağlantı isteği aldığı için kısa süreliğine yanıt veremedi.',
                'detail' => 'Paylaşımlı hostingin veritabanı sunucusunun aynı anda kaç bağlantı kabul edeceği sabit bir sınırla kısıtlı (hosting firması tarafından belirleniyor, biz artıramıyoruz). Yoğun ziyaret veya arama motoru botlarının kısa sürede çok sayıda sayfa taraması bu sınıra takılabiliyor. Genellikle saniyeler içinde kendiliğinden düzelir, veri kaybına yol açmaz — o an sayfayı açmaya çalışan ziyaretçi hata görür ama sitenin geri kalanı etkilenmez. 11 Eylül 2026\'da en sık ziyaret edilen sayfalarda veritabanına gidişi büyük ölçüde azaltan bir düzeltme yapıldı; bu kayıt hâlâ günde birkaç kez tekrarlanıyorsa bana bildirin, daha kapsamlı bir önlem (ör. bot trafiğini sınırlama) gerekebilir.',
                'severity' => 'orta',
            ];
        }

        if (str_contains($msg, 'Scheduled command') && str_contains($msg, 'failed with exit code')) {
            return [
                'summary' => 'Otomatik bir arka plan görevi (günlük kontrol) başarısız oldu.',
                'detail' => 'Bu kayıt, planlanmış bir görevin hata verdiğini bildiriyor — asıl sebep genelde aynı saatte oluşan başka bir hata kaydında ("Hatalar" listesinde yakınlarda) görünür.',
                'severity' => 'orta',
            ];
        }

        if ($exceptionClass && str_contains($exceptionClass, 'UniqueConstraintViolationException')) {
            return [
                'summary' => 'Sistem aynı bilgiyi veritabanına iki kez kaydetmeye çalıştı.',
                'detail' => 'Zaten var olan bir kayıt (ör. aynı e-posta veya kod) tekrar eklenmeye çalışıldı ve sistem bunu engelledi. Genelde veri kaybına yol açmaz.',
                'severity' => 'dusuk',
            ];
        }

        if ($exceptionClass && (str_contains($exceptionClass, 'QueryException') || str_contains($exceptionClass, 'Database'))) {
            return [
                'summary' => 'Veritabanı işlemi sırasında bir sorun oluştu.',
                'detail' => 'Bu hatanın tam sebebini çözmek teknik inceleme gerektiriyor — aşağıdaki "Teknik detay" kısmını bana gösterirseniz inceleyip açıklarım.',
                'severity' => 'yuksek',
            ];
        }

        return [
            'summary' => 'Sistemde teknik bir hata oluştu.',
            'detail' => 'Bu hatanın ne anlama geldiğini otomatik olarak çözemedim — aşağıdaki "Teknik detay" kısmını bana gösterirseniz inceleyip açık Türkçe anlatabilirim.',
            'severity' => 'yuksek',
        ];
    }
}
