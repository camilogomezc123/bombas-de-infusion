<?php

namespace App\Console\Commands;

use App\Models\AcvCase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agora:recompute-months')]
#[Description('Recalcula el mes de cada caso ACV a partir de su fecha de egreso')]
class RecomputeCaseMonths extends Command
{
    public function handle(): int
    {
        $updated = 0;

        AcvCase::query()
            ->where('is_cancelled', false)
            ->chunkById(200, function ($cases) use (&$updated): void {
                foreach ($cases as $case) {
                    $month = $case->discharged_at?->format('Y/m');

                    if ($case->month !== $month) {
                        // updateQuietly evita generar registros de auditoría por el recálculo masivo.
                        $case->updateQuietly(['month' => $month]);
                        $updated++;
                    }
                }
            });

        $this->info("Meses recalculados por fecha de egreso. Casos actualizados: {$updated}.");

        return self::SUCCESS;
    }
}
