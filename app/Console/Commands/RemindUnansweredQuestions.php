<?php

namespace App\Console\Commands;

use App\Models\FacilityQuestion;
use App\Models\FacilityUser;
use Illuminate\Console\Command;

// 21 Temmuz 2026: aile bir kuruma soru sordugunda kurum bildirim aliyor
// (bkz. Public/FacilityQuestionController::store) ama cevaplamazsa hicbir
// hatirlatma gitmiyordu - soru suresiz "pending" kalabiliyordu, aile
// belirsiz sure bekliyordu. Bu komut, belirtilen saatten eski VE hala
// cevapsiz sorular icin kuruma TEK SEFERLIK bir hatirlatma gonderir
// (reminder_sent_at ile isaretlenir, tekrar tekrar gonderilmez).
class RemindUnansweredQuestions extends Command
{
    protected $signature = 'questions:remind-unanswered {--hours=48 : Bu saatten eski cevapsiz sorular icin hatirlatma gonderilir}';

    protected $description = 'Belirtilen saatten eski, hala cevaplanmamis aile sorulari icin kuruma tek seferlik hatirlatma gonderir';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $questions = FacilityQuestion::pending()
            ->whereNull('reminder_sent_at')
            ->where('created_at', '<=', $cutoff)
            ->with('facility')
            ->get();

        $count = 0;
        foreach ($questions as $question) {
            $facility = $question->facility;
            if (! $facility) {
                continue;
            }

            FacilityUser::where('facility_id', $facility->id)->get()->each(
                fn (FacilityUser $user) => notify_user(
                    $user,
                    'question_reminder',
                    'Cevaplanmamış bir sorunuz var',
                    'Bir aile '.$hours.' saat önce soru sordu, henüz cevaplanmadı: "'.\Illuminate\Support\Str::limit($question->question, 100).'"',
                    ['facility_question_id' => $question->id]
                )
            );

            $question->update(['reminder_sent_at' => now()]);
            $count++;
        }

        $this->info("{$count} cevapsiz soru icin hatirlatma gonderildi (>{$hours} saat).");

        return self::SUCCESS;
    }
}
