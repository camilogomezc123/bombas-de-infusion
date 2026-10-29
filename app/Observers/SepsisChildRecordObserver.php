<?php

namespace App\Observers;

use App\Services\ClinicalAuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría genérica reutilizable para los registros clínicos repetibles del caso
 * de Sepsis (tamizaje, bundle, hemodinámica, cultivos, antimicrobianos, control del
 * foco, continuidad, educación). Reusa ClinicalAudit/ClinicalAuditService — el mismo
 * mecanismo ya usado por SepsisCase — en vez de crear un sistema de auditoría paralelo.
 */
class SepsisChildRecordObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        foreach ($model->getChanges() as $field => $newValue) {
            if ($field === 'updated_at') {
                continue;
            }

            $this->record($model, 'updated', $field, $model->getOriginal($field), $newValue);
        }
    }

    private function record(Model $model, string $event, ?string $field = null, mixed $oldValue = null, mixed $newValue = null): void
    {
        $programId = $model->case?->clinical_program_id;

        if (! $programId) {
            return;
        }

        app(ClinicalAuditService::class)->record($model, $programId, $event, $field, $oldValue, $newValue);
    }
}
