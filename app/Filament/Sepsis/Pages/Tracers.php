<?php

namespace App\Filament\Sepsis\Pages;

use App\Models\ProgramMember;
use App\Models\ProgramResource;
use App\Models\SepsisCase;
use App\Models\SepsisSafetyEvent;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class Tracers extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Trazadores';

    protected static ?string $title = 'Trazadores del programa';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'trazadores';

    protected string $view = 'filament.sepsis.tracers';

    public ?int $caseId = null;

    #[Computed]
    public function caseOptions(): array
    {
        return SepsisCase::query()
            ->whereHas('program', fn (Builder $query) => $query->where('code', 'SEPSIS'))
            ->with('patient')
            ->orderByDesc('case_sequence')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (SepsisCase $case): array => [
                $case->id => "{$case->case_number} · ".($case->patient?->full_name ?? 'Sin paciente'),
            ])
            ->all();
    }

    #[Computed]
    public function patientTracer(): ?array
    {
        if (! $this->caseId) {
            return null;
        }

        $case = SepsisCase::query()
            ->with([
                'patient', 'assignedAuditor', 'screenings.recordedBy', 'bundleTasks.responsible', 'hemodynamicAssessments',
                'cultures', 'antimicrobialAdministrations', 'sourceControlActions', 'careTransitions',
                'educationRecords', 'timeZeroValidator', 'safetyEvents', 'postsepsisFollowups',
            ])
            ->find($this->caseId);

        if (! $case) {
            return null;
        }

        $events = collect();

        if ($case->registered_on) {
            $events->push(['at' => $case->registered_on, 'label' => 'Registro del caso', 'group' => 'Tamizaje']);
        }
        foreach ($case->screenings as $screening) {
            $events->push(['at' => $screening->occurred_at, 'label' => 'Tamizaje: '.($screening->result ?? 'registrado'), 'group' => 'Tamizaje']);
        }
        if ($case->activation_at) {
            $events->push(['at' => $case->activation_at, 'label' => 'Tiempo cero / activación del código', 'group' => 'Activación']);
        }
        if ($case->time_zero_validated_at) {
            $events->push(['at' => $case->time_zero_validated_at, 'label' => 'Tiempo cero validado por '.($case->timeZeroValidator?->name ?? 'usuario'), 'group' => 'Activación']);
        }
        foreach ($case->bundleTasks as $task) {
            $events->push(['at' => $task->done_at ?? $task->target_at, 'label' => $task->label.': '.$task->statusLabel(), 'group' => 'Bundle']);
        }
        foreach ($case->cultures as $culture) {
            $events->push(['at' => $culture->taken_at, 'label' => 'Cultivo '.$culture->site.($culture->microorganism ? " — {$culture->microorganism}" : ''), 'group' => 'Infección']);
        }
        foreach ($case->antimicrobialAdministrations as $admin) {
            $events->push(['at' => $admin->administered_at ?? $admin->ordered_at, 'label' => 'Antimicrobiano: '.$admin->drug, 'group' => 'Infección']);
        }
        foreach ($case->sourceControlActions as $action) {
            $events->push(['at' => $action->performed_at ?? $action->assessed_at, 'label' => 'Control del foco: '.($action->procedure ?? $action->decision ?? 'registrado'), 'group' => 'Infección']);
        }
        foreach ($case->hemodynamicAssessments as $assessment) {
            $events->push(['at' => $assessment->assessed_at, 'label' => 'Reevaluación hemodinámica'.($assessment->phenotype ? " ({$assessment->phenotype})" : ''), 'group' => 'Hemodinámica']);
        }
        foreach ($case->careTransitions as $transition) {
            $events->push(['at' => $transition->transitioned_at, 'label' => "Traslado {$transition->origin_service} → {$transition->destination_service}", 'group' => 'Continuidad']);
        }
        foreach ($case->educationRecords as $education) {
            $events->push(['at' => $education->occurred_at, 'label' => 'Educación a '.$education->informed_person, 'group' => 'Paciente y familia']);
        }
        if ($case->discharged_at) {
            $events->push(['at' => $case->discharged_at, 'label' => 'Egreso ('.($case->outcome_state ?? 'sin estado').')', 'group' => 'Desenlace']);
        }
        if ($case->death_at) {
            $events->push(['at' => $case->death_at, 'label' => 'Defunción', 'group' => 'Desenlace']);
        }
        foreach ($case->safetyEvents as $safetyEvent) {
            $events->push([
                'at' => $safetyEvent->event_at ?? $safetyEvent->occurred_on,
                'label' => 'Evento de seguridad: '.(SepsisSafetyEvent::EVENT_TYPES[$safetyEvent->event_type] ?? $safetyEvent->event_type),
                'group' => 'Seguridad',
            ]);
        }

        $timeline = $events
            ->filter(fn (array $event): bool => filled($event['at']))
            ->sortBy('at')
            ->values();

        return [
            'case' => $case,
            'timeline' => $timeline,
            'bundle_compliance' => $this->bundleCompliance($case),
            'missing_records' => $this->missingRecords($case),
            'professionals' => $this->participatingProfessionals($case),
        ];
    }

    /**
     * Resumen de cumplimiento del bundle para ESTE caso puntual — no es el indicador
     * institucional bundle_pct (ese se calcula agregado en SepsisIndicatorService).
     *
     * @return array{total: int, done: int, done_late: int, percentage: ?float}
     */
    private function bundleCompliance(SepsisCase $case): array
    {
        $tasks = $case->bundleTasks;
        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();
        $doneLate = $tasks->where('status', 'done_late')->count();

        return [
            'total' => $total,
            'done' => $done,
            'done_late' => $doneLate,
            'percentage' => $total > 0 ? round((($done + $doneLate) / $total) * 100, 1) : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function missingRecords(SepsisCase $case): array
    {
        $sections = [
            'screenings' => 'Tamizaje',
            'bundleTasks' => 'Bundle de primera hora',
            'hemodynamicAssessments' => 'Reevaluación hemodinámica',
            'cultures' => 'Cultivos',
            'antimicrobialAdministrations' => 'Antimicrobianos',
            'sourceControlActions' => 'Control del foco',
            'careTransitions' => 'Continuidad / traslados',
            'educationRecords' => 'Educación al paciente y la familia',
            'postsepsisFollowups' => 'Seguimiento postsepsis',
        ];

        return collect($sections)
            ->filter(fn (string $label, string $relation): bool => $case->{$relation}->isEmpty())
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function participatingProfessionals(SepsisCase $case): array
    {
        $names = collect([$case->assignedAuditor?->name, $case->timeZeroValidator?->name])
            ->merge($case->screenings->map(fn ($screening) => $screening->recordedBy?->name ?? $screening->clinician_name))
            ->merge($case->bundleTasks->map(fn ($task) => $task->responsible?->name));

        return $names->filter()->unique()->values()->all();
    }

    #[Computed]
    public function systemTracers(): array
    {
        $program = ProgramAccess::program();

        $members = ProgramMember::query()->where('clinical_program_id', $program?->id)->where('is_active', true)->get();
        $competencyReady = $members->filter(fn (ProgramMember $member): bool => $member->competency_valid_until && $member->competency_valid_until->isFuture())->count();

        $resources = ProgramResource::query()->where('clinical_program_id', $program?->id)->get();
        $resourcesAvailable = $resources->where('availability', 'available')->count();

        return [
            'team_total' => $members->count(),
            'team_competency_ready' => $competencyReady,
            'resources_total' => $resources->count(),
            'resources_available' => $resourcesAvailable,
            'open_safety_events' => SepsisSafetyEvent::query()->where('clinical_program_id', $program?->id)->where('status', '!=', 'closed')->count(),
        ];
    }
}
