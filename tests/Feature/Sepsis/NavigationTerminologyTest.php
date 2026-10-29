<?php

namespace Tests\Feature\Sepsis;

use App\Filament\Sepsis\Resources\ComplianceEvidence\ComplianceEvidenceResource;
use App\Filament\Sepsis\Resources\ClinicalPrograms\ClinicalProgramResource;
use App\Filament\Sepsis\Resources\ProgramCommittees\ProgramCommitteeResource;
use App\Filament\Sepsis\Resources\ProgramMemberships\ProgramMembershipResource;
use App\Filament\Sepsis\Resources\QualityStandards\QualityStandardResource;
use Tests\TestCase;

class NavigationTerminologyTest extends TestCase
{
    public function test_new_navigation_uses_institutional_visible_names(): void
    {
        $labels = [
            QualityStandardResource::getNavigationGroup(),
            QualityStandardResource::getNavigationLabel(),
            QualityStandardResource::getPluralModelLabel(),
            ComplianceEvidenceResource::getNavigationGroup(),
            ComplianceEvidenceResource::getNavigationLabel(),
            ComplianceEvidenceResource::getPluralModelLabel(),
            ProgramMembershipResource::getNavigationLabel(),
            ProgramMembershipResource::getPluralModelLabel(),
        ];

        foreach ($labels as $label) {
            $this->assertDoesNotMatchRegularExpression('/\bjci\b/i', (string) $label);
        }

        $this->assertSame('Programa', ClinicalProgramResource::getNavigationGroup());
        $this->assertSame('Información general', ClinicalProgramResource::getNavigationLabel());
        $this->assertSame('Equipo y competencias', ProgramMembershipResource::getNavigationLabel());
        $this->assertSame('Comité', ProgramCommitteeResource::getNavigationLabel());
        $this->assertSame('Calidad y mejora', QualityStandardResource::getNavigationGroup());
        $this->assertSame('Calidad y mejora', ComplianceEvidenceResource::getNavigationGroup());
    }
}
