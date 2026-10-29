<?php

namespace App\Services;

use App\Models\AcsCase;
use Illuminate\Support\Collection;

class AcsDataQualityService
{
    /**
     * @return array{percentage:float,missing:array<int,string>,issues:array<int,string>,indicator_notes:array<int,string>}
     */
    public function assess(AcsCase $case): array
    {
        $required = [
            'acs_type' => 'Tipo de SCA',
            'admission_at' => 'Fecha de ingreso',
            'first_medical_contact_at' => 'Primer contacto médico',
            'ecg_interpreted_at' => 'ECG interpretado',
            'diagnosis_at' => 'Fecha del diagnóstico',
            'outcome' => 'Desenlace',
        ];

        if ($case->acs_type === 'stemi') {
            $required['reperfusion_strategy'] = 'Estrategia de reperfusión';
            if ($case->reperfusion_strategy === 'primary_pci') {
                $required['first_device_at'] = 'Primer dispositivo';
            }
            if ($case->reperfusion_strategy === 'none') {
                $required['no_reperfusion_reason'] = 'Justificación de no reperfusión';
            }
        }

        if (in_array($case->acs_type, ['nstemi', 'nste_acs', 'unstable_angina'], true)) {
            $required['nste_strategy'] = 'Estrategia NSTE-ACS';
            $required['grace_score'] = 'Puntaje GRACE';
            $required['grace_risk_category'] = 'Categoría GRACE';
            $required['grace_assessed_at'] = 'Fecha de GRACE';
        }

        $missing = collect($required)
            ->filter(fn (string $label, string $field): bool => blank($case->{$field}))
            ->values()->all();

        $issues = $this->chronologyIssues($case);
        $complete = count($required) - count($missing);
        $percentage = count($required) ? round($complete * 100 / count($required), 1) : 100.0;

        return [
            'percentage' => $percentage,
            'missing' => $missing,
            'issues' => $issues,
            'indicator_notes' => $this->indicatorNotes($case),
        ];
    }

    /**
     * @return Collection<int, array{case:AcsCase,percentage:float,missing:array,issues:array,indicator_notes:array}>
     */
    public function report(int $limit = 200): Collection
    {
        return AcsCase::query()
            ->whereHas('program', fn ($query) => $query->where('code', 'INFARTO'))
            ->with(['patient', 'site'])
            ->latest('admission_at')->limit($limit)->get()
            ->map(fn (AcsCase $case): array => ['case' => $case, ...$this->assess($case)]);
    }

    /** @return array<int, string> */
    private function chronologyIssues(AcsCase $case): array
    {
        $issues = [];
        foreach ([
            ['symptom_onset_at', 'first_medical_contact_at', 'El FMC ocurre antes del inicio de síntomas'],
            ['first_medical_contact_at', 'ecg_interpreted_at', 'El ECG interpretado ocurre antes del FMC'],
            ['ecg_performed_at', 'ecg_interpreted_at', 'La interpretación del ECG ocurre antes de realizarlo'],
            ['diagnosis_at', 'first_device_at', 'El primer dispositivo ocurre antes del diagnóstico'],
            ['admission_at', 'discharged_at', 'El egreso ocurre antes del ingreso'],
        ] as [$from, $to, $message]) {
            if ($case->{$from} && $case->{$to} && $case->{$to}->lt($case->{$from})) {
                $issues[] = $message;
            }
        }

        return $issues;
    }

    /** @return array<int, string> */
    private function indicatorNotes(AcsCase $case): array
    {
        $notes = [];
        if (! $case->is_valid || $case->is_cancelled) {
            $notes[] = 'Excluido de indicadores por validez/cancelación.';
        }
        if ($case->acs_type === 'stemi' && (! $case->first_medical_contact_at || ! $case->first_device_at)) {
            $notes[] = 'No entra al indicador FMC-dispositivo: faltan tiempos.';
        }
        if (in_array($case->acs_type, ['nstemi', 'nste_acs', 'unstable_angina'], true)
            && blank($case->grace_risk_category)) {
            $notes[] = 'No entra al análisis GRACE por categoría no documentada.';
        }

        return $notes;
    }
}
