<?php

namespace App\Enums;

enum CaseStatus: string
{
    case Imported = 'imported';
    case Hospitalized = 'hospitalized';
    case Assigned = 'assigned';
    case InReview = 'in_review';
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Imported => 'Importado',
            self::Hospitalized => 'Hospitalizado',
            self::Assigned => 'Asignado',
            self::InReview => 'En revisión',
            self::Pending => 'Pendiente de revisión',
            self::Completed => 'Finalizado',
            self::Cancelled => 'Anulado',
        };
    }

    /**
     * Estados en los que el auditor asignado todavía puede editar el caso.
     */
    public function isAuditorEditable(): bool
    {
        return in_array($this, [self::Assigned, self::InReview], true);
    }

    /**
     * Estados que representan un caso todavía abierto.
     *
     * @return array<int, self>
     */
    public static function inProcess(): array
    {
        return [
            self::Imported,
            self::Hospitalized,
            self::Assigned,
            self::InReview,
            self::Pending,
        ];
    }

    /**
     * Estados incluidos en el módulo Pendientes de análisis.
     *
     * @return array<int, self>
     */
    public static function pendingAnalysis(): array
    {
        return [
            self::Imported,
            self::Assigned,
            self::InReview,
            self::Pending,
        ];
    }
}
