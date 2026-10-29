<?php

namespace App\Services;

use App\Models\TepCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TepIndicatorService
{
    public const GOALS = [
        'pretest_documented_pct' => ['label' => 'Probabilidad pretest documentada', 'target' => 90.0, 'direction' => 'gte'],
        'category_documented_pct' => ['label' => 'Categoría A-E documentada', 'target' => 90.0, 'direction' => 'gte'],
        'pert_activation_pct' => ['label' => 'Activación PERT en categorías C-E', 'target' => 90.0, 'direction' => 'gte'],
        'anticoagulation_documented_pct' => ['label' => 'Anticoagulación o contraindicación documentada', 'target' => 95.0, 'direction' => 'gte'],
        'early_followup_pct' => ['label' => 'Seguimiento temprano post-TEP', 'target' => 85.0, 'direction' => 'gte'],
        'followup_3m_pct' => ['label' => 'Seguimiento a tres meses', 'target' => 80.0, 'direction' => 'gte'],
        'hospital_mortality_pct' => ['label' => 'Mortalidad hospitalaria', 'target' => null, 'direction' => 'lte'],
        'persistent_symptoms_pct' => ['label' => 'Síntomas persistentes / evaluación CTEPD', 'target' => null, 'direction' => 'lte'],
    ];

    public function dashboard(?string $year = null): array
    {
        $cases = $this->query($year)->with(['followups', 'ctepdEvaluations'])->get();
        $confirmed = $cases->where('tep_confirmed', true);
        $pertEligible = $confirmed->whereIn('aha_category', ['C', 'D', 'E']);
        $discharged = $confirmed->whereNotNull('discharged_at');

        return [
            'cases' => $cases->count(),
            'confirmed_cases' => $confirmed->count(),
            'categories' => collect(TepCase::CATEGORIES)->mapWithKeys(fn ($label, $key) => [$key => $confirmed->where('aha_category', $key)->count()])->all(),
            'pretest_documented_pct' => $this->percentage($cases, fn (TepCase $c) => filled($c->pretest_method) && filled($c->pretest_result)),
            'category_documented_pct' => $this->percentage($confirmed, fn (TepCase $c) => filled($c->aha_category) && $c->category_documented_at && filled($c->category_justification)),
            'pert_activation_pct' => $this->percentage($pertEligible, fn (TepCase $c) => $c->pert_activated_at || filled($c->pert_not_activated_reason)),
            'anticoagulation_documented_pct' => $this->percentage($confirmed, fn (TepCase $c) => $c->anticoagulation_started_at || filled($c->anticoagulation_contraindication)),
            'median_diagnosis_anticoagulation' => $this->medianMinutes($confirmed, 'diagnosis_at', 'anticoagulation_started_at'),
            'early_followup_pct' => $this->percentage($discharged, fn (TepCase $c) => $c->followups->contains(fn ($f) => $f->contact_achieved && in_array($f->milestone, ['7d', '30d'], true))),
            'followup_3m_pct' => $this->percentage($discharged, fn (TepCase $c) => $c->followups->contains(fn ($f) => $f->contact_achieved && $f->milestone === '3m')),
            'hospital_mortality_pct' => $this->percentage($confirmed, fn (TepCase $c) => $c->death_at !== null),
            'major_bleeding_pct' => $this->percentage($confirmed->whereNotNull('major_bleeding'), fn (TepCase $c) => $c->major_bleeding),
            'recurrence_90d_pct' => $this->percentage($confirmed->whereNotNull('recurrence_90d'), fn (TepCase $c) => $c->recurrence_90d),
            'persistent_symptoms_pct' => $this->percentage($discharged, fn (TepCase $c) => $c->followups->contains('persistent_dyspnea', true) || $c->ctepdEvaluations->contains('persistent_symptoms', true)),
        ];
    }

    private function query(?string $year): Builder
    {
        return TepCase::query()->whereHas('program', fn (Builder $q) => $q->where('code', 'TEP'))
            ->where('is_valid', true)->where('is_cancelled', false)
            ->when($year, fn (Builder $q) => $q->whereYear('admission_at', $year));
    }

    private function percentage(Collection $items, callable $criterion): ?float
    {
        return $items->isEmpty() ? null : round($items->filter($criterion)->count() * 100 / $items->count(), 1);
    }

    private function medianMinutes(Collection $items, string $from, string $to): ?float
    {
        $values = $items->filter(fn (TepCase $c) => $c->{$from} && $c->{$to})
            ->map(fn (TepCase $c) => (int) $c->{$from}->diffInMinutes($c->{$to}))->sort()->values();

        return $values->isEmpty() ? null : round((float) $values->median(), 1);
    }
}
