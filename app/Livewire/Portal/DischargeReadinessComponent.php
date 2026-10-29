<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DischargeReadinessComponent extends Component
{
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

    public function reviewItem(int $itemId): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $item = $case->dischargeReadinessCheck?->items()->findOrFail($itemId);
        abort_unless($item, 404);

        $item->update([
            'reviewed_by_type' => $actor::class,
            'reviewed_by_id' => $actor->id,
            'reviewed_at' => now(),
        ]);

        $this->dispatch('celebrate');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        return view('livewire.portal.discharge-readiness-component', [
            'check' => $case?->dischargeReadinessCheck()->with('items')->first(),
        ]);
    }
}
