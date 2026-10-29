<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Ruta propia del cuidador: exclusiva de este actor (a diferencia del resto del
 * portal, que comparten paciente y cuidador), por eso aborta ya en mount() si el
 * cuidador no tiene la autorización can_access_journey vigente.
 */
class CaregiverJourneyComponent extends Component
{
    /** @var array<int, string> */
    public array $notes = [];

    public function mount(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = Auth::guard('caregiver')->user();

        abort_unless(
            $case && $actor instanceof Caregiver && CaseAccess::caregiverCanAccessJourney($actor, $case),
            403,
        );
    }

    public function markComplete(int $stepId): void
    {
        $case = PortalHomeController::currentCase();
        $actor = Auth::guard('caregiver')->user();
        abort_unless(
            $case && $actor instanceof Caregiver && CaseAccess::caregiverCanAccessJourney($actor, $case),
            403,
        );

        $step = $case->caregiverJourneySteps()->findOrFail($stepId);
        $step->update([
            'reported_by_type' => Caregiver::class,
            'reported_by_id' => $actor->id,
            'reported_at' => now(),
            'caregiver_notes' => $this->notes[$stepId] ?? null,
        ]);
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        return view('livewire.portal.caregiver-journey-component', [
            'steps' => $case?->caregiverJourneySteps()->orderBy('sort_order')->get() ?? collect(),
        ]);
    }
}
