<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\EducationAssignment;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EducationComponent extends Component
{
    /** @var array<int, string> */
    public array $responses = [];

    /** @var array<int, string> */
    public array $feedback = [];

    public function mount(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();

        if (! $case || ! $actor) {
            return;
        }

        foreach ($this->assignmentsForActor($case, $actor)->get() as $assignment) {
            $this->responses[$assignment->id] = $assignment->response ?? '';
            $this->feedback[$assignment->id] = $assignment->feedback ?? '';
        }
    }

    private function actor(): Patient|Caregiver|null
    {
        $case = PortalHomeController::currentCase();
        if (! $case) {
            return null;
        }

        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        if ($patient instanceof Patient && CaseAccess::patientCanAccess($patient, $case)) {
            return $patient;
        }

        if ($caregiver instanceof Caregiver && CaseAccess::caregiverCanAccess($caregiver, $case)) {
            return $caregiver;
        }

        return null;
    }

    /**
     * El catálogo puede dirigirse al paciente, al cuidador o a ambos. Además de
     * comprobar el caso en cada escritura, esta consulta evita mostrar o aceptar
     * respuestas de un actor distinto a la audiencia definida por el equipo.
     */
    private function assignmentsForActor($case, Patient|Caregiver $actor)
    {
        $audiences = $actor instanceof Patient
            ? ['paciente', 'ambos']
            : ['cuidador', 'ambos'];

        return $case->educationAssignments()
            ->whereHas('resource', fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('audience', $audiences));
    }

    private function assignmentForActor(int $assignmentId): EducationAssignment
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        return $this->assignmentsForActor($case, $actor)->findOrFail($assignmentId);
    }

    public function markViewed(int $assignmentId): void
    {
        $actor = $this->actor();
        abort_unless($actor, 403);

        $assignment = $this->assignmentForActor($assignmentId);
        $assignment->update([
            'viewed_by_type' => $actor::class,
            'viewed_by_id' => $actor->id,
            'viewed_at' => now(),
        ]);

        $this->dispatch('celebrate');
    }

    public function saveResponse(int $assignmentId): void
    {
        $actor = $this->actor();
        abort_unless($actor, 403);

        $assignment = $this->assignmentForActor($assignmentId);
        $data = validator([
            'response' => $this->responses[$assignmentId] ?? null,
            'feedback' => $this->feedback[$assignmentId] ?? null,
        ], [
            'response' => ['required', Rule::in(array_keys(EducationAssignment::RESPONSES))],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [
            'response.required' => 'Selecciona cómo te resultó el contenido.',
            'response.in' => 'La respuesta seleccionada no es válida.',
            'feedback.max' => 'El comentario no puede superar 2000 caracteres.',
        ])->validate();

        $completedAt = now();
        $assignment->update([
            'viewed_by_type' => $actor::class,
            'viewed_by_id' => $actor->id,
            'viewed_at' => $assignment->viewed_at ?? $completedAt,
            'response' => $data['response'],
            'feedback' => filled($data['feedback'] ?? null) ? trim($data['feedback']) : null,
            'completed_by_type' => $actor::class,
            'completed_by_id' => $actor->id,
            'completed_at' => $completedAt,
            // Toda edición o nueva entrega vuelve a la bandeja de validación.
            'validated_by' => null,
            'validated_at' => null,
            'validation_notes' => null,
        ]);

        session()->flash('education_status', 'Respuesta enviada. Tu equipo la revisará.');
        $this->dispatch('celebrate');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();

        $assignments = $case && $actor
            ? $this->assignmentsForActor($case, $actor)
                ->with('resource')
                ->get()
            : collect();

        return view('livewire.portal.education-component', [
            'assignments' => $assignments,
        ]);
    }
}
