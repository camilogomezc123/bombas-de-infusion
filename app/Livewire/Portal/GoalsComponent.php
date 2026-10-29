<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\GoalProgressReport;
use App\Models\Patient;
use App\Models\RecoveryGoal;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class GoalsComponent extends Component
{
    public ?int $selectedGoalId = null;

    #[Validate('nullable|string')]
    public string $notes = '';

    public bool $had_difficulty = false;

    #[Validate('nullable|string')]
    public string $difficulty_reason = '';

    #[Validate('nullable|string')]
    public string $difficulty_reason_other = '';

    public function mount(): void
    {
        $case = PortalHomeController::currentCase();
        $this->selectedGoalId = $case?->recoveryGoals()->where('status', 'active')->value('id');
    }

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        abort_unless($case, 403);

        $reporter = null;
        if ($patient instanceof Patient && CaseAccess::patientCanAccess($patient, $case)) {
            $reporter = $patient;
        } elseif ($caregiver instanceof Caregiver && CaseAccess::caregiverCanAccess($caregiver, $case)) {
            $reporter = $caregiver;
        }

        abort_unless($reporter, 403);

        $goal = RecoveryGoal::query()->where('pics_case_id', $case->id)->findOrFail($this->selectedGoalId);

        $this->validate();

        GoalProgressReport::query()->create([
            'recovery_goal_id' => $goal->id,
            'reporter_type' => $reporter::class,
            'reporter_id' => $reporter->id,
            'reported_at' => now(),
            'notes' => $this->notes ?: null,
            'had_difficulty' => $this->had_difficulty,
            'difficulty_reason' => $this->had_difficulty ? ($this->difficulty_reason ?: null) : null,
            'difficulty_reason_other' => $this->had_difficulty && $this->difficulty_reason === 'otra' ? ($this->difficulty_reason_other ?: null) : null,
        ]);

        $this->reset(['notes', 'had_difficulty', 'difficulty_reason', 'difficulty_reason_other']);

        session()->flash('goals_status', 'Avance registrado. Tu equipo lo revisará.');
        $this->dispatch('celebrate');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        $goals = $case
            ? $case->recoveryGoals()->with(['progressReports' => fn ($q) => $q->latest('reported_at')->with('reporter', 'validatedBy')])->orderBy('created_at')->get()
            : collect();

        return view('livewire.portal.goals-component', [
            'case' => $case,
            'goals' => $goals,
        ]);
    }
}
