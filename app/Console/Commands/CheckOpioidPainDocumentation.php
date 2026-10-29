<?php

namespace App\Console\Commands;

use App\Models\IcuStay;
use App\Services\Infusion\OpioidPainAssessmentGuard;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Igual que infusion:check-antibiotic-continuity: el vacío de documentación crece con
 * el solo paso del tiempo, no con un evento nuevo de la bomba, así que hay que
 * revisarlo periódicamente en vez de esperar a que algo lo dispare.
 */
#[Signature('infusion:check-opioid-pain-documentation')]
#[Description('Revisa infusiones de opioide activas sin evaluación de dolor reciente en ICU Liberation')]
class CheckOpioidPainDocumentation extends Command
{
    public function handle(OpioidPainAssessmentGuard $guard): int
    {
        $stays = IcuStay::query()
            ->where('status', 'active')
            ->where('is_cancelled', false)
            ->whereHas('infusionEvents', fn ($q) => $q->whereHas('drugInfusionProfile', fn ($q2) => $q2->where('category', 'analgesia')))
            ->get();

        foreach ($stays as $stay) {
            $guard->evaluate($stay);
        }

        $this->info("Revisadas {$stays->count()} estancia(s) con infusión de opioide activa.");

        return self::SUCCESS;
    }
}
