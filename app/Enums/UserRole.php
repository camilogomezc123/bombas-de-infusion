<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Leader = 'leader';
    case Auditor = 'auditor';
    case Followup = 'followup';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Leader => 'Líder de programa',
            self::Auditor => 'Auditor',
            self::Followup => 'Seguimiento',
            self::Viewer => 'Consulta',
        };
    }
}
