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
     * @return array{summary: string, detail: string}
     */
    public function plainExplanation(): array
    {
        $exceptionClass = $this->context['exception_class'] ?? null;
        $msg = (string) $this->message;

        if ($exceptionClass === 'Illuminate\\Database\\UniqueConstraintViolationException' && str_contains($msg, 'CheckUserFlows')) {
            return [
                'summary' => 'Günlük otomatik test kontrolü sırasında geçici bir çakışma yaşandı.',
                'detail' => '3 site aynı veritabanını paylaştığı ve aynı saatte otomatik kontrol çalıştırdığı için, iki site aynı test kaydını aynı anda oluşturmaya çalışmış. Gerçek ziyaretçi/kullanıcı verisiyle ilgisi yok, sitenin çalışmasını etkilemez. Bu tür çakışmalar bir daha olmaması için düzeltildi.',
            ];
        }

        if (str_contains($msg, 'Scheduled command') && str_contains($msg, 'failed with exit code')) {
            return [
                'summary' => 'Otomatik bir arka plan görevi (günlük kontrol) başarısız oldu.',
                'detail' => 'Bu kayıt, planlanmış bir görevin hata verdiğini bildiriyor — asıl sebep genelde aynı saatte oluşan başka bir hata kaydında ("Hatalar" listesinde yakınlarda) görünür.',
            ];
        }

        if ($exceptionClass && str_contains($exceptionClass, 'UniqueConstraintViolationException')) {
            return [
                'summary' => 'Sistem aynı bilgiyi veritabanına iki kez kaydetmeye çalıştı.',
                'detail' => 'Zaten var olan bir kayıt (ör. aynı e-posta veya kod) tekrar eklenmeye çalışıldı ve sistem bunu engelledi. Genelde veri kaybına yol açmaz.',
            ];
        }

        if ($exceptionClass && (str_contains($exceptionClass, 'QueryException') || str_contains($exceptionClass, 'Database'))) {
            return [
                'summary' => 'Veritabanı işlemi sırasında bir sorun oluştu.',
                'detail' => 'Bu hatanın tam sebebini çözmek teknik inceleme gerektiriyor — aşağıdaki "Teknik detay" kısmını bana gösterirseniz inceleyip açıklarım.',
            ];
        }

        return [
            'summary' => 'Sistemde teknik bir hata oluştu.',
            'detail' => 'Bu hatanın ne anlama geldiğini otomatik olarak çözemedim — aşağıdaki "Teknik detay" kısmını bana gösterirseniz inceleyip açık Türkçe anlatabilirim.',
        ];
    }
}
