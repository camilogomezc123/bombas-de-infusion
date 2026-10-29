<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\HomeMonitoringReading;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class HomeMonitoringComponent extends Component
{
    #[Validate('required|in:spo2,heart_rate,blood_pressure,temperature,respiratory_rate,glucose,weight,otro')]
    public string $reading_type = 'spo2';

    #[Validate('required|string|max:30')]
    public string $value = '';

    #[Validate('nullable|string|max:20')]
    public string $unit = '';

    #[Validate('required|date')]
    public string $measured_at = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount(): void
    {
        $this->measured_at = now()->format('Y-m-d\TH:i');
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

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->validate();

        HomeMonitoringReading::query()->create([
            'pics_case_id' => $case->id,
            'reading_type' => $this->reading_type,
            'value' => $this->value,
            'unit' => $this->unit ?: null,
            'measured_at' => $this->measured_at,
            'notes' => $this->notes ?: null,
            'recorded_by_type' => $actor::class,
            'recorded_by_id' => $actor->id,
        ]);

        $this->reset(['value', 'unit', 'notes']);
        $this->measured_at = now()->format('Y-m-d\TH:i');

        session()->flash('monitoring_status', 'Lectura registrada.');
        $this->dispatch('celebrate');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        $readings = $case
            ? $case->homeMonitoringReadings()->with('recordedBy')->orderByDesc('measured_at')->get()
            : collect();

        return view('livewire.portal.home-monitoring-component', [
            'case' => $case,
            'readings' => $readings,
        ]);
    }
}
