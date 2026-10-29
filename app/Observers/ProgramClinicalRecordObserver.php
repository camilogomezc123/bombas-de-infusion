<?php

namespace App\Observers;

use App\Services\ClinicalAuditService;
use App\Support\HubMetrics;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría reutilizable para registros clínicos de cualquier programa.
 */
class ProgramClinicalRecordObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
        HubMetrics::forget();
    }

    public function updated(Model $model): void
    {
        foreach ($model->getChanges() as $field => $newValue) {
            if ($field === 'updated_at') {
                continue;
            }

            $this->record($model, 'updated', $field, $model->getOriginal($field), $newValue);
        }
        HubMetrics::forget();
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
        HubMetrics::forget();
    }

    private function record(
        Model $model,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
    ): void {
        $case = method_exists($model, 'case') ? $model->case : null;
        $program = method_exists($model, 'program') ? $model->program : $case?->program;
        $programId = $model->getAttribute('clinical_program_id') ?? $case?->clinical_program_id;

        if (! $programId) {
            return;
        }

        app(ClinicalAuditService::class)->record(
            $model,
            (int) $programId,
            $event,
            $field,
            $oldValue,
            $newValue,
            metadata: ['program_code' => $program?->code],
        );
    }
}
