<?php

namespace App\Console\Commands;

use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\ProgramMember;
use App\Notifications\SepsisMonthlyDigestNotification;
use App\Services\SepsisExecutiveSummaryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agora:send-sepsis-monthly-digest')]
#[Description('Envía el resumen mensual del Programa de Sepsis a Dirección y al Coordinador del programa')]
class SendSepsisMonthlyDigest extends Command
{
    public function handle(): int
    {
        $program = ClinicalProgram::query()->where('code', 'SEPSIS')->first();

        if (! $program) {
            $this->warn('No existe el programa SEPSIS todavía.');

            return self::SUCCESS;
        }

        $summary = app(SepsisExecutiveSummaryService::class)->summary();

        $recipients = ProgramMember::query()
            ->where('clinical_program_id', $program->id)
            ->where('is_active', true)
            ->whereIn('role', [ProgramRole::ExecutiveDirection->value, ProgramRole::Coordinator->value, ProgramRole::Leader->value])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => $user && $user->is_active)
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new SepsisMonthlyDigestNotification($summary));
        }

        $this->info("Resumen mensual enviado a {$recipients->count()} destinatario(s).");

        return self::SUCCESS;
    }
}
