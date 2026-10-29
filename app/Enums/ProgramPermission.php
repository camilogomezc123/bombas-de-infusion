<?php

namespace App\Enums;

enum ProgramPermission: string
{
    case ViewProgram = 'view_program';
    case ManageProgram = 'manage_program';
    case ViewCases = 'view_cases';
    case CreateCases = 'create_cases';
    case EditCases = 'edit_cases';
    case AuditCases = 'audit_cases';
    case ApproveCases = 'approve_cases';
    case ViewIndicators = 'view_indicators';
    case ManageStandards = 'manage_standards';
    case ManageEvidence = 'manage_evidence';
    case ManageGovernance = 'manage_governance';
    case ManageProgramUsers = 'manage_program_users';
}
