<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\AcvCase;
use App\Models\User;
use App\Notifications\WeeklyAcvSummaryNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agora:send-weekly-summary')]
#[Description('Envía a cada auditor el resumen semanal de casos ACV')]
class SendWeeklyAcvSummary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('role', UserRole::Auditor)
            ->where('is_active', true)
            ->each(function (User $auditor) use (&$sent): void {
                $cases = AcvCase::query()
                    ->where('assigned_auditor_id', $auditor->id)
                    ->where('is_cancelled', false)
                    ->where('status', '!=', CaseStatus::Completed->value);

                $assigned = (clone $cases)->count();
                $pending = (clone $cases)->where('status', CaseStatus::Pending->value)->count();
                $overdue = (clone $cases)
                    ->whereNotNull('assigned_at')
                    ->where('assigned_at', '<=', now()->subDays(7))
                    ->count();

                if ($assigned === 0) {
                    return;
                }

                $auditor->notify(new WeeklyAcvSummaryNotification($assigned, $pending, $overdue));
                $sent++;
            });

        $this->info("Resumen semanal generado para {$sent} auditor(es).");

        return self::SUCCESS;
    }
}
