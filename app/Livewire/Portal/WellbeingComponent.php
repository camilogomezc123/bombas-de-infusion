<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsFollowup;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WellbeingComponent extends Component
{
    /** Ventana de días desde el ingreso al programa en la que cae cada checkpoint. */
    private const CHECKPOINT_WINDOWS = [
        '48_72h' => [2, 4],
        '7d' => [5, 10],
        '30d' => [25, 40],
        '3m' => [80, 100],
        '6m' => [170, 190],
        '12m' => [350, 380],
    ];

    public ?string $checkpoint = null;

    public array $hads = [];

    public array $phq9 = [];

    public array $pcptsd = [];

    public array $ptg = [];

    public array $picsf = [];

    public ?float $fatigueScore = null;

    public ?float $painRest = null;

    public ?float $painMovement = null;

    public function mount(): void
    {
        $this->checkpoint = $this->currentCheckpoint();
    }

    /**
     * El checkpoint vigente según los días transcurridos desde el ingreso al programa.
     * Si ninguna ventana coincide (aún muy pronto, o ya pasaron todas), no hay nada que ofrecer.
     */
    private function currentCheckpoint(): ?string
    {
        $case = PortalHomeController::currentCase();
        if (! $case || ! $case->enrollment_at) {
            return null;
        }

        $days = (int) $case->enrollment_at->diffInDays(now());

        foreach (self::CHECKPOINT_WINDOWS as $checkpoint => [$from, $to]) {
            if ($days >= $from && $days <= $to) {
                return $checkpoint;
            }
        }

        return null;
    }

    private function isCaregiver(): bool
    {
        return Auth::guard('caregiver')->check();
    }

    private function existingFollowup(): ?PicsFollowup
    {
        $case = PortalHomeController::currentCase();
        if (! $case || ! $this->checkpoint) {
            return null;
        }

        return $case->followups()
            ->where('checkpoint', $this->checkpoint)
            ->where('respondent_type', $this->isCaregiver() ? 'familia' : 'paciente')
            ->first();
    }

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        abort_unless($case && $this->checkpoint, 403);

        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();
        $actor = $caregiver instanceof Caregiver ? $caregiver : $patient;
        abort_unless($actor instanceof Patient || $actor instanceof Caregiver, 403);

        $data = [
            'pics_case_id' => $case->id,
            'checkpoint' => $this->checkpoint,
            'respondent_type' => $actor instanceof Caregiver ? 'familia' : 'paciente',
            'submitted_by_type' => $actor::class,
            'submitted_by_id' => $actor->id,
            'followed_up_at' => now(),
            'contact_achieved' => true,
        ];

        if ($actor instanceof Caregiver) {
            $data['picsf_respuestas'] = array_map('intval', $this->picsf);
        } else {
            $data['hads_respuestas'] = array_map('intval', $this->hads);
            $data['phq9_respuestas'] = array_map('intval', $this->phq9);
            $data['pcptsd_respuestas'] = array_map('boolval', $this->pcptsd);
            $data['fatigue_score'] = $this->fatigueScore;
            $data['pain_rest'] = $this->painRest;
            $data['pain_movement'] = $this->painMovement;
            if (in_array($this->checkpoint, PicsFollowup::PTG_CHECKPOINTS, true)) {
                $data['ptg_respuestas'] = array_map('intval', $this->ptg);
            }
        }

        $data = PicsFollowup::computeScores($data);

        $case->followups()->updateOrCreate(
            ['checkpoint' => $this->checkpoint, 'respondent_type' => $data['respondent_type']],
            $data,
        );

        session()->flash('wellbeing_status', 'Gracias por diligenciarlo. Tu equipo lo revisará.');
        $this->dispatch('celebrate');
    }

    public function render()
    {
        return view('livewire.portal.wellbeing-component', [
            'checkpointLabel' => $this->checkpoint ? PicsFollowup::CHECKPOINTS[$this->checkpoint] : null,
            'isCaregiver' => $this->isCaregiver(),
            'showPtg' => $this->checkpoint && in_array($this->checkpoint, PicsFollowup::PTG_CHECKPOINTS, true),
            'existing' => $this->existingFollowup(),
        ]);
    }
}
