<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Instrumentos de tamizaje portados tal cual del programa PICS ya validado por el
 * usuario en "Panel de control" (app/Models/PicsEvaluacion.php y
 * app/Http/Controllers/PicsController.php de ese proyecto): mismas preguntas, mismas
 * fórmulas de puntaje y mismos puntos de corte. AMT/Pfeiffer y MoCA requieren un
 * evaluador entrenado (calificar respuesta correcta/incorrecta) — nunca se ofrecen en
 * el portal de autoservicio del paciente. PHQ-9, HADS-A, PC-PTSD-5 y PTG-SF sí son
 * autoadministrables; PICS-F es para el cuidador.
 */
#[Fillable([
    'pics_case_id', 'checkpoint', 'respondent_type', 'contact_method', 'contact_achieved',
    'functional_capacity', 'mobility', 'strength', 'sleep_quality',
    'disfagia',
    'amt_respuestas', 'amt_score', 'moca_respuestas', 'moca_total',
    'hads_respuestas', 'hads_ansiedad',
    'phq9_respuestas', 'phq9_score',
    'pcptsd_respuestas', 'pcptsd_score',
    'ptg_respuestas', 'ptg_score',
    'picsf_respuestas', 'picsf_distress',
    'fatigue_score', 'pain_rest', 'pain_movement',
    'medications_review', 'readmission', 'return_to_work', 'referred_to', 'notes',
    'followed_up_at', 'responsible_user_id',
    'submitted_by_type', 'submitted_by_id', 'confirmed_by', 'confirmed_at',
])]
class PicsFollowup extends Model
{
    public const CHECKPOINTS = [
        '48_72h' => '48-72 horas post-egreso',
        '7d' => '7 días',
        '30d' => '30 días',
        '3m' => '3 meses',
        '6m' => '6 meses',
        '12m' => '12 meses',
    ];

    public const RESPONDENT_TYPES = [
        'paciente' => 'Paciente',
        'familia' => 'Familia / cuidador',
    ];

    public const DISFAGIA_OPTIONS = [
        'pasa' => 'Pasa el tamizaje',
        'falla' => 'Falla el tamizaje',
        'no_aplica' => 'No aplica',
        'pendiente' => 'Pendiente',
    ];

    /** Checkpoints en los que aplica el Cuestionario de Crecimiento Postraumático. */
    public const PTG_CHECKPOINTS = ['3m', '6m', '12m'];

    // ── Pfeiffer SPMSQ / AMT — el puntaje son los ERRORES (0-10, menor es mejor) ──
    public const AMT_ITEMS = [
        '¿Cuál es la fecha de hoy? (día, mes y año)',
        '¿Qué día de la semana es hoy?',
        '¿Cuál es el nombre de este lugar o institución?',
        '¿Cuál es su número de teléfono? (Si no tiene: ¿cuál es su dirección completa?)',
        '¿Cuántos años tiene usted?',
        '¿Cuándo nació usted? (día, mes y año)',
        '¿Quién es el Presidente actualmente?',
        '¿Quién fue el Presidente anterior?',
        '¿Cuál es el primer apellido de su madre?',
        'Reste de 3 en 3 empezando desde 20 (20 → 17 → 14 → 11 → 8…)',
    ];

    public const MOCA_DOMAINS = [
        'visuoespacial' => 'Visuoespacial / ejecutiva',
        'nomenclatura' => 'Nomenclatura',
        'atencion' => 'Atención',
        'lenguaje' => 'Lenguaje',
        'abstraccion' => 'Abstracción',
        'recuerdo' => 'Recuerdo diferido',
        'orientacion' => 'Orientación',
    ];

    /** HADS-A: 7 ítems de ansiedad, cada uno [pregunta, opciones en orden 0-3]. */
    public const HADS_ITEMS = [
        ['Me siento tenso(a) o nervioso(a)', ['Nunca', 'A veces', 'Muchas veces', 'Casi siempre']],
        ['Siento una especie de temor como si algo malo fuera a suceder', ['No siento nada de eso', 'Sí, pero no me preocupa mucho', 'Sí, pero no muy intenso', 'Sí, y muy intenso']],
        ['Tengo la cabeza llena de preocupaciones', ['Solo ocasionalmente', 'A veces pero no muy a menudo', 'Muchas veces', 'Casi todo el día']],
        ['Puedo estar sentado tranquilamente y sentirme relajado(a)', ['Siempre', 'Generalmente', 'Pocas veces', 'Nunca']],
        ['Siento una especie de miedo como si tuviera "mariposas" en el estómago', ['Nunca', 'A veces', 'Con bastante frecuencia', 'Muy a menudo']],
        ['Me siento inquieto(a) como si tuviera que estar en movimiento', ['Nada en absoluto', 'No mucho', 'Bastante', 'Mucho']],
        ['Experimento de repente sensaciones de gran angustia o temor', ['Nunca', 'Pocas veces', 'Con bastante frecuencia', 'Muy a menudo']],
    ];

    /** PHQ-9: 9 ítems, 0=Nunca · 1=Varios días · 2=La mitad de días o más · 3=Casi todos los días. */
    public const PHQ9_ITEMS = [
        'Poco interés o placer en hacer las cosas',
        'Sentirse desanimado(a), deprimido(a) o sin esperanza',
        'Problemas para dormir, mantenerse dormido(a), o dormir demasiado',
        'Sentirse cansado(a) o con poca energía',
        'Tener poco apetito o comer en exceso',
        'Sentirse mal consigo mismo(a), o que es un fracaso, o que ha fallado a su familia',
        'Dificultad para concentrarse en leer, ver televisión u otras actividades',
        'Moverse o hablar tan despacio que otros lo han notado; o lo contrario, estar tan inquieto(a) que se ha movido más de lo usual',
        'Pensar que estaría mejor muerto(a) o en hacerse daño de alguna manera',
    ];

    public const PCPTSD_ITEMS = [
        'Ha tenido pesadillas o pensamientos intrusivos sobre su experiencia en la UCI cuando no quería',
        'Ha intentado evitar pensar en lo que vivió en la UCI o ha evitado situaciones que se lo recuerdan',
        'Se ha sentido constantemente en alerta, vigilante o se asusta fácilmente',
        'Se ha sentido emocionalmente entumecido(a) o desconectado(a) de las demás personas',
        'Se ha sentido culpable o incapaz de dejar de culparse por lo que vivió en la UCI',
    ];

    public const PTG_ITEMS = [
        'He cambiado mis prioridades sobre lo que es importante en la vida',
        'Tengo una mayor apreciación por el valor de mi propia vida',
        'He desarrollado nuevos intereses o actividades',
        'Siento que puedo confiar más en mí mismo(a) para manejar las dificultades',
        'Tengo una mejor comprensión de las cuestiones espirituales o de vida',
        'Sé que puedo contar con las personas en momentos de crisis',
        'He establecido un nuevo camino o propósito para mi vida',
        'Tengo un mayor sentido de compasión hacia los demás',
        'Sé que soy capaz de manejar las dificultades mejor de lo que pensaba',
        'Hago mejor uso de mis energías y tiempo',
    ];

    /** PICS-F (cuidador): 5 ítems, [pregunta, opciones 0-4]. */
    public const PICSF_ITEMS = [
        ['Ha tenido pesadillas o recuerdos intrusivos sobre la estancia en UCI de su familiar', ['Nunca', 'Pocas veces', 'Frecuentemente', 'Casi siempre', 'Siempre']],
        ['Se ha sentido ansioso(a) o nervioso(a) con frecuencia', ['Nunca', 'Pocas veces', 'Frecuentemente', 'Casi siempre', 'Siempre']],
        ['Ha tenido dificultad para concentrarse en sus actividades diarias', ['Nunca', 'Pocas veces', 'Frecuentemente', 'Casi siempre', 'Siempre']],
        ['Ha tenido problemas para dormir relacionados con la situación de su familiar', ['Nunca', 'Pocas veces', 'Frecuentemente', 'Casi siempre', 'Siempre']],
        ['Ha sentido culpa relacionada con la enfermedad o la atención de su familiar', ['Nunca', 'Pocas veces', 'Frecuentemente', 'Casi siempre', 'Siempre']],
    ];

    protected function casts(): array
    {
        return [
            'contact_achieved' => 'boolean',
            'amt_respuestas' => 'array',
            'moca_respuestas' => 'array',
            'hads_respuestas' => 'array',
            'phq9_respuestas' => 'array',
            'pcptsd_respuestas' => 'array',
            'ptg_respuestas' => 'array',
            'picsf_respuestas' => 'array',
            'readmission' => 'boolean',
            'return_to_work' => 'boolean',
            'referred_to' => 'array',
            'followed_up_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PicsReferral::class);
    }

    public function submittedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function checkpointLabel(): string
    {
        return self::CHECKPOINTS[$this->checkpoint] ?? $this->checkpoint;
    }

    public function isSelfSubmitted(): bool
    {
        return $this->submitted_by_type !== null;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    // ── Semáforos por dominio (verde/amarillo/rojo/sin_dato) ──────────────────
    // Puntos de corte idénticos a los ya validados en "Panel de control".

    public function semaforoAmt(): string
    {
        if ($this->amt_score === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->amt_score <= 2 => 'verde',
            $this->amt_score <= 4 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoAnsiedad(): string
    {
        if ($this->hads_ansiedad === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->hads_ansiedad <= 7 => 'verde',
            $this->hads_ansiedad <= 10 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoDepresion(): string
    {
        if ($this->phq9_score === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->phq9_score <= 4 => 'verde',
            $this->phq9_score <= 9 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoPtsd(): string
    {
        if ($this->pcptsd_score === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->pcptsd_score <= 1 => 'verde',
            $this->pcptsd_score <= 2 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoFatiga(): string
    {
        if ($this->fatigue_score === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->fatigue_score <= 3 => 'verde',
            $this->fatigue_score <= 6 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoDolor(): string
    {
        if ($this->pain_rest === null && $this->pain_movement === null) {
            return 'sin_dato';
        }

        $max = max((float) ($this->pain_rest ?? 0), (float) ($this->pain_movement ?? 0));

        return match (true) {
            $max <= 3 => 'verde',
            $max <= 6 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoPtg(): string
    {
        if ($this->ptg_score === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->ptg_score >= 30 => 'verde',
            $this->ptg_score >= 15 => 'amarillo',
            default => 'rojo',
        };
    }

    public function semaforoPicsf(): string
    {
        if ($this->picsf_distress === null) {
            return 'sin_dato';
        }

        return match (true) {
            $this->picsf_distress <= 8 => 'verde',
            $this->picsf_distress <= 11 => 'amarillo',
            default => 'rojo',
        };
    }

    /** Semáforo global: el peor de todos los dominios evaluados. */
    public function semaforoGlobal(): string
    {
        $todos = [
            $this->semaforoAmt(), $this->semaforoAnsiedad(), $this->semaforoDepresion(),
            $this->semaforoPtsd(), $this->semaforoFatiga(), $this->semaforoDolor(),
        ];

        return match (true) {
            in_array('rojo', $todos, true) => 'rojo',
            in_array('amarillo', $todos, true) => 'amarillo',
            in_array('verde', $todos, true) => 'verde',
            default => 'sin_dato',
        };
    }

    // ── Banderas de tamizaje positivo — SIEMPRE calculadas, nunca diligenciadas
    // a mano. Mismos cortes que usan los indicadores IND-30 a IND-33 del programa. ──

    public function isCognitionPositive(): ?bool
    {
        return $this->amt_score === null ? null : $this->amt_score >= 3;
    }

    public function isAnxietyPositive(): ?bool
    {
        return $this->hads_ansiedad === null ? null : $this->hads_ansiedad >= 8;
    }

    public function isDepressionPositive(): ?bool
    {
        return $this->phq9_score === null ? null : $this->phq9_score >= 10;
    }

    public function isPtsdPositive(): ?bool
    {
        return $this->pcptsd_score === null ? null : $this->pcptsd_score >= 3;
    }

    public function isFamilyDistressPositive(): ?bool
    {
        return $this->picsf_distress === null ? null : $this->picsf_distress >= 12;
    }

    /** ¿Aplica administrar el MoCA? (Pfeiffer/AMT con 3 o más errores). */
    public function requiresMoca(): bool
    {
        return $this->amt_score !== null && $this->amt_score >= 3;
    }

    /**
     * Calcula *_score/*_total a partir de las respuestas crudas de cada instrumento.
     * Misma fórmula que PicsController::store() en "Panel de control": el puntaje
     * nunca se diligencia a mano, siempre se deriva de las respuestas guardadas.
     * Se usa tanto desde el formulario del profesional como desde el autorreporte
     * del portal, para no duplicar la lógica de calificación.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function computeScores(array $data): array
    {
        if (isset($data['amt_respuestas']) && count($data['amt_respuestas']) === count(self::AMT_ITEMS)) {
            $correct = array_map('boolval', $data['amt_respuestas']);
            $data['amt_score'] = count(self::AMT_ITEMS) - array_sum($correct);
        }

        $mocaResp = $data['moca_respuestas'] ?? null;
        $hasAllMoca = is_array($mocaResp)
            && ($data['amt_score'] ?? null) !== null
            && $data['amt_score'] >= 3
            && count(array_filter($mocaResp, fn ($v) => $v !== null && $v !== '')) === count(self::MOCA_DOMAINS);

        if ($hasAllMoca) {
            $data['moca_total'] = array_sum(array_map('intval', $mocaResp));
        } else {
            $data['moca_respuestas'] = null;
            $data['moca_total'] = null;
        }

        if (isset($data['hads_respuestas']) && count($data['hads_respuestas']) === count(self::HADS_ITEMS)) {
            $data['hads_ansiedad'] = array_sum(array_map('intval', $data['hads_respuestas']));
        }

        if (isset($data['phq9_respuestas']) && count($data['phq9_respuestas']) === count(self::PHQ9_ITEMS)) {
            $data['phq9_score'] = array_sum(array_map('intval', $data['phq9_respuestas']));
        }

        if (isset($data['pcptsd_respuestas']) && count($data['pcptsd_respuestas']) === count(self::PCPTSD_ITEMS)) {
            $data['pcptsd_score'] = array_sum(array_map('boolval', $data['pcptsd_respuestas']));
        }

        if (isset($data['ptg_respuestas']) && count($data['ptg_respuestas']) === count(self::PTG_ITEMS)) {
            $data['ptg_score'] = array_sum(array_map('intval', $data['ptg_respuestas']));
        } else {
            $data['ptg_respuestas'] = null;
            $data['ptg_score'] = null;
        }

        if (isset($data['picsf_respuestas']) && count($data['picsf_respuestas']) === count(self::PICSF_ITEMS)) {
            $data['picsf_distress'] = array_sum(array_map('intval', $data['picsf_respuestas']));
        } else {
            $data['picsf_respuestas'] = null;
            $data['picsf_distress'] = null;
        }

        return $data;
    }
}
