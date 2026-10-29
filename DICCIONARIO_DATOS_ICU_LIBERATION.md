# Diccionario de datos — ICU Liberation

Todas las tablas nuevas se crean en una sola migración autocontenida: `database/migrations/2026_07_27_040000_create_icu_liberation_program_tables.php` (mismo patrón que `2026_07_27_030000_create_pulmonary_embolism_tables.php` de TEP).

## `icu_units`

| Campo | Tipo | Notas |
|---|---|---|
| `clinical_program_id` | FK | `restrictOnDelete` |
| `site_id` | FK nullable | Sede (modelo `Site`, compartido) |
| `name`, `code` | string | |
| `unit_type` | string | `adult`\|`intermediate`\|`cardiovascular`\|`surgical`\|`neuro`\|`oncologic`\|`other` |
| `bed_count` | int nullable | Aproximación agregada — no hay modelo `IcuBed` individual en esta versión (ver "Decisiones técnicas") |
| `is_active` | bool | |

## `icu_stays` (cabecera de la estancia)

Campos clave: `case_number`/`case_sequence` (numeración `UCI-000001`, generada en `CreateIcuStay`, no por observer), `patient_id`, `site_id`, `icu_unit_id`, `assigned_auditor_id`, `bed_label` (string libre, no FK a una tabla de camas), `admission_number`, `admission_diagnosis`, `admission_source`, `status` (`active`\|`discharged_icu`\|`discharged_hospital`\|`deceased`\|`cancelled`), `admission_at`, `mechanical_ventilation` + `ventilation_start_at`/`extubation_at`/`unplanned_extubation`/`reintubation_at`/`tracheostomy_at`, `icu_discharge_at`/`icu_discharge_destination`, `hospital_discharge_at`, `death_at`, `outcome_state`, `icu_stay_days`/`hospital_stay_days` (decimal, puede registrarse manualmente o derivarse en el servicio de indicadores), `is_valid`/`is_cancelled`/`exclusion_reason`, `completed_at`.

No existe columna `month`: el filtro por período en `IcuLiberationIndicatorService` usa `whereYear('admission_at', $year)`, igual que TEP.

## Tablas hijas (todas `belongsTo IcuStay`, `cascadeOnDelete`)

| Tabla | Modelo | Propósito |
|---|---|---|
| `icu_liberation_rounds` | `IcuLiberationRound` | Ronda diaria: participantes (JSON), RASS/SAS objetivo/real, CAM-ICU/ICDSC, "mapa cerebral", planes por componente, metas del día |
| `icu_pain_assessments` | `IcuPainAssessment` | Componente A — escala, puntaje, autorreporte, intervención, revaluación |
| `icu_sat_trials` | `IcuSatTrial` | Componente B (SAT) — elegibilidad, exclusión, resultado, conducta posterior |
| `icu_sbt_trials` | `IcuSbtTrial` | Componente B (SBT) — elegibilidad, parámetros, duración, resultado, evaluación de extubación |
| `icu_sedation_assessments` | `IcuSedationAssessment` | Componente C — objetivo/real, analgésicos/sedantes/infusiones, bloqueo neuromuscular, riesgo de abstinencia |
| `icu_delirium_assessments` | `IcuDeliriumAssessment` | Componente D — herramienta, evaluabilidad, resultado, subtipo, factores precipitantes, intervenciones (JSON) |
| `icu_mobility_sessions` | `IcuMobilitySession` | Componente E — tamizaje de seguridad, herramienta, nivel meta/basal/alcanzado, duración, distancia, barrera |
| `icu_family_engagements` | `IcuFamilyEngagement` | Componente F — contacto, preferencias, participación en ronda/movilidad/reorientación, reunión familiar (VALUE) |
| `icu_sleep_assessments` | `IcuSleepAssessment` | Sueño (G, complementario) — hábitos, calidad subjetiva, interrupciones, intervenciones (JSON) |
| `icu_physical_restraints` | `IcuPhysicalRestraint` | Restricciones físicas — tipo, indicación, alternativas, retiro |
| `icu_device_reviews` | `IcuDeviceReview` | Revisión diaria de necesidad de dispositivos invasivos |
| `icu_pics_followups` | `IcuPicsFollowup` | Seguimiento post-UCI por hito (48-72h/7d/30d/3m/6m/12m) — función, cognición, ansiedad, depresión, TEPT, calidad de vida, carga del cuidador |
| `icu_stay_audits` | `IcuStayAudit` | Auditoría de caso — criterios cumplidos/no cumplidos (JSON), causa probable, `requires_phva` |

## Tabla 1:1

| Tabla | Modelo | Propósito |
|---|---|---|
| `icu_transfer_checklists` | `IcuTransferChecklist` | Checklist de traslado (17 ítems booleanos, `IcuTransferChecklist::CHECKLIST_ITEMS`) + entrega estructurada UCI-piso (equipo que entrega/recibe, información transmitida, pendientes, riesgos, plan de rehabilitación, comprensión verificada) |

## Convenciones seguidas (heredadas de TEP/Sepsis)

- `#[Fillable([...])]` (atributo PHP 8, no `$fillable` clásico) y `protected function casts(): array` (no propiedad `$casts`).
- Sin políticas Laravel (`Gate::policy`) para `IcuStay`: autorización vía métodos estáticos `canViewAny()`/`canCreate()`/`canEdit()` en el propio `IcuStayResource`, delegando a `ProgramAccess::can()` — mismo patrón que `TepCaseResource`.
- Sin `$table->softDeletes()`: los registros no se eliminan, se marcan `is_cancelled`/`is_valid` o se cierran (`completed_at`).
- JSON para listas variables (`participants`, `interventions`, `criteria_met`/`criteria_not_met`, `referred_to`) en vez de tablas pivote adicionales, cuando el contenido no necesita consultarse relacionalmente.

## Decisiones técnicas explícitas

1. **No se creó un modelo `IcuBed` individual.** El censo requiere "cama" por paciente; se optó por un campo `bed_label` (string libre) en `icu_stays` más `bed_count` agregado en `icu_units`, en vez de una tabla de camas con estado propio (disponible/ocupada/mantenimiento). Si se necesita gestión de camas en tiempo real (bloqueo, mantenimiento, limpieza), se añade `IcuBed` como tabla nueva sin romper `icu_stays.bed_label`.
2. **El semáforo del censo es una aproximación de 3 colores** (verde/amarillo/rojo), no los 6 de la especificación. Ver `MAPA_FUNCIONAL_ICU_LIBERATION.md`.
3. **`icu_stay_days`/`hospital_stay_days` pueden registrarse manualmente o derivarse** en `IcuLiberationIndicatorService` a partir de fechas — ambas rutas coexisten porque no todas las estancias tendrán ambas fechas disponibles el mismo día.
