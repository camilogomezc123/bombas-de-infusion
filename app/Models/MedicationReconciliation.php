<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lista clínica estructurada de medicamentos conciliados al egreso: qué medicamento,
 * dosis, vía, frecuencia, y la decisión de conciliación (continúa/nuevo/suspendido/
 * ajustado). El portal la muestra en modo lectura — si el paciente/cuidador tiene una
 * duda, se reutiliza SupportRequest (tipo "duda_medicamento") en vez de agregar una
 * tercera capa de "revisado/confirmado" (esa ya existe, más general, en
 * DischargeReadinessItem).
 */
#[Fillable(['pics_case_id', 'reconciled_by', 'reconciled_at', 'notes', 'created_by', 'updated_by'])]
class MedicationReconciliation extends Model
{
    protected function casts(): array
    {
        return ['reconciled_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicationReconciliationItem::class);
    }
}
