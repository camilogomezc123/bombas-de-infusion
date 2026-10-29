<?php

namespace App\Filament\Infarto\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ClinicalPathway extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Ruta clínica';

    protected static ?string $title = 'Ruta clínica de síndrome coronario agudo';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ruta-clinica';

    protected string $view = 'filament.infarto.clinical-pathway';

    public function steps(): array
    {
        return [
            ['title' => 'Síntomas compatibles', 'time' => 'Inicio', 'objective' => 'Reconocer síntomas típicos y atípicos', 'responsible' => 'Paciente / primer contacto', 'evidence' => 'Inicio de síntomas documentado', 'risk' => 'Demora del paciente'],
            ['title' => 'Primer contacto médico', 'time' => 'FMC', 'objective' => 'Iniciar evaluación institucional', 'responsible' => 'Equipo prehospitalario / urgencias', 'evidence' => 'FMC documentado', 'risk' => 'Demora prehospitalaria'],
            ['title' => 'ECG', 'time' => '≤10 minutos', 'objective' => 'Realizar e interpretar ECG oportunamente', 'responsible' => 'Urgencias / cardiología', 'evidence' => 'Hora de realización e interpretación', 'risk' => 'ECG tardío'],
            ['title' => 'Clasificación inicial', 'time' => 'Inmediata', 'objective' => 'Diferenciar STEMI y NSTE-ACS', 'responsible' => 'Médico tratante', 'evidence' => 'Clasificación documentada', 'risk' => 'Clasificación incorrecta'],
            ['title' => 'Ruta STEMI', 'time' => '≤90/120 minutos', 'objective' => 'Lograr reperfusión oportuna', 'responsible' => 'Código Infarto / hemodinamia', 'evidence' => 'FMC-primer dispositivo', 'risk' => 'Reperfusión tardía'],
            ['title' => 'Ruta NSTE-ACS', 'time' => 'Según riesgo', 'objective' => 'Definir estrategia invasiva', 'responsible' => 'Cardiología', 'evidence' => 'Riesgo y estrategia documentados', 'risk' => 'Angiografía no oportuna'],
            ['title' => 'Hospitalización', 'time' => 'Continuo', 'objective' => 'Vigilar respuesta y complicaciones', 'responsible' => 'UCI / hospitalización', 'evidence' => 'Evolución y complicaciones', 'risk' => 'Deterioro no reconocido'],
            ['title' => 'Egreso seguro', 'time' => 'Antes del alta', 'objective' => 'Completar prevención secundaria', 'responsible' => 'Equipo interdisciplinario', 'evidence' => 'Checklist de egreso', 'risk' => 'Plan incompleto'],
            ['title' => 'Rehabilitación', 'time' => 'Posterior al egreso', 'objective' => 'Asegurar remisión e inicio efectivo', 'responsible' => 'Rehabilitación cardiaca', 'evidence' => 'Remisión e inicio', 'risk' => 'Pérdida de seguimiento'],
            ['title' => 'Seguimiento', 'time' => '30 días–12 meses', 'objective' => 'Medir resultados y recurrencia', 'responsible' => 'Programa SCA', 'evidence' => 'Seguimientos', 'risk' => 'Reingreso no detectado'],
        ];
    }
}
