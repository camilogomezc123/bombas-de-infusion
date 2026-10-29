<?php

namespace App\Console\Commands;

use App\Models\AcvCase;
use App\Models\CaseFollowup;
use Illuminate\Console\Command;

class CloseExpiredFollowups extends Command
{
    protected $signature = 'agora:close-expired-followups';

    protected $description = 'Marca como no efectivo el seguimiento de casos con más de 120 días de egreso sin registro.';

    public function handle(): int
    {
        $cases = AcvCase::query()
            ->whereNotNull('discharged_at')
            ->where('discharged_at', '<=', now()->subDays(120))
            ->where('is_cancelled', false)
            ->whereDoesntHave('followup')
            ->where(fn ($q) => $q->whereNull('deceased')->orWhere('deceased', false))
            ->where(fn ($q) => $q->whereNull('discharge_destination')->orWhere('discharge_destination', '!=', 'Remitido'))
            ->where(fn ($q) => $q->whereNull('stroke_type')->orWhere('stroke_type', '!=', 'Imitador del Ictus'))
            ->get();

        if ($cases->isEmpty()) {
            $this->info('Sin casos expirados pendientes.');

            return self::SUCCESS;
        }

        foreach ($cases as $case) {
            CaseFollowup::create([
                'acv_case_id'   => $case->id,
                'user_id'       => null,
                'contacted_at'  => null,
                'is_effective'  => false,
                'rankin_90_days' => null,
                'observations'  => 'Seguimiento cerrado automáticamente por el sistema (más de 120 días sin registro).',
                'is_auto_closed' => true,
            ]);
        }

        $this->info("Cerrados automáticamente: {$cases->count()} casos.");

        return self::SUCCESS;
    }
}
