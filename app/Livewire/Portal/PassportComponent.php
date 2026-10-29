<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\RecoveryPassportItem;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PassportComponent extends Component
{
    public string $mobility_before = '';

    public string $autonomy_before = '';

    public string $habitual_activities = '';

    public string $supports_before = '';

    public string $current_situation = '';

    public string $home_barriers = '';

    public string $transport_barriers = '';

    public string $companion_barriers = '';

    public string $access_barriers = '';

    public string $new_item_description = '';

    public string $new_item_type = RecoveryPassportItem::TYPE_ASPIRATION;

    public function mount(): void
    {
        $passport = PortalHomeController::currentCase()?->recoveryPassport;

        if ($passport) {
            $this->mobility_before = (string) $passport->mobility_before;
            $this->autonomy_before = (string) $passport->autonomy_before;
            $this->habitual_activities = (string) $passport->habitual_activities;
            $this->supports_before = (string) $passport->supports_before;
            $this->current_situation = (string) $passport->current_situation;
            $this->home_barriers = (string) $passport->home_barriers;
            $this->transport_barriers = (string) $passport->transport_barriers;
            $this->companion_barriers = (string) $passport->companion_barriers;
            $this->access_barriers = (string) $passport->access_barriers;
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

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $case->recoveryPassport()->updateOrCreate(
            ['pics_case_id' => $case->id],
            [
                'mobility_before' => $this->mobility_before ?: null,
                'autonomy_before' => $this->autonomy_before ?: null,
                'habitual_activities' => $this->habitual_activities ?: null,
                'supports_before' => $this->supports_before ?: null,
                'current_situation' => $this->current_situation ?: null,
                'home_barriers' => $this->home_barriers ?: null,
                'transport_barriers' => $this->transport_barriers ?: null,
                'companion_barriers' => $this->companion_barriers ?: null,
                'access_barriers' => $this->access_barriers ?: null,
                'reported_by_type' => $actor::class,
                'reported_by_id' => $actor->id,
                'reported_at' => now(),
            ],
        );

        session()->flash('passport_status', 'Pasaporte guardado. Tu equipo lo revisará.');
        $this->dispatch('celebrate');
    }

    public function addItem(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->validate([
            'new_item_description' => 'required|string|min:2',
            'new_item_type' => 'required|in:'.RecoveryPassportItem::TYPE_NEED.','.RecoveryPassportItem::TYPE_ASPIRATION,
        ]);

        $passport = $case->recoveryPassport()->firstOrCreate(['pics_case_id' => $case->id]);

        $passport->items()->create([
            'type' => $this->new_item_type,
            'description' => $this->new_item_description,
            'status' => $this->new_item_type === RecoveryPassportItem::TYPE_NEED ? 'identificado' : null,
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->id,
        ]);

        $this->reset(['new_item_description']);
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();
        $passport = $case?->recoveryPassport;
        $latestFollowup = $case?->followups()->latest('followed_up_at')->first();

        return view('livewire.portal.passport-component', [
            'case' => $case,
            'passport' => $passport,
            'latestFollowup' => $latestFollowup,
            'itemTypes' => RecoveryPassportItem::TYPES,
        ]);
    }
}
