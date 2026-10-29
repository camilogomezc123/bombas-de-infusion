# Implementación — Programa Institucional de Excelencia en ICU Liberation, Recuperación del Paciente Crítico y Prevención del Síndrome Post-UCI

## 1. Inspección previa del repositorio

Antes de programar se confirmó que ÁGORA ya tenía, como consecuencia de la construcción previa de Sepsis y TEP, una **capa de gobierno/calidad/documentos/competencias/indicadores completamente genérica** (`ClinicalProgram`, `ProgramMember`, `ProgramCommittee`, `RaciAssignment`, `ProgramResource`, `EvidenceDocument`, `ClinicalRule`, cadena `AccreditationStandard`→`MeasurableElement`→`ComplianceAssessment`→`AssessmentFinding`→`CorrectiveAction`, `IndicatorDefinition`, `Competency`/`StaffCompetency`), parametrizada por `clinical_program_id` y resuelta en tiempo real mediante `App\Support\ProgramContext`/`ProgramAccess::currentCode()`. Esta capa **se reutilizó en su totalidad**, sin crear un solo modelo ni recurso Filament nuevo para gobierno, calidad, documentos, RACI, reglas clínicas o competencias.

También se confirmó que TEP (`TepCase`, `TepIndicatorService`, `TepPanelProvider`) es el precedente arquitectónico más reciente y leve (migración única autocontenida, sin observadores de auditoría por campo, autorización vía métodos estáticos en el Resource en vez de `Gate::policy`). El programa ICU Liberation se construyó siguiendo **ese mismo método**, no el de Sepsis (más antiguo y más pesado).

Ninguno de los 4 documentos fuente solicitados se encontró en el repositorio (ver `MATRIZ_ADOPCION_ICU_LIBERATION.md`). Se continuó con datos demostrativos y reglas clínicas en borrador, como se instruyó explícitamente.

## 2. Módulos construidos (versión funcional inicial solicitada)

1. **Gobierno clínico** — reutilizado; solo se sembró el registro `ClinicalProgram` (código `ICULIB`) y 5 reglas clínicas en borrador.
2. **Censo UCI** (`IcuCensus`) — estancias activas con semáforo de cumplimiento del bundle del día (verde/amarillo/rojo).
3. **Ronda ICU Liberation** (`LiberationRound`) — herramienta diaria con "mapa cerebral" (RASS/SAS objetivo/real, CAM-ICU/ICDSC, barreras, plan de ajuste) y planes por componente.
4. **Bundle ABCDEF completo** — 12 tablas hijas de `IcuStay` (dolor, SAT, SBT, sedación, delirium, movilidad, familia, sueño, restricciones, dispositivos, PICS, auditoría), cada una con su `RelationManager` dentro de **Estancias UCI**.
5. **Registro diario** — vía los relation managers anteriores, cada uno con fecha/hora propia (no hay límite de un registro por día; el censo y el motor de indicadores filtran por fecha cuando corresponde).
6. **Indicadores** — `IcuLiberationIndicatorService` (15 indicadores en vivo) + `IndicatorDefinition` (catálogo ampliado de ~25 indicadores adicionales documentados, sin cálculo automático todavía).
7. **Dashboard** — `ExecutiveSummary` (Vista general) e `IcuLiberationIndicators` (tablero dedicado).
8. **Auditoría** — `IcuStayAudit` por estancia, con bandera `requires_phva` que remite al motor PHVA genérico (`FindingResource`/`CorrectiveActionsRelationManager`, el mismo de Sepsis/TEP).
9. **PHVA** — reutilizado (`CorrectiveAction` con las 4 etapas y verificación de efectividad obligatoria para cerrar).
10. **Traslado y PICS** — `IcuTransferChecklist` (17 ítems + entrega estructurada) embebido en la estancia, y `IcuPicsFollowup` (6 hitos) como relation manager.

## 3. Modelo de datos (nuevo en esta ejecución)

17 tablas nuevas en una única migración autocontenida (`2026_07_27_040000_create_icu_liberation_program_tables.php`, mismo patrón que la de TEP): `icu_units`, `icu_stays`, `icu_liberation_rounds`, `icu_pain_assessments`, `icu_sat_trials`, `icu_sbt_trials`, `icu_sedation_assessments`, `icu_delirium_assessments`, `icu_mobility_sessions`, `icu_family_engagements`, `icu_sleep_assessments`, `icu_physical_restraints`, `icu_device_reviews`, `icu_transfer_checklists`, `icu_pics_followups`, `icu_stay_audits`. Ver `DICCIONARIO_DATOS_ICU_LIBERATION.md` para el detalle campo por campo.

## 4. Reglas de negocio destacadas

- `IcuLiberationIndicatorService::bundleCompliance()` calcula "dosis del bundle" **por oportunidades** (no todo-o-nada): cuenta componentes elegibles cumplidos sobre componentes elegibles totales, con SAT/SBT sumándose solo cuando la estancia está ventilada.
- Ningún flujo ejecuta una acción clínica automáticamente: no hay botón que "active" un SAT/SBT, no se calcula elegibilidad de forma vinculante, no se sugiere un sedante ni una dosis. Todos los campos de "elegible"/"resultado" son de **registro**, no de decisión.
- Las reglas clínicas configurables (`ClinicalRule`) nacen en `status=draft` y requieren aprobación institucional explícita antes de poder citarse como criterio vinculante.
- El módulo pediátrico se dejó explícitamente fuera de alcance (población objetivo del programa limitada a adultos).

## 5. Seguridad clínica

El banner de advertencia de la sección 4 de la especificación está presente en: `ExecutiveSummary`, `IcuCensus`, `LiberationRound`, `ClinicalPathway` y el formulario de `IcuStayResource`. Texto exacto: *"Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa ICU Liberation. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados."*

## 6. Pruebas

`tests/Feature/IcuLiberation/IcuLiberationFoundationTest.php` (8 pruebas): sembrado de programa/indicadores/reglas, reversibilidad de la migración, relaciones completas de `IcuStay`, cálculo de completitud del checklist de traslado, cálculo del indicador compuesto de bundle (con verificación explícita de que no altera los indicadores de Sepsis), autorización (líder puede, usuario sin membresía no puede) y renderizado real de las 5 páginas del panel.

Suite completa del repositorio tras esta ronda: **107/107 pruebas en verde**.

## 7. Decisiones técnicas explícitas

1. **Sin modelo `IcuBed` individual** — el censo usa `bed_label` (string libre) en vez de una tabla de camas con estado propio. Ver justificación en `DICCIONARIO_DATOS_ICU_LIBERATION.md`.
2. **Semáforo del censo de 3 colores**, no los 6 de la especificación (faltan azul "programado" y morado "requiere decisión interdisciplinaria") — documentado como siguiente fase.
3. **Sin observador de auditoría por campo** en los componentes del bundle (a diferencia de Sepsis) — se siguió el patrón más leviano de TEP. Documentado en `MODELO_GOBIERNO_ICU_LIBERATION.md` junto con cómo activarlo si se necesita.
4. **Catálogo de indicadores amplio pero parcialmente "solo documental"** — 15 en vivo, ~25 catalogados sin cálculo automático, para no atribuir metas que la especificación no definió explícitamente.
5. **Datos de demostración** (`IcuLiberationDemoSeeder`, gitignored) cubren 20 pacientes/estancias distribuidas en 6 meses con la proporción exacta pedida (10 ventilados, 8 SAT, 7 SBT, 5 delirium, 10 movilizados, 3 restricciones, 10 familiares, 5 PICS, 3 auditorías) más 6 documentos representativos — no los 15/20/3 completos de la sección 48, documentado como pendiente.

## 8. Registro adicional durante esta ejecución

Se detectó que la capa compartida (`ProgramContext`, `ExcellenceCenters`, `Hub`, `HubMetrics`) no tenía entrada para el nuevo programa — se completaron las 4 (más el ajuste correspondiente en `tests/Feature/HubAndDashboardPerformanceTest.php`, que tenía aserciones con el conteo de centros/consultas hardcodeado en 4).

## 9. Pendientes reales para la siguiente fase

- Semáforo completo de 6 colores, comparación entre unidades/turnos, control estadístico de procesos.
- Informes PDF/CSV dedicados (hoy la exportación disponible es la genérica de las tablas Filament).
- Matriz de adopción documental con contenido real (pendiente de los 4 documentos fuente).
- Ampliar el catálogo de indicadores en vivo más allá de los 15 iniciales, según prioridad clínica del Comité.
- Módulo pediátrico (deliberadamente desactivado).
- Investigación, docencia y simulación.
- Comité, RACI y competencias demostrativas específicas de ICU Liberation (hoy los recursos existen y funcionan, pero no se precargó una matriz RACI ni un comité demostrativo propio del programa, a diferencia de Sepsis).

## 10. Comandos ejecutados para verificar esta entrega

```bash
php artisan migrate
php artisan migrate:rollback --step=1   # verificado reversible
php artisan migrate
php artisan db:seed --class=IcuLiberationDemoSeeder
php artisan test        # 107/107
npm run build            # sin errores
```
