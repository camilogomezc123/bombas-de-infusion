# Mapa funcional — Programa Institucional de Excelencia en ICU Liberation

## 1. Mapa de navegación (`/icu-liberation`)

```
Vista general                              (ExecutiveSummary — indicadores en vivo + estancias activas/ventiladas/restricciones)
Censo UCI                                   (IcuCensus — semáforo verde/amarillo/rojo por cumplimiento del bundle del día)
Ruta clínica                                (ClinicalPathway — referencia institucional del recorrido ABCDEF)

Programa ICU Liberation
  Estancias UCI                             (IcuStayResource)
  Ronda ICU Liberation                      (LiberationRound — herramienta diaria, "mapa cerebral")

Gobierno clínico  (reutilizado 100% de Sepsis/TEP — mismos modelos, mismo código)
  Información general                       (ClinicalProgramResource)
  Equipo y competencias                      (ProgramMembershipResource)
  Competencias                               (CompetencyResource)
  Comité                                      (ProgramCommitteeResource)
  Matriz RACI                                (RaciAssignmentResource)
  Recursos y acceso                          (ProgramResourceResource)
  Guías y documentos                         (ProgramDocumentResource)
  Brechas y ajustes del protocolo            (ProtocolGapResource)
  Reglas clínicas                            (ClinicalRuleResource — SAT/SBT, dolor, delirium, movilidad; estado draft)

Calidad y mejora  (reutilizado)
  Estándares                                 (QualityStandardResource)
  Evidencias                                 (ComplianceEvidenceResource)
  Hallazgos y acciones                       (FindingResource → CorrectiveActionsRelationManager, plan PHVA)

Indicadores                                  (IcuLiberationIndicators)
Ficha técnica de indicadores                 (IndicatorDefinitionResource — en vivo + catalogados)
```

Dentro de **Estancias UCI**, cada expediente tiene 4 pestañas (Resumen, Ventilación, Traslado y egreso, Cierre) más un banner de seguridad clínica permanente, y estos relation managers:

```
Estancia UCI
 ├ Rondas ICU Liberation             (icu_liberation_rounds)
 ├ Dolor                             (icu_pain_assessments)
 ├ SAT                               (icu_sat_trials)
 ├ SBT                               (icu_sbt_trials)
 ├ Analgesia y sedación              (icu_sedation_assessments)
 ├ Delirium y cognición              (icu_delirium_assessments)
 ├ Movilidad temprana                (icu_mobility_sessions)
 ├ Familia y humanización            (icu_family_engagements)
 ├ Sueño y ambiente                  (icu_sleep_assessments)
 ├ Restricciones físicas             (icu_physical_restraints)
 ├ Dispositivos                      (icu_device_reviews)
 ├ Recuperación post-UCI (PICS)      (icu_pics_followups)
 └ Auditoría del caso                (icu_stay_audits, con bandera requires_phva)
```

El checklist de traslado y entrega estructurada (`icu_transfer_checklists`, 1:1 con la estancia) vive embebido en la pestaña "Traslado y egreso" del formulario principal, no como relation manager aparte (es un registro único por estancia, no repetible).

## 2. Flujo de la estancia

```
Ingreso a UCI  →  admission_at, diagnóstico principal, servicio de origen
        ↓
Ronda ICU Liberation (diaria)  →  RASS/SAS objetivo vs. real, CAM-ICU/ICDSC, barreras, plan de ajuste
        ↓
Bundle ABCDEF (continuo, por oportunidad)
  A: Dolor evaluado/tratado          → icu_pain_assessments
  B: SAT + SBT (si ventilado)        → icu_sat_trials / icu_sbt_trials
  C: Objetivo y nivel de sedación    → icu_sedation_assessments
  D: Delirium (CAM-ICU/ICDSC)        → icu_delirium_assessments
  E: Movilidad temprana              → icu_mobility_sessions
  F: Familia y humanización          → icu_family_engagements
  G (complementario): Sueño          → icu_sleep_assessments
        ↓
Ventilación → Extubación (SAT+SBT exitosos) o reintubación/traqueostomía
        ↓
Traslado / egreso de UCI  →  IcuTransferChecklist (17 ítems) + entrega estructurada UCI-piso
        ↓
Egreso hospitalario  →  hospital_discharge_at / death_at
        ↓
Recuperación post-UCI (PICS)  →  icu_pics_followups (48-72h, 7d, 30d, 3m, 6m, 12m)
```

## 3. Motor de indicadores

`IcuLiberationIndicatorService::dashboard(?year)` es la única fuente de los indicadores en vivo. Reutiliza el mismo método que `TepIndicatorService`/`SepsisIndicatorService`: una sola lectura de estancias por período, sin dosis ni decisiones clínicas automáticas.

Ver el detalle completo de fórmulas en `DICCIONARIO_INDICADORES_ICU_LIBERATION.md`.

## 4. Pendiente para la siguiente fase

- Semáforo completo de 6 colores en el censo (hoy: verde/amarillo/rojo; faltan azul "programado" y morado "requiere decisión interdisciplinaria").
- Comparación entre unidades (`IcuUnit`) y por turno.
- Control estadístico de procesos (percentiles, tendencias con bandas).
- Informes PDF/CSV dedicados (hoy solo se puede exportar desde las tablas de Filament donde ya existe `CsvExporter`).
- Módulo pediátrico (deliberadamente desactivado — requiere escalas y reglas propias).
- Investigación/docencia y simulación.
- Matriz de adopción documental formal (`MATRIZ_ADOPCION_ICU_LIBERATION.md`) una vez existan los documentos fuente reales.
