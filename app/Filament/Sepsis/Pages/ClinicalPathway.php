<?php

namespace App\Filament\Sepsis\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ClinicalPathway extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Ruta Clínica';

    protected static ?string $title = 'Ruta Clínica — Código Sepsis';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ruta-clinica';

    protected string $view = 'filament.sepsis.clinical-pathway';

    /**
     * Referencia institucional de la ruta (contenido estático, no depende de un caso).
     * Los documentos e indicadores citados son los nombres visibles ya existentes en el sistema.
     *
     * @return array<int, array<string, string>>
     */
    public function steps(): array
    {
        return [
            ['title' => 'Paciente con riesgo o deterioro', 'objective' => 'Detectar oportunamente signos de deterioro clínico.', 'responsible' => 'Personal asistencial de primer contacto', 'time' => 'Continuo', 'requirements' => 'Monitorización de signos vitales', 'evidence' => 'Registro de signos vitales', 'indicator' => '—', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Deterioro no detectado a tiempo'],
            ['title' => 'Tamizaje institucional', 'objective' => 'Identificar sospecha de infección y calcular NEWS2.', 'responsible' => 'Enfermería / Médico', 'time' => 'Al ingreso y cada reevaluación', 'requirements' => 'Escala NEWS2 disponible', 'evidence' => 'Registro de tamizaje (pestaña Tamizaje y activación)', 'indicator' => 'Tamizaje oportuno', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Caso no detectado'],
            ['title' => 'Sospecha de infección', 'objective' => 'Establecer sospecha clínica de foco infeccioso.', 'responsible' => 'Médico tratante', 'time' => 'Al tamizaje positivo', 'requirements' => 'Evaluación clínica dirigida', 'evidence' => 'Foco probable documentado', 'indicator' => '—', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Foco no identificado'],
            ['title' => 'Alerta positiva', 'objective' => 'Generar alerta cuando NEWS2 ≥ 6 o exista disfunción/hipoperfusión/deterioro significativo.', 'responsible' => 'Sistema / Enfermería', 'time' => 'Inmediato al criterio', 'requirements' => 'NEWS2 ≥ 6 o signo clínico/paraclínico de disfunción orgánica — el juicio clínico prevalece sobre la escala aislada', 'evidence' => 'Alerta registrada', 'indicator' => '—', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Fatiga de alarmas / falsos positivos'],
            ['title' => 'Activación Código Sepsis', 'objective' => 'Activar formalmente el código y fijar el tiempo cero.', 'responsible' => 'Médico tratante', 'time' => 'Tiempo cero', 'requirements' => 'Sospecha de infección + criterio de activación', 'evidence' => 'Activación documentada, tiempo cero validado', 'indicator' => 'Activación documentada del Código Sepsis', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Activación tardía o no realizada'],
            ['title' => 'Evaluación de infección y disfunción orgánica', 'objective' => 'Confirmar infección y cuantificar disfunción orgánica.', 'responsible' => 'Médico tratante', 'time' => 'Primera hora', 'requirements' => 'Lactato, paraclínicos, SOFA cuando esté disponible', 'evidence' => 'Lactato inicial, disfunciones documentadas', 'indicator' => 'Lactato oportuno', 'document' => 'Guía Antimicrobiana Institucional', 'risk' => 'Subestimación de la severidad'],
            ['title' => 'Clasificación', 'objective' => 'Clasificar como sepsis posible, probable/definida o choque séptico.', 'responsible' => 'Médico tratante', 'time' => 'Primera hora', 'requirements' => 'Criterios clínicos y paraclínicos', 'evidence' => 'Clasificación documentada en el caso', 'indicator' => '—', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Clasificación incorrecta'],
            ['title' => 'Diagnóstico inicial', 'objective' => 'Documentar el diagnóstico y foco infeccioso probable.', 'responsible' => 'Médico tratante', 'time' => 'Primera hora', 'requirements' => 'Historia clínica, examen físico, imágenes si aplica', 'evidence' => 'Foco infeccioso registrado', 'indicator' => '—', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Diagnóstico incompleto'],
            ['title' => 'Antimicrobianos', 'objective' => 'Administrar antimicrobiano empírico oportuno según clasificación.', 'responsible' => 'Médico / Enfermería / Farmacia', 'time' => '≤ 1 h en choque o sepsis probable · ≤ 3 h en sepsis posible sin choque', 'requirements' => 'Hemocultivos previos sin retrasar el antimicrobiano', 'evidence' => 'Hora de orden, dispensación y administración', 'indicator' => 'Antimicrobiano oportuno', 'document' => 'Guía Antimicrobiana Institucional', 'risk' => 'Antimicrobiano tardío'],
            ['title' => 'Reanimación', 'objective' => 'Optimizar perfusión con líquidos y vasopresor si se requiere.', 'responsible' => 'Médico / Enfermería', 'time' => 'Primera a tercera hora', 'requirements' => 'Evaluación de respuesta a volumen', 'evidence' => 'Líquidos y vasopresores documentados', 'indicator' => 'Meta de PAM en 3 horas', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Sobrecarga de líquidos'],
            ['title' => 'Control de la fuente', 'objective' => 'Definir y ejecutar el control del foco infeccioso.', 'responsible' => 'Médico tratante / Especialidad interconsultada', 'time' => 'Lo antes posible, meta institucional definida', 'requirements' => 'Valoración por especialidad si aplica', 'evidence' => 'Decisión y procedimiento documentados', 'indicator' => 'Control oportuno de la fuente', 'document' => 'Ruta de Control de la Fuente', 'risk' => 'Control tardío de la fuente'],
            ['title' => 'Definición del nivel de atención', 'objective' => 'Definir si requiere UCI, UTMO u otro nivel.', 'responsible' => 'Médico tratante / Intensivista', 'time' => 'Tres a seis horas', 'requirements' => 'Reevaluación clínica', 'evidence' => 'Destino documentado', 'indicator' => 'Ingreso no planeado a UCI', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Traslado no planeado a UCI'],
            ['title' => 'Reevaluación', 'objective' => 'Reevaluar respuesta clínica y metas de reanimación.', 'responsible' => 'Médico / Enfermería', 'time' => '3 h, 6 h, 24 h', 'requirements' => 'Reevaluación hemodinámica documentada', 'evidence' => 'Reevaluaciones registradas', 'indicator' => 'Reevaluación hemodinámica documentada', 'document' => 'Protocolo Institucional Código Sepsis en Adultos', 'risk' => 'Falta de reevaluación'],
            ['title' => 'Desescalamiento terapéutico', 'objective' => 'Ajustar o suspender antimicrobianos según microbiología.', 'responsible' => 'Médico tratante / Infectología', 'time' => '48-72 horas', 'requirements' => 'Resultado de cultivos', 'evidence' => 'Ajuste documentado', 'indicator' => 'Revisión antimicrobiana a las 48-72 horas', 'document' => 'Guía Antimicrobiana Institucional', 'risk' => 'Uso prolongado innecesario de antimicrobianos'],
            ['title' => 'Egreso', 'objective' => 'Preparar el egreso con educación y conciliación de medicamentos.', 'responsible' => 'Médico / Enfermería', 'time' => 'Al alta', 'requirements' => 'Checklist de egreso completo', 'evidence' => 'Educación al paciente y la familia registrada', 'indicator' => 'Conciliación de medicamentos al egreso', 'document' => 'Ruta de Recuperación Postsepsis', 'risk' => 'Egreso sin preparación adecuada'],
            ['title' => 'Recuperación postsepsis', 'objective' => 'Evaluar estado funcional, cognitivo y emocional posterior al egreso.', 'responsible' => 'Coordinador del programa / Enfermería', 'time' => '48-72 h, 7 d, 15 d, 30 d, 90 d', 'requirements' => 'Contacto con el paciente o cuidador', 'evidence' => 'Seguimientos postsepsis registrados', 'indicator' => 'Seguimiento postsepsis programado', 'document' => 'Ruta de Recuperación Postsepsis', 'risk' => 'Pérdida de seguimiento'],
            ['title' => 'Seguimiento y prevención de recurrencia', 'objective' => 'Identificar riesgo de recurrencia y necesidad de intervención.', 'responsible' => 'Coordinador del programa', 'time' => 'Continuo tras el egreso', 'requirements' => 'Análisis de seguimientos previos', 'evidence' => 'Riesgo de recurrencia documentado', 'indicator' => 'Recurrencia de retrasos', 'document' => 'Ruta de Recuperación Postsepsis', 'risk' => 'Reingreso o reconsulta no anticipados'],
        ];
    }

    /**
     * @return array<int, array{label: string, description: string}>
     */
    public function timeline(): array
    {
        return [
            ['label' => 'Tiempo cero', 'description' => 'Activación del Código Sepsis'],
            ['label' => 'Primera hora', 'description' => 'Lactato, hemocultivos, antimicrobiano, reanimación inicial'],
            ['label' => 'Tres horas', 'description' => 'Reevaluación, control del foco, nivel de atención'],
            ['label' => 'Seis horas', 'description' => 'Metas de reanimación, ajuste antimicrobiano'],
            ['label' => '24 horas', 'description' => 'Reevaluación integral'],
            ['label' => '48-72 horas', 'description' => 'Desescalamiento, revisión microbiológica'],
            ['label' => 'Egreso', 'description' => 'Checklist de egreso, educación'],
            ['label' => 'Seguimiento 7-15-30-90 días', 'description' => 'Recuperación postsepsis'],
        ];
    }
}
