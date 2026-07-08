<?php

namespace App\Console\Commands;

use App\Models\DataImportRow;
use App\Services\DataImportRowApprovalService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Inceleme kuyrugundaki (pending_review) satirlari toplu onaylar: her satir
 * icin DataImportRowApprovalService::approve() cagirir — bu, aciklamayi
 * otomatik uretir (payload'da yoksa), kategoriye gore havuzdan 5 filigranli
 * (ORNEKTIR) gorsel ekler, ve mukerrer kaydi (ayni telefon/isim+ilce)
 * sessizce atlar (facility olusturmaz, satiri "skipped" yapar).
 */
class BulkApproveDataImportRows extends Command
{
    protected $signature = 'veri-cekici:bulk-approve
        {--city= : Sadece bu sehir slug\'ina ait satirlar (opsiyonel)}
        {--publish=1 : Onaylanan kurumlar yayinlansin mi (is_published)}
        {--limit=0 : Maksimum kac satir islensin (0 = hepsi)}';

    protected $description = 'pending_review durumundaki veri cekici satirlarini toplu onaylar (aciklama+5 filigranli gorsel otomatik eklenir, mukerrerler atlanir)';

    public function handle(): int
    {
        $query = DataImportRow::with('batch.city')
            ->where('status', 'pending_review');

        if ($citySlug = $this->option('city')) {
            $query->whereHas('batch.city', fn ($q) => $q->where('slug', $citySlug));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $this->info("Islenecek satir: {$rows->count()}");

        $rowService = app(DataImportRowApprovalService::class);
        $publish = (bool) $this->option('publish');

        $created = 0;
        $skipped = 0;
        $errored = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            try {
                $rowService->approve($row, $publish);
                $created++;
            } catch (QueryException $e) {
                // QueryException, PHP'de RuntimeException'dan miras alir; asagidaki
                // RuntimeException catch'inden ONCE burada yakalanmazsa, beklenmedik
                // bir DB hatasi (orn. slug/telefon UNIQUE ihlali) "mukerrer atlandi"
                // olarak sayilir ve gercek bir hata sessizce gizlenir.
                $row->update(['status' => 'error', 'message' => $e->getMessage()]);
                $errored++;
            } catch (\RuntimeException $e) {
                // isDuplicate() zaten satiri 'skipped' yapip mesaj birakiyor;
                // bos isim gibi diger RuntimeException'lar icin de ayni sekilde isaretle.
                if ($row->fresh()->status !== 'skipped') {
                    $row->update(['status' => 'skipped', 'message' => $e->getMessage()]);
                }
                $skipped++;
            } catch (\Throwable $e) {
                $row->update(['status' => 'error', 'message' => $e->getMessage()]);
                $errored++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Olusturulan kurum: {$created} | Atlanan (mukerrer/eksik): {$skipped} | Hata: {$errored}");

        return self::SUCCESS;
    }
}
