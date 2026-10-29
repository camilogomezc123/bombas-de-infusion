<?php

namespace App\Support\Posuci;

use App\Models\Caregiver;
use App\Models\CaregiverAuthorization;
use App\Models\Patient;
use App\Models\PicsCase;

/**
 * Control de acceso del portal paciente/familia. Nunca confía en lo que llega del
 * formulario o de la URL — siempre vuelve a consultar la autorización vigente en base
 * de datos. Análogo a App\Support\ProgramAccess, pero para los tres tipos de actor del
 * portal (paciente, cuidador, y — vía el guard "web" ya existente — el profesional).
 */
class CaseAccess
{
    public static function patientCanAccess(Patient $patient, PicsCase $case): bool
    {
        return $case->patient_id === $patient->id;
    }

    public static function caregiverAuthorization(Caregiver $caregiver, PicsCase $case): ?CaregiverAuthorization
    {
        return CaregiverAuthorization::query()
            ->where('pics_case_id', $case->id)
            ->where('caregiver_id', $caregiver->id)
            ->whereNull('revoked_at')
            ->first();
    }

    public static function caregiverCanAccess(Caregiver $caregiver, PicsCase $case): bool
    {
        return self::caregiverAuthorization($caregiver, $case) !== null;
    }

    public static function caregiverCanWriteDiary(Caregiver $caregiver, PicsCase $case): bool
    {
        return (bool) self::caregiverAuthorization($caregiver, $case)?->can_write_diary;
    }

    public static function caregiverCanAccessJourney(Caregiver $caregiver, PicsCase $case): bool
    {
        return (bool) self::caregiverAuthorization($caregiver, $case)?->can_access_journey;
    }

    /**
     * Caso activo del paciente autenticado en el guard "patient". Null si no existe
     * o si el usuario autenticado no es realmente el dueño (defensa en profundidad).
     */
    public static function currentCaseForPatient(Patient $patient): ?PicsCase
    {
        return PicsCase::query()->where('patient_id', $patient->id)->latest('id')->first();
    }

    /**
     * Caso del cuidador autenticado, únicamente si tiene autorización vigente sobre él.
     */
    public static function currentCaseForCaregiver(Caregiver $caregiver): ?PicsCase
    {
        $authorization = CaregiverAuthorization::query()
            ->where('caregiver_id', $caregiver->id)
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();

        return $authorization?->case;
    }
}
