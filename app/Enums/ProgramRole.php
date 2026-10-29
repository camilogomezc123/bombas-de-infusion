<?php

namespace App\Enums;

enum ProgramRole: string
{
    case Leader = 'leader';
    case ClinicalLeader = 'clinical_leader';
    case Auditor = 'auditor';
    case CommitteeMember = 'committee_member';
    case QualityManager = 'quality_manager';
    case Viewer = 'viewer';
    case ExecutiveDirection = 'executive_direction';
    case Coordinator = 'coordinator';
    case Physician = 'physician';
    case Nurse = 'nurse';
    case DataAnalyst = 'data_analyst';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Líder del programa',
            self::ClinicalLeader => 'Líder clínico',
            self::Auditor => 'Auditor',
            self::CommitteeMember => 'Miembro del comité',
            self::QualityManager => 'Gestor de calidad',
            self::Viewer => 'Consulta',
            self::ExecutiveDirection => 'Dirección',
            self::Coordinator => 'Coordinador del programa',
            self::Physician => 'Médico',
            self::Nurse => 'Enfermería',
            self::DataAnalyst => 'Analista de datos',
        };
    }

    public function canManageCases(): bool
    {
        return in_array($this, [self::Leader, self::ClinicalLeader, self::Coordinator], true);
    }

    public function canManageCompliance(): bool
    {
        return in_array($this, [self::Leader, self::ClinicalLeader, self::QualityManager, self::Coordinator], true);
    }

    /** @return array<int, ProgramPermission> */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Leader, self::ClinicalLeader, self::Coordinator => ProgramPermission::cases(),
            self::Auditor => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::EditCases, ProgramPermission::AuditCases,
                ProgramPermission::ViewIndicators,
            ],
            self::QualityManager => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::ViewIndicators, ProgramPermission::ManageStandards,
                ProgramPermission::ManageEvidence, ProgramPermission::ManageGovernance,
            ],
            self::CommitteeMember => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::ViewIndicators,
            ],
            self::Viewer => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::ViewIndicators,
            ],
            self::ExecutiveDirection => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::ViewIndicators, ProgramPermission::ManageGovernance,
            ],
            self::Physician, self::Nurse => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::CreateCases, ProgramPermission::EditCases,
                ProgramPermission::ViewIndicators,
            ],
            self::DataAnalyst => [
                ProgramPermission::ViewProgram, ProgramPermission::ViewCases,
                ProgramPermission::CreateCases, ProgramPermission::ViewIndicators,
            ],
        };
    }
}
