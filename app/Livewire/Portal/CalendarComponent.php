<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Models\PicsAgendaItem;
use App\Models\PicsCase;
use App\Notifications\AppointmentResponseNotification;
use App\Support\Posuci\CaseAccess;
use App\Support\Posuci\StaffNotifier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CalendarComponent extends Component
{
    #[Validate('required|string|min:2')]
    public string $title = '';

    #[Validate('required|date')]
    public string $remind_at = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount(): void
    {
        $this->remind_at = now()->addHour()->format('Y-m-d\TH:i');
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

    public function addReminder(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->validate();

        PersonalReminder::query()->create([
            'pics_case_id' => $case->id,
            'title' => $this->title,
            'remind_at' => $this->remind_at,
            'notes' => $this->notes ?: null,
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->id,
        ]);

        $this->reset(['title', 'notes']);
        $this->remind_at = now()->addHour()->format('Y-m-d\TH:i');

        session()->flash('calendar_status', 'Recordatorio agregado.');
        $this->dispatch('celebrate');
        $this->dispatch('calendar-refresh');
    }

    /**
     * Crea o edita un recordatorio personal desde el calendario (tocar un día vacío,
     * o tocar un recordatorio ya existente). Siempre atribuido/limitado al actor
     * autenticado — nunca se puede editar el recordatorio de otra persona.
     */
    public function saveReminder(?int $id, string $title, string $remindAt, ?string $notes): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $title = trim($title);
        abort_if($title === '' || $remindAt === '', 422);

        $payload = [
            'pics_case_id' => $case->id,
            'title' => $title,
            'remind_at' => $remindAt,
            'notes' => $notes ? trim($notes) : null,
        ];

        if ($id) {
            $this->ownReminder($case, $actor, $id)->update($payload);
            session()->flash('calendar_status', 'Recordatorio actualizado.');
        } else {
            $payload['created_by_type'] = $actor::class;
            $payload['created_by_id'] = $actor->id;
            PersonalReminder::query()->create($payload);
            session()->flash('calendar_status', 'Recordatorio agregado.');
            $this->dispatch('celebrate');
        }

        $this->dispatch('calendar-refresh');
    }

    public function deleteReminder(int $id): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->ownReminder($case, $actor, $id)->delete();

        session()->flash('calendar_status', 'Recordatorio eliminado.');
        $this->dispatch('calendar-refresh');
    }

    /** Arrastrar y soltar un recordatorio propio en el calendario para reprogramarlo. */
    public function rescheduleReminder(int $id, string $remindAt): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->ownReminder($case, $actor, $id)->update(['remind_at' => $remindAt]);

        session()->flash('calendar_status', 'Recordatorio reprogramado.');
        $this->dispatch('calendar-refresh');
    }

    private function ownReminder(PicsCase $case, Patient|Caregiver $actor, int $id): PersonalReminder
    {
        return PersonalReminder::query()
            ->where('pics_case_id', $case->id)
            ->where('created_by_type', $actor::class)
            ->where('created_by_id', $actor->id)
            ->findOrFail($id);
    }

    public function respondToAppointment(int $itemId, string $response): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);
        abort_unless(array_key_exists($response, PicsAgendaItem::RESPONSES), 422);

        $item = PicsAgendaItem::query()
            ->where('pics_case_id', $case->id)
            ->whereIn('type', PicsAgendaItem::RESPONDABLE_TYPES)
            ->findOrFail($itemId);

        $item->update([
            'patient_response' => $response,
            'patient_response_at' => now(),
            'patient_responded_by_type' => $actor::class,
            'patient_responded_by_id' => $actor->id,
        ]);

        StaffNotifier::notifyCaseStaff($case, new AppointmentResponseNotification($item));

        if ($response === 'confirmada') {
            $this->dispatch('celebrate');
        }

        $this->dispatch('calendar-refresh');
        session()->flash('calendar_status', $response === 'confirmada' ? '¡Confirmaste tu asistencia!' : 'Le avisamos a tu equipo que no podrás asistir.');
    }

    public function render()
    {
        return view('livewire.portal.calendar-component', [
            'case' => PortalHomeController::currentCase(),
        ]);
    }
}
