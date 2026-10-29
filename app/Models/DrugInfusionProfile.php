<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'aliases', 'category', 'dose_unit', 'vis_component', 'vis_weight', 'vis_rate_unit',
    'max_dose_soft', 'max_dose_hard', 'dose_limit_unit', 'max_cumulative_dose_per_kg_per_day',
    'pis_risk_applicable', 'pis_dose_threshold', 'pis_duration_threshold_minutes', 'is_active',
    'continuous_infusion', 'max_interruption_minutes',
])]
class DrugInfusionProfile extends Model
{
    public const CATEGORIES = [
        'vasoactive' => 'Vasoactivo',
        'sedation' => 'Sedación',
        'analgesia' => 'Analgesia',
        'antiemetic' => 'Antiemético',
        'antibiotic' => 'Antibiótico (infusión continua)',
        'other' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'vis_component' => 'boolean',
            'vis_weight' => 'decimal:4',
            'max_dose_soft' => 'decimal:4',
            'max_dose_hard' => 'decimal:4',
            'max_cumulative_dose_per_kg_per_day' => 'decimal:4',
            'pis_risk_applicable' => 'boolean',
            'pis_dose_threshold' => 'decimal:4',
            'is_active' => 'boolean',
            'continuous_infusion' => 'boolean',
            'max_interruption_minutes' => 'integer',
        ];
    }

    public function infusionEvents(): HasMany
    {
        return $this->hasMany(InfusionEvent::class);
    }

    /**
     * Busca el perfil cuyo nombre o alias coincide (sin distinguir mayúsculas/acentos
     * simples) con el nombre crudo que reporta la bomba.
     */
    public static function matchByRawName(string $rawName): ?self
    {
        $needle = mb_strtolower(trim($rawName));

        return self::query()->where('is_active', true)->get()->first(function (self $profile) use ($needle) {
            if (mb_strtolower($profile->name) === $needle) {
                return true;
            }

            return collect($profile->aliases ?? [])->contains(fn (string $alias) => mb_strtolower($alias) === $needle);
        });
    }
}
