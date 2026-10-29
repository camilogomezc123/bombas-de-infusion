<?php

namespace App\Observers;

use App\Enums\CaseStatus;
use App\Models\SepsisCase;
use App\Notifications\SepsisCaseAssignedNotification;
use App\Services\ClinicalAuditService;
use App\Support\HubMetrics;
use App\Support\ProgramAccess;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class SepsisCaseObserver
{
    public function creating(SepsisCase $case): void
    {
        $case->clinical_program_id ??= ProgramAccess::program()?->id;
        if (! $case->case_sequence) {
            $next = ((int) SepsisCase::query()->lockForUpdate()->max('case_sequence')) + 1;
            $case->case_sequence = $next;
            $case->case_number = 'SP'.$next;
        }

        $case->created_by ??= auth()->id();
        $case->updated_by ??= auth()->id();

        $this->syncWorkflowDates($case);
    }

    public function updating(SepsisCase $case): void
    {
        $this->authorizeStatusTransition($case);
        $case->updated_by = auth()->id();

        if (! $case->isDirty('status')
            && $case->status === CaseStatus::Assigned
            && auth()->id() === $case->assigned_auditor_id) {
            $case->status = CaseStatus::InReview;
        }

        $this->syncWorkflowDates($case);
    }

    public function created(SepsisCase $case): void
    {
        HubMetrics::forget();
        $this->record($case, 'created');
    }

    public function updated(SepsisCase $case): void
    {
        HubMetrics::forget();

        foreach ($case->getChanges() as $field => $newValue) {
            if (in_array($field, ['updated_at', 'updated_by'], true)) {
                continue;
            }

            $this->record($case, 'updated', $field, $case->getOriginal($field), $newValue);
        }

        if ($case->wasChanged('status') && $case->status === CaseStatus::Assigned) {
            $this->notifyAssignment($case);
        } elseif ($case->wasChanged('assigned_auditor_id')
            && in_array($case->status, [CaseStatus::Assigned, CaseStatus::InReview, CaseStatus::Pending], true)) {
            $this->notifyAssignment($case);
        }
    }

    public function deleted(SepsisCase $case): void
    {
        HubMetrics::forget();
    }

    private function syncWorkflowDates(SepsisCase $case): void
    {
        // El egreso define el periodo definitivo. Mientras el caso siga abierto,
        // se usa el mejor hito disponible para incorporarlo a estadísticas.
        if ($case->discharged_at) {
            $case->month = $case->discharged_at->format('Y/m');
        } elseif (blank($case->month)) {
            $periodDate = $case->activation_at
                ?? $case->admission_at
                ?? $case->registered_on
                ?? now();

            $case->month = CarbonImmutable::parse($periodDate)->format('Y/m');
        }

        if ($case->isDirty('assigned_auditor_id') && $case->assigned_auditor_id) {
            $case->assigned_at = now();
        }

        if ($case->status === null) {
            // Sin estado explícito todavía (p. ej. creación programática): se deja que
            // el valor por defecto de la columna se aplique en el insert; nada que sincronizar aún.
            return;
        }

        $status = $case->status instanceof CaseStatus ? $case->status : CaseStatus::from($case->status);

        if ($status === CaseStatus::InReview && ! $case->analysis_started_at) {
            $case->analysis_started_at = now();
        }

        if ($status === CaseStatus::Pending && ! $case->auditor_finalized_at) {
            $case->auditor_finalized_at = now();
        }

        if ($status === CaseStatus::Completed && ! $case->completed_at) {
            $case->completed_at = now();
        }

        if ($status === CaseStatus::Cancelled) {
            $case->is_cancelled = true;
            $case->cancelled_at ??= now();
        }
    }

    private function authorizeStatusTransition(SepsisCase $case): void
    {
        if (! $case->isDirty('status') || ! auth()->check()) {
            return;
        }

        $previous = $case->getOriginal('status');
        $next = $case->status instanceof CaseStatus ? $case->status->value : $case->status;
        $user = auth()->user();
        $isManager = $user->canManageSepsisCases();
        $isAssignedAuditor = $case->assigned_auditor_id === $user->id;

        if ($next === CaseStatus::Cancelled->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador pueden anular un caso.']);
        }

        if ($previous === CaseStatus::Completed->value && $next !== CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador pueden reabrir un caso finalizado.']);
        }

        if ($next === CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador finalizan la revisión.']);
        }

        if ($previous === CaseStatus::Hospitalized->value && $next === CaseStatus::Assigned->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador envían casos a análisis.']);
        }

        if ($next === CaseStatus::Pending->value && ! ($isManager || $isAssignedAuditor)) {
            throw ValidationException::withMessages(['status' => 'Solo el auditor asignado puede finalizar el análisis de este caso.']);
        }
    }

    private function notifyAssignment(SepsisCase $case): void
    {
        if (! $case->assigned_auditor_id) {
            return;
        }

        $case->loadMissing('assignedAuditor');
        $case->assignedAuditor?->notify(new SepsisCaseAssignedNotification($case));
    }

    private function record(
        SepsisCase $case,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
    ): void {
        if (! $case->clinical_program_id) {
            return;
        }

        app(ClinicalAuditService::class)->record(
            $case,
            $case->clinical_program_id,
            $event,
            $field,
            $oldValue,
            $newValue,
        );
    }
}
