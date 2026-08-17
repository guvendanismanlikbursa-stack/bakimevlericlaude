<?php

namespace App\Services;

use App\Models\ScheduledJobRun;
use Illuminate\Console\Scheduling\Event;

/**
 * 17 Agustos 2026: kullanicinin talebi - bkz. scheduled_job_runs migration
 * yorumu. routes/console.php'deki HER zamanlanmis gorev, kendi ->onSuccess()/
 * ->onFailure() callback'iyle burayi cagirir - boylece bir gorev sessizce
 * calismayi biraktiginda bunu SADECE tesadufen degil, /admin/zamanlanan-
 * gorevler ekraninda VE /_saglik ucunda gorebiliriz.
 */
class ScheduledJobMonitor
{
    public static function attach(Event $event, string $jobName, int $expectedFrequencyMinutes): Event
    {
        return $event
            ->onSuccess(function () use ($jobName, $expectedFrequencyMinutes) {
                self::recordSuccess($jobName, $expectedFrequencyMinutes);
            })
            ->onFailure(function () use ($jobName, $expectedFrequencyMinutes) {
                self::recordFailure($jobName, $expectedFrequencyMinutes);
            });
    }

    public static function recordSuccess(string $jobName, int $expectedFrequencyMinutes): void
    {
        ScheduledJobRun::updateOrCreate(
            ['job_name' => $jobName],
            [
                'expected_frequency_minutes' => $expectedFrequencyMinutes,
                'last_success_at' => now(),
                'consecutive_failures' => 0,
            ]
        );
    }

    public static function recordFailure(string $jobName, int $expectedFrequencyMinutes): void
    {
        $run = ScheduledJobRun::firstOrNew(['job_name' => $jobName]);
        $run->expected_frequency_minutes = $expectedFrequencyMinutes;
        $run->last_failure_at = now();
        $run->consecutive_failures = ($run->consecutive_failures ?? 0) + 1;
        $run->save();
    }
}
