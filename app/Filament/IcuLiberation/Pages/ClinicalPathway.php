<?php

namespace App\Filament\IcuLiberation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ClinicalPathway extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Ruta clínica';

    protected static ?string $title = 'Ruta institucional ICU Liberation';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ruta-clinica';

    protected string $view = 'filament.icu-liberation.clinical-pathway';

    public function steps(): array
    {
        return [
            ['Ingreso a UCI', 'Al ingreso', 'Registrar diagnóstico, servicio de origen y evaluación inicial', 'Equipo tratante'],
            ['Ronda ICU Liberation', 'Diaria', 'Revisar dolor, sedación, SAT/SBT, delirium, movilidad, familia, sueño y dispositivos', 'Equipo interdisciplinario'],
            ['A — Dolor', 'Continua', 'Evaluar, prevenir y tratar el dolor con escala validada', 'Enfermería / médico'],
            ['B — SAT y SBT', 'Diaria si ventilado', 'Coordinar prueba de despertar y de respiración espontánea', 'Enfermería / terapia respiratoria'],
            ['C — Analgesia y sedación', 'Continua', 'Documentar objetivo y nivel real de sedación', 'Médico / enfermería'],
            ['D — Delirium', 'Por turno', 'Evaluar con CAM-ICU/ICDSC y gestionar factores precipitantes', 'Enfermería / médico'],
            ['E — Movilidad temprana', 'Diaria', 'Evaluar seguridad y progresar el nivel de movilidad', 'Fisioterapia / enfermería'],
            ['F — Familia', 'Continua', 'Identificar cuidador, comunicar y favorecer participación', 'Equipo interdisciplinario'],
            ['Sueño y ambiente', 'Nocturna', 'Reducir interrupciones, luz y ruido innecesarios', 'Enfermería'],
            ['Ventilación y extubación', 'Cuando aplique', 'Evaluar extubación tras SBT exitoso', 'Médico / terapia respiratoria'],
            ['Traslado y egreso de UCI', 'Al egresar', 'Checklist de continuidad y entrega estructurada', 'Equipo interdisciplinario'],
            ['Recuperación post-UCI y PICS', 'Post-egreso', 'Identificar riesgo y programar seguimientos', 'Programa ICU Liberation'],
        ];
    }
}
