<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\DiaryEntry;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DiaryComponent extends Component
{
    #[Validate('required|date')]
    public string $entry_date = '';

    #[Validate('required|string|min:5')]
    public string $content = '';

    #[Validate('nullable|string')]
    public string $message_to_patient = '';

    #[Validate('nullable|string')]
    public string $meaningful_memory = '';

    public bool $visible_to_patient = true;

    public function mount(): void
    {
        $this->entry_date = now()->toDateString();
    }

    /**
     * El paciente siempre puede escribir en su propio diario; el cuidador solo si
     * tiene la autorización can_write_diary vigente para este caso.
     */
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

        if ($caregiver instanceof Caregiver && CaseAccess::caregiverCanWriteDiary($caregiver, $case)) {
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

        $isPatientAuthor = $actor instanceof Patient;

        DiaryEntry::query()->create([
            'pics_case_id' => $case->id,
            'authorable_type' => $actor::class,
            'authorable_id' => $actor->id,
            'entry_date' => $this->entry_date,
            'content' => $this->content,
            // "Mensaje para el paciente" no aplica cuando el propio paciente escribe.
            'message_to_patient' => $isPatientAuthor ? null : ($this->message_to_patient ?: null),
            'meaningful_memory' => $this->meaningful_memory ?: null,
            'is_draft' => false,
            // El paciente siempre ve lo que él mismo escribió.
            'visible_to_patient' => $isPatientAuthor ? true : $this->visible_to_patient,
        ]);

        $this->reset(['content', 'message_to_patient', 'meaningful_memory']);
        $this->entry_date = now()->toDateString();

        session()->flash('diary_status', 'Entrada guardada.');
        $this->dispatch('celebrate');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();
        $patient = Auth::guard('patient')->user();

        $entries = collect();

        if ($case) {
            $query = $case->diaryEntries()->where('is_draft', false)->orderByDesc('entry_date');

            if ($patient instanceof Patient) {
                $query->where('visible_to_patient', true);
            }

            $entries = $query->with('authorable')->get();
        }

        return view('livewire.portal.diary-component', [
            'case' => $case,
            'entries' => $entries,
            'canWrite' => $this->actor() !== null,
            'isPatient' => $patient instanceof Patient,
        ]);
    }
}
