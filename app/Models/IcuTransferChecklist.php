<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'icu_stay_id', 'cognitive_status_reviewed', 'functional_status_reviewed', 'delirium_reviewed', 'pain_reviewed',
    'sedatives_opioids_reviewed', 'withdrawal_risk_reviewed', 'nutrition_reviewed', 'devices_reviewed',
    'mobility_rehab_reviewed', 'restraints_reviewed', 'sleep_reviewed', 'emotional_needs_reviewed',
    'family_engagement_reviewed', 'pics_risk_reviewed', 'medication_reconciliation', 'continuity_plan_documented',
    'warning_signs_documented', 'handoff_at', 'team_delivering', 'team_receiving', 'information_transmitted',
    'pending_items', 'risks', 'rehabilitation_plan', 'understanding_verified',
])]
class IcuTransferChecklist extends Model
{
    public const CHECKLIST_ITEMS = [
        'cognitive_status_reviewed' => 'Estado cognitivo',
        'functional_status_reviewed' => 'Estado funcional',
        'delirium_reviewed' => 'Delirium',
        'pain_reviewed' => 'Dolor',
        'sedatives_opioids_reviewed' => 'Sedantes y opioides',
        'withdrawal_risk_reviewed' => 'Riesgo de abstinencia',
        'nutrition_reviewed' => 'Nutrición',
        'devices_reviewed' => 'Dispositivos',
        'mobility_rehab_reviewed' => 'Movilidad y rehabilitación',
        'restraints_reviewed' => 'Restricciones',
        'sleep_reviewed' => 'Sueño',
        'emotional_needs_reviewed' => 'Necesidades emocionales',
        'family_engagement_reviewed' => 'Participación familiar',
        'pics_risk_reviewed' => 'Riesgo de PICS',
        'medication_reconciliation' => 'Conciliación de medicamentos',
        'continuity_plan_documented' => 'Plan de continuidad',
        'warning_signs_documented' => 'Señales de alarma',
    ];

    protected function casts(): array
    {
        return array_merge(
            array_fill_keys(array_keys(self::CHECKLIST_ITEMS), 'boolean'),
            ['understanding_verified' => 'boolean', 'handoff_at' => 'datetime'],
        );
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class, 'icu_stay_id');
    }

    public function completionPercentage(): float
    {
        $total = count(self::CHECKLIST_ITEMS);
        $done = collect(self::CHECKLIST_ITEMS)->filter(fn (string $label, string $field): bool => (bool) $this->{$field})->count();

        return round(($done / $total) * 100, 1);
    }
}
