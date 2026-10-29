<?php

namespace App\Services;

use App\Models\PicsCase;
use App\Models\PicsFollowup;
use Illuminate\Support\Collection;

class PicsIndicatorService
{
    /**
     * Indicadores institucionales con fuente de cálculo automática. Sin meta todavía —
     * se asigna cuando el Comité PICS apruebe la línea base (ver migración del programa).
     */
    public const GOALS = [
        'screening_positive_pct' => ['label' => 'Tamizaje positivo para PICS (cognitivo, psicológico o físico)', 'target' => null, 'op' => null],
        'followup_rate_pct' => ['label' => 'Seguimiento post-UCI iniciado', 'target' => null, 'op' => null],
        'referral_completion_pct' => ['label' => 'Remisiones completadas', 'target' => null, 'op' => null],
    ];

    /**
     * Datos completos del tablero con una sola lectura de casos.
     *
     * @return array{rows: array<int, array<string, mixed>>, current: array<string, mixed>|null, distributions: array<string, mixed>, cases: Collection}
     */
    public function dashboard(?string $year = null, ?string $month = null): array
    {
        $cases = $this->baseQuery($year)
            ->with(['patient', 'followups', 'referrals'])
            ->orderBy('month')
            ->get();
        $selectedCases = $month
            ? $cases->where('month', $month)->values()
            : $cases;

        return [
            'rows' => $cases
                ->groupBy('month')
                ->map(fn (Collection $monthCases, string $caseMonth): array => $this->calculateMonth($caseMonth, $monthCases))
                ->values()
                ->all(),
            'current' => $selectedCases->isEmpty()
                ? null
                : $this->calculateMonth($month ?? ($year ?: 'Total'), $selectedCases),
            'distributions' => $this->calculateDistributions($selectedCases),
            'cases' => $selectedCases,
        ];
    }

    /**
     * Casos válidos, con mes y no anulados (base de los indicadores).
     */
    private function baseQuery(?string $year = null)
    {
        return PicsCase::query()
            ->where('is_valid', true)
            ->where('is_cancelled', false)
            ->whereNotNull('month')
            ->where('month', '!=', '')
            ->when($year, fn ($q) => $q->where('month', 'like', "{$year}/%"));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function monthly(?string $year = null): array
    {
        return $this->baseQuery($year)
            ->with(['followups', 'referrals'])
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(fn (Collection $cases, string $month): array => $this->calculateMonth($month, $cases))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function years(): array
    {
        return $this->baseQuery()
            ->select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn (string $m): string => substr($m, 0, 4))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resumen agregado del período (un mes específico o todo el año).
     *
     * @return array<string, mixed>|null
     */
    public function summary(?string $year = null, ?string $month = null): ?array
    {
        $cases = $this->baseQuery($year)
            ->with(['followups', 'referrals'])
            ->when($month, fn ($q) => $q->where('month', $month))
            ->get();

        if ($cases->isEmpty()) {
            return null;
        }

        return $this->calculateMonth($month ?? ($year ?: 'Total'), $cases);
    }

    /**
     * @return array<string, array<int, array{label: string, value: int}>>
     */
    public function distributions(?string $year = null, ?string $month = null): array
    {
        $cases = $this->baseQuery($year)
            ->with(['followups'])
            ->when($month, fn ($q) => $q->where('month', $month))
            ->get();

        return $this->calculateDistributions($cases);
    }

    /**
     * @return array<string, array<int, array{label: string, value: int}>>
     */
    private function calculateDistributions(Collection $cases): array
    {
        $followups = $cases->flatMap->followups;

        return [
            'checkpoints' => $followups
                ->groupBy(fn (PicsFollowup $f): string => $f->checkpointLabel())
                ->map(fn (Collection $g, string $label): array => ['label' => $label, 'value' => $g->count()])
                ->values()
                ->all(),
            'enrollment_source' => $cases
                ->groupBy(fn (PicsCase $c): string => PicsCase::ENROLLMENT_SOURCES[$c->enrollment_source] ?? 'Sin dato')
                ->map(fn (Collection $g, string $label): array => ['label' => $label, 'value' => $g->count()])
                ->sortByDesc('value')
                ->values()
                ->all(),
            'risk_level' => $cases
                ->whereNotNull('risk_level')
                ->groupBy(fn (PicsCase $c): string => PicsCase::RISK_LEVELS[$c->risk_level] ?? $c->risk_level)
                ->map(fn (Collection $g, string $label): array => ['label' => $label, 'value' => $g->count()])
                ->values()
                ->all(),
        ];
    }

    private function calculateMonth(string $month, Collection $cases): array
    {
        $followups = $cases->flatMap->followups;
        $referrals = $cases->flatMap->referrals;

        $withFollowup = $cases->filter(fn (PicsCase $c): bool => $c->followups->isNotEmpty());

        $screeningAssessed = $followups->filter(fn (PicsFollowup $f): bool => $this->wasScreened($f));
        $screeningPositive = $screeningAssessed->filter(fn (PicsFollowup $f): bool => $this->isPositive($f));

        $readmissionAssessed = $followups->whereNotNull('readmission');
        $returnToWorkAssessed = $followups->whereNotNull('return_to_work');

        return [
            'month' => $month,
            'total' => $cases->count(),
            'followups_total' => $followups->count(),
            'referrals_total' => $referrals->count(),
            'followup_rate_pct' => $this->percentage($withFollowup->count(), $cases->count()),
            'contact_achieved_pct' => $this->percentage($followups->where('contact_achieved', true)->count(), $followups->count()),
            'screening_positive_pct' => $this->percentage($screeningPositive->count(), $screeningAssessed->count()),
            'referral_completion_pct' => $this->percentage($referrals->where('status', 'completed')->count(), $referrals->count()),
            'readmission_pct' => $this->percentage($readmissionAssessed->where('readmission', true)->count(), $readmissionAssessed->count()),
            'return_to_work_pct' => $this->percentage($returnToWorkAssessed->where('return_to_work', true)->count(), $returnToWorkAssessed->count()),
            // Desglose por dominio — mismos cortes que IND-30 a IND-33 en "Panel de control".
            'cognitive_impairment_pct' => $this->domainRate($followups, fn (PicsFollowup $f) => $f->isCognitionPositive()),
            'anxiety_pct' => $this->domainRate($followups, fn (PicsFollowup $f) => $f->isAnxietyPositive()),
            'depression_pct' => $this->domainRate($followups, fn (PicsFollowup $f) => $f->isDepressionPositive()),
            'ptsd_pct' => $this->domainRate($followups, fn (PicsFollowup $f) => $f->isPtsdPositive()),
            'family_distress_pct' => $this->domainRate($followups, fn (PicsFollowup $f) => $f->isFamilyDistressPositive()),
            'high_risk_pct' => $this->percentage($cases->where('risk_level', 'alto')->count(), $cases->whereNotNull('risk_level')->count()),
        ];
    }

    /**
     * % de seguimientos con un dominio evaluado que resultó positivo. El callback
     * recibe el método *_Positive() correspondiente, que ya devuelve null si el
     * instrumento no se diligenció en ese seguimiento (no cuenta como negativo).
     */
    private function domainRate(Collection $followups, \Closure $positiveCallback): ?float
    {
        $assessed = $followups->filter(fn (PicsFollowup $f) => $positiveCallback($f) !== null);
        $positive = $assessed->filter(fn (PicsFollowup $f) => $positiveCallback($f) === true);

        return $this->percentage($positive->count(), $assessed->count());
    }

    /**
     * ¿Se documentó al menos uno de los cinco dominios de tamizaje en este checkpoint?
     */
    private function wasScreened(PicsFollowup $followup): bool
    {
        return $followup->isCognitionPositive() !== null
            || $followup->isAnxietyPositive() !== null
            || $followup->isDepressionPositive() !== null
            || $followup->isPtsdPositive() !== null
            || $followup->isFamilyDistressPositive() !== null;
    }

    /**
     * ¿Algún dominio evaluado resultó positivo? Lee los puntos de corte validados de
     * cada instrumento (PicsFollowup::is*Positive()) — nunca infiere a partir de texto libre.
     */
    private function isPositive(PicsFollowup $followup): bool
    {
        return $followup->isCognitionPositive() === true
            || $followup->isAnxietyPositive() === true
            || $followup->isDepressionPositive() === true
            || $followup->isPtsdPositive() === true
            || $followup->isFamilyDistressPositive() === true;
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);
    }
}
