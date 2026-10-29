<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalProfileController extends Controller
{
    public function show(): View
    {
        $account = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
        $case = PortalHomeController::currentCase();
        $case?->loadMissing('patient');

        // A patient sees their authorized care circle. A caregiver sees only their
        // own authorization; this page does not grant access to other accounts.
        $authorizations = $case
            ? $case->caregiverAuthorizations()
                ->with('caregiver')
                ->whereNull('revoked_at')
                ->whereHas('caregiver', fn ($query) => $query->where('is_active', true))
                ->when($account instanceof Caregiver, fn ($query) => $query->where('caregiver_id', $account->id))
                ->orderBy('authorized_at')
                ->get()
            : collect();

        return view('portal.profile', [
            'case' => $case,
            'profilePatient' => $case?->patient ?? ($account instanceof Patient ? $account : null),
            'account' => $account,
            'authorizations' => $authorizations,
            'isPatientAccount' => $account instanceof Patient,
            'canAccessJourney' => $case && $account instanceof Caregiver
                && CaseAccess::caregiverCanAccessJourney($account, $case),
        ]);
    }
}
