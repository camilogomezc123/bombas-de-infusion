<?php

namespace App\Console\Commands;

use App\Models\IcuStay;
use App\Services\Infusion\AntibioticContinuityGuard;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * A diferencia de infusion:ingest-events, esta revisión no depende de que llegue un
 * evento nuevo: una interrupción real de un antibiótico en infusión continua (código
 * sepsis) es precisamente la AUSENCIA de eventos nuevos, así que hay que revisar el
 * silencio periódicamente en vez de esperar a que algo dispare el cálculo.
 */
#[Signature('infusion:check-antibiotic-continuity')]
#[Description('Revisa interrupciones de antibióticos en infusión continua (código sepsis) en estancias activas')]
class CheckAntibioticContinuity extends Command
{
    public function handle(AntibioticContinuityGuard $guard): int
    {
        $stays = IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->whereHas('infusionEvents')
            ->get();

        foreach ($stays as $stay) {
            $guard->evaluate($stay);
        }

        $this->info("Revisadas {$stays->count()} estancia(s) activa(s) con eventos de bomba.");

        return self::SUCCESS;
    }
}
