# Diccionario de indicadores — ICU Liberation

## 1. Indicadores en vivo (`IcuLiberationIndicatorService::dashboard()`)

Estos son la única fuente de verdad para su respectivo `core_indicator_key`. `IndicatorDefinition` los referencia con `is_core_indicator=true` y los **lee** en tiempo de consulta — nunca los recalcula ni los duplica.

| Código | Nombre | `core_indicator_key` | Numerador | Denominador | Sentido |
|---|---|---|---|---|---|
| COMP-01 | Cumplimiento del bundle ABCDEF (por oportunidades) | `bundle_compliance_pct` | Componentes elegibles con registro | Componentes elegibles (dolor+sedación+delirium+movilidad, + SAT+SBT si ventilado) | A mayor, mejor |
| A-01 | Dolor evaluado | `pain_assessment_pct` | Estancias con ≥1 `icu_pain_assessments` | Total de estancias del período | A mayor, mejor |
| B-02 | SAT realizado entre elegibles | `sat_realized_pct` | Estancias ventiladas con ≥1 `icu_sat_trials` | Estancias ventiladas | A mayor, mejor |
| B-06 | SBT realizado entre elegibles | `sbt_realized_pct` | Estancias ventiladas con ≥1 `icu_sbt_trials` | Estancias ventiladas | A mayor, mejor |
| C-01 | Objetivo de sedación documentado | `sedation_goal_documented_pct` | Estancias con `goal_value` registrado | Total de estancias del período | A mayor, mejor |
| D-01 | Evaluación de delirium realizada | `delirium_assessment_pct` | Estancias con ≥1 `icu_delirium_assessments` | Total de estancias del período | A mayor, mejor |
| D-05 | Prevalencia diaria de delirium | `delirium_prevalence_pct` | Estancias evaluadas con algún resultado `positive` | Estancias con evaluación de delirium | A menor, mejor |
| E-03 | Movilidad realizada entre elegibles | `mobility_realized_pct` | Estancias con ≥1 `icu_mobility_sessions` | Total de estancias del período | A mayor, mejor |
| RES-ICUL-03 | Días de ventilación mecánica (mediana) | `ventilation_days_median` | — (mediana) | `ventilation_start_at` → `extubation_at`/egreso/muerte | A menor, mejor |
| RES-ICUL-05 | Estancia en UCI (mediana, días) | `icu_los_median_days` | — (mediana) | `icu_stay_days` o `admission_at` → `icu_discharge_at`/muerte | A menor, mejor |
| RES-ICUL-01 | Mortalidad hospitalaria | `hospital_mortality_pct` | Estancias con `death_at` no nulo | Total de estancias del período | A menor, mejor |
| RES-ICUL-02 | Mortalidad en UCI | `icu_mortality_pct` | `death_at` no nulo Y `icu_discharge_at` nulo (fallece sin haber egresado de UCI) | Total de estancias del período | A menor, mejor |
| RES-ICUL-10 | Reintubación | `reintubation_pct` | Estancias extubadas con `reintubation_at` no nulo | Estancias con `extubation_at` no nulo | A menor, mejor |
| RES-ICUL-20 | Autoextubación | `unplanned_extubation_pct` | Estancias ventiladas con `unplanned_extubation=true` | Estancias ventiladas | A menor, mejor |
| PICS-05 | Seguimiento post-UCI iniciado | `pics_followup_rate_pct` | Estancias egresadas con ≥1 `icu_pics_followups` | Estancias egresadas (UCI u hospital) | A mayor, mejor |

Complementarios (mostrados en el tablero, sin meta institucional propia todavía): `family_identified_pct` (familiar identificado).

**Nota de exclusión metodológica**: el sueño (componente "G") y la participación familiar (componente "F") se miden como indicadores propios y **no** entran en el cálculo de `bundle_compliance_pct`, para no diluir la medida estricta de los 4 componentes ABCDEF de aplicación continua (dolor, sedación, delirium, movilidad) más SAT/SBT cuando hay ventilación mecánica — conforme a la instrucción explícita de no presentar el sueño como sustituto oficial del bundle.

## 2. Indicadores catalogados (pendientes de fuente automática)

Sembrados en `IndicatorDefinition` con `is_core_indicator=false`, cubren el alcance institucional completo de las secciones 24-35 de la especificación, sin meta atribuida (las metas deben aprobarse tras construir línea base):

`EST-ICUL-01/02/03/04/05/06/07/12`, `A-08`, `B-11/12`, `C-04/07`, `D-06`, `E-06/10`, `F-01/05`, `SLP-01`, `RES-ICUL-12/17`, `PICS-01/07`, `BAL-01/03`.

Para activar el cálculo automático de cualquiera de estos, se agrega el campo a `IcuLiberationIndicatorService::dashboard()` y se marca `is_core_indicator=true` con su `core_indicator_key` en la migración de referencia — nunca se le asigna una meta que la especificación no definiera explícitamente sin aprobación del Comité ICU Liberation.

## 3. Reglas de cálculo compartidas con el resto de ÁGORA

- Solo se incluyen estancias con `is_valid=true` y `is_cancelled=false`.
- El filtro por año usa `whereYear('admission_at', $year)` (mismo patrón que `TepIndicatorService`), no un campo `month` derivado.
- Las medianas usan `Collection::median()` sobre valores no nulos únicamente.
- Ningún indicador prescribe, ordena ni ejecuta una acción clínica — todos son descriptivos, calculados sobre lo que el equipo ya registró.
