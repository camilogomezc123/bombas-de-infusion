<?php

namespace App\Filament\Tep\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ClinicalPathway extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Ruta diagnóstica';

    protected static ?string $title = 'Ruta institucional de TEP';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ruta-diagnostica';

    protected string $view = 'filament.tep.clinical-pathway';

    public function steps(): array
    {
        return [
            ['Sospecha clínica', 'Inicio', 'Registrar presentación y estabilidad', 'Urgencias'],
            ['Probabilidad pretest', 'Temprano', 'Documentar método, resultado y puntaje', 'Médico tratante'],
            ['Ruta diagnóstica', 'Según probabilidad', 'Dímero D e imagen indicados por protocolo', 'Urgencias / radiología'],
            ['Confirmación', 'Oportuna', 'Documentar estudio y resultado', 'Equipo tratante'],
            ['Categoría A-E', 'Tras confirmar', 'Registrar categoría clínica y justificación', 'Equipo tratante'],
            ['PERT', 'Categorías C-E', 'Activar o documentar razón de no activación', 'Equipo PERT'],
            ['Tratamiento', 'Sin demora evitable', 'Registrar anticoagulación y terapias avanzadas', 'Equipo tratante'],
            ['Egreso seguro', 'Antes del alta', 'Plan, educación y continuidad', 'Equipo interdisciplinario'],
            ['Seguimiento post-TEP', 'Temprano y 3 meses', 'Síntomas, adherencia, recurrencia y sangrado', 'Programa TEP'],
            ['CTEPD/CTEPH', 'Si persisten síntomas', 'Documentar evaluación y remisión', 'Especialistas'],
        ];
    }
}
