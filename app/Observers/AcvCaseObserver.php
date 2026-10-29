<?php

namespace App\Observers;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\AcvCase;
use App\Models\CaseAudit;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseConversationNotification;
use App\Notifications\CaseCorrectionsNotification;
use App\Support\HubMetrics;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AcvCaseObserver
{
    public function creating(AcvCase $case): void
    {
        if (! $case->case_sequence) {
            $next = ((int) AcvCase::query()
                ->whereNotNull('case_sequence')
                ->orderByDesc('case_sequence')
                ->lockForUpdate()
                ->value('case_sequence')) + 1;
            $case->case_sequence = $next;
            $case->case_number = 'S'.$next;
        }

        $case->created_by ??= auth()->id();
        $case->updated_by ??= auth()->id();
        $case->is_recurrence = AcvCase::query()
            ->where('patient_id', $case->patient_id)
            ->where('is_cancelled', false)
            ->exists();

        $this->syncWorkflowDates($case);
    }

    public function updating(AcvCase $case): void
    {
        $this->authorizeStatusTransition($case);
        $case->updated_by = auth()->id();

        // El auditor asignado, al guardar un caso recién asignado, lo pone "En revisión".
        if (! $case->isDirty('status')
            && $case->status === CaseStatus::Assigned
            && auth()->id() === $case->assigned_auditor_id) {
            $case->status = CaseStatus::InReview;
        }

        $this->syncWorkflowDates($case);
    }

    public function created(AcvCase $case): void
    {
        HubMetrics::forget();
        $this->record($case, 'created');
        $this->notifyAssignment($case);
    }

    public function updated(AcvCase $case): void
    {
        HubMetrics::forget();

        foreach ($case->getChanges() as $field => $newValue) {
            if (in_array($field, ['updated_at', 'updated_by'], true)) {
                continue;
            }

            $this->record($case, 'updated', $field, $case->getOriginal($field), $newValue);
        }

        // Notificar al auditor cuando el caso se envía a análisis, o si se reasigna
        // estando ya en el flujo de análisis.
        if ($case->wasChanged('status') && $case->status === CaseStatus::Assigned) {
            $this->notifyAssignment($case);
        } elseif ($case->wasChanged('assigned_auditor_id')
            && in_array($case->status, [CaseStatus::Assigned, CaseStatus::InReview, CaseStatus::Pending], true)) {
            $this->notifyAssignment($case);
        }

        // El líder cerró su revisión: enviar feedback de correcciones al auditor.
        if ($case->wasChanged('status')
            && $case->getOriginal('status') === CaseStatus::Pending->value
            && $case->status === CaseStatus::Completed) {
            $this->notifyCorrections($case);
        }

        if ($case->wasChanged('clinical_data')) {
            $this->notifyObservationChange($case);
        }
    }

    public function deleted(AcvCase $case): void
    {
        HubMetrics::forget();
    }

    private function syncWorkflowDates(AcvCase $case): void
    {
        $status = $case->status instanceof CaseStatus ? $case->status : CaseStatus::from($case->status);

        if ($case->isDirty('assigned_auditor_id') && $case->assigned_auditor_id) {
            $case->assigned_at = now();
        }

        if ($status === CaseStatus::InReview && ! $case->analysis_started_at) {
            $case->analysis_started_at = now();
        }

        // El auditor finalizó: se congela el cronómetro de análisis.
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

    private function authorizeStatusTransition(AcvCase $case): void
    {
        if (! $case->isDirty('status') || ! auth()->check()) {
            return;
        }

        $previous = $case->getOriginal('status');
        $next = $case->status instanceof CaseStatus ? $case->status->value : $case->status;
        $user = auth()->user();
        $isManager = $user->canManageAllCases();
        $isAssignedAuditor = $case->assigned_auditor_id === $user->id;

        if ($next === CaseStatus::Cancelled->value && ! $isManager) {
            throw ValidationException::withMessages([
                'status' => 'Solo el líder o el administrador pueden anular un caso.',
            ]);
        }

        if ($previous === CaseStatus::Completed->value && $next !== CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages([
                'status' => 'Solo el líder o el administrador pueden reabrir un caso finalizado.',
            ]);
        }

        if ($next === CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages([
                'status' => 'Solo el líder o el administrador finalizan la revisión.',
            ]);
        }

        if ($previous === CaseStatus::Hospitalized->value
            && $next === CaseStatus::Assigned->value
            && ! $isManager) {
            throw ValidationException::withMessages([
                'status' => 'Solo el líder o el administrador envían casos a análisis.',
            ]);
        }

        if ($next === CaseStatus::Pending->value && ! ($isManager || $isAssignedAuditor)) {
            throw ValidationException::withMessages([
                'status' => 'Solo el auditor asignado puede finalizar el análisis de este caso.',
            ]);
        }
    }

    private function record(
        AcvCase $case,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
    ): void {
        CaseAudit::query()->create([
            'acv_case_id' => $case->id,
            'user_id' => auth()->id(),
            'event' => $event,
            'field' => $field,
            'old_value' => $this->stringify($oldValue),
            'new_value' => $this->stringify($newValue),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function notifyAssignment(AcvCase $case): void
    {
        if (! $case->assigned_auditor_id) {
            return;
        }

        $case->loadMissing(['assignedAuditor', 'patient']);
        $case->assignedAuditor?->notify(new CaseAssignedNotification($case));
    }

    /**
     * Envía al auditor el detalle de las correcciones hechas por el líder durante su revisión.
     */
    private function notifyCorrections(AcvCase $case): void
    {
        if (! $case->assigned_auditor_id) {
            return;
        }

        $case->loadMissing(['assignedAuditor', 'patient']);
        $auditor = $case->assignedAuditor;

        if (! $auditor) {
            return;
        }

        $since = $case->auditor_finalized_at ?? $case->assigned_at;

        $audits = CaseAudit::query()
            ->where('acv_case_id', $case->id)
            ->where('event', 'updated')
            ->where('user_id', auth()->id())
            ->where('field', '!=', 'status')
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->orderBy('created_at')
            ->get();

        $auditor->notify(new CaseCorrectionsNotification($case, $this->buildCorrections($audits)));
    }

    private function notifyObservationChange(AcvCase $case): void
    {
        $oldData = $this->arrayValue($case->getOriginal('clinical_data'));
        $newData = $this->arrayValue($case->clinical_data);
        $oldObservation = trim((string) data_get($oldData, 'observations'));
        $newObservation = trim((string) data_get($newData, 'observations'));

        if ($oldObservation === $newObservation || $newObservation === '') {
            return;
        }

        $case->loadMissing(['assignedAuditor', 'patient']);
        $actor = auth()->user();

        if (! $actor) {
            return;
        }

        $notification = new CaseConversationNotification(
            $case,
            "Observaciones actualizadas · {$case->case_number}",
            "{$actor->name} actualizó las observaciones del caso {$case->case_number}.",
        );

        if ($actor->canManageAllCases()) {
            if ($case->assignedAuditor && $case->assignedAuditor->id !== $actor->id) {
                $case->assignedAuditor->notify($notification);
            }

            return;
        }

        User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Administrator, UserRole::Leader])
            ->whereKeyNot($actor->id)
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }

    /**
     * Colapsa los registros de auditoría en una lista de correcciones por campo.
     *
     * @return array<int, array{label: string, old: ?string, new: ?string}>
     */
    private function buildCorrections(Collection $audits): array
    {
        $changes = [];

        foreach ($audits as $audit) {
            if ($audit->field === 'clinical_data') {
                $old = json_decode((string) $audit->old_value, true) ?: [];
                $new = json_decode((string) $audit->new_value, true) ?: [];

                foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
                    $oldVal = $this->scalarize($old[$key] ?? null);
                    $newVal = $this->scalarize($new[$key] ?? null);

                    if ($oldVal === $newVal) {
                        continue;
                    }

                    $changes["clinical_data.{$key}"] ??= ['label' => $this->fieldLabel("clinical_data.{$key}"), 'old' => $oldVal];
                    $changes["clinical_data.{$key}"]['new'] = $newVal;
                }

                continue;
            }

            $changes[$audit->field] ??= ['label' => $this->fieldLabel($audit->field), 'old' => $audit->old_value];
            $changes[$audit->field]['new'] = $audit->new_value;
        }

        return array_values(array_filter(
            $changes,
            fn (array $change): bool => ($change['old'] ?? null) !== ($change['new'] ?? null),
        ));
    }

    private function scalarize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function fieldLabel(string $field): string
    {
        return [
            'admission_number' => 'Número de ingreso',
            'resq_code' => 'Código ResQ',
            'eapb' => 'EAPB',
            'discharge_destination' => 'Destino del egreso',
            'health_regime' => 'Régimen de salud',
            'stroke_type' => 'Tipo de ACV',
            'arrival_at' => 'Ingreso / hora puerta',
            'last_known_well_at' => 'Última vez en buen estado',
            'imaging_at' => 'Hora de imagen',
            'thrombolysis_at' => 'Hora de trombólisis',
            'groin_puncture_at' => 'Punción inguinal',
            'revascularization_at' => 'Revascularización',
            'speech_therapy_at' => 'Valoración por fonoaudiología',
            'physiotherapy_at' => 'Valoración por fisioterapia',
            'discharged_at' => 'Fecha y hora de egreso',
            'thrombolysed' => 'Trombolizado',
            'thrombectomy' => 'Trombectomía',
            'deceased' => 'Fallecido',
            'hemorrhagic_transformation' => 'Transformación hemorrágica',
            'month' => 'Mes',
            'assigned_auditor_id' => 'Auditor asignado',
            'clinical_data.imaging_type' => 'Imagen realizada',
            'clinical_data.nihss_arrival' => 'NIHSS al ingreso',
            'clinical_data.rankin_arrival' => 'Rankin al ingreso',
            'clinical_data.code_activated' => '¿Se activó código?',
            'clinical_data.wake_up_stroke' => 'ACV de despertar',
            'clinical_data.inpatient_stroke' => 'ACV intrahospitalario',
            'clinical_data.treatment_dose_mg' => 'Dosis en mg',
            'clinical_data.tici' => 'TICI',
            'clinical_data.stroke_cause' => 'Causa del ACV',
            'clinical_data.three_month_contact_type' => 'Contacto a tres meses',
            'clinical_data.rankin_three_months' => 'Rankin a los tres meses',
            'clinical_data.observations' => 'Observaciones',
        ][$field] ?? ucfirst(str_replace(['clinical_data.', '_'], ['', ' '], $field));
    }
}
