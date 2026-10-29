<?php

namespace App\Enums;

enum EvidenceStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Expired = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::UnderReview => 'En revisión',
            self::Verified => 'Verificada',
            self::Expired => 'Vencida',
            self::Archived => 'Archivada',
        };
    }
}
