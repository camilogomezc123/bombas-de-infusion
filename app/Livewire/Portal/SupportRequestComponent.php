<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\SupportRequest;
use App\Support\Posuci\CaseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SupportRequestComponent extends Component
{
    #[Validate('required|string|min:5')]
    public string $description = '';

    #[Validate('required|in:baja,media,alta')]
    public string $priority = 'media';

    public string $type = 'dificultad';

    /**
     * Permite llegar desde otro módulo (ej. Medicamentos, "Tengo una duda sobre este
     * medicamento") con el tipo y una descripción sugerida ya listos.
     */
    public function mount(Request $request): void
    {
        $tipo = $request->query('tipo');
        if (is_string($tipo) && array_key_exists($tipo, SupportRequest::TYPES)) {
            $this->type = $tipo;
        }

        $medicamento = $request->query('medicamento');
        if (is_string($medicamento) && $medicamento !== '') {
            $this->description = "Duda sobre {$medicamento}: ";
        }
    }

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        abort_unless($case, 403);

        $actor = null;
        if ($patient instanceof Patient && CaseAccess::patientCanAccess($patient, $case)) {
            $actor = $patient;
        } elseif ($caregiver instanceof Caregiver && CaseAccess::caregiverCanAccess($caregiver, $case)) {
            $actor = $caregiver;
        }

        abort_unless($actor, 403);

        $this->validate([
            'description' => 'required|string|min:5',
            'priority' => 'required|in:baja,media,alta',
            'type' => 'required|in:'.implode(',', array_keys(SupportRequest::TYPES)),
        ]);

        SupportRequest::query()->create([
            'pics_case_id' => $case->id,
            'type' => $this->type,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => 'nueva',
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->id,
        ]);

        $this->reset(['description', 'priority', 'type']);
        $this->priority = 'media';
        $this->type = 'dificultad';

        session()->flash('support_status', 'Tu solicitud fue enviada. Tu equipo la revisará.');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        $requests = $case
            ? $case->supportRequests()->latest('created_at')->get()
            : collect();

        return view('livewire.portal.support-request-component', [
            'case' => $case,
            'requests' => $requests,
        ]);
    }
}
