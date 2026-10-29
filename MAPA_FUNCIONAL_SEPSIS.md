# Mapa funcional — Programa Institucional de Excelencia en Sepsis

## 1. Mapa de navegación (`/sepsis`)

```
Vista general                              (ExecutiveSummary — incluye distribuciones por servicio/foco/adquisición/población especial)
Casos                                       (SepsisCaseResource — exportable a CSV, filtrable por sede)
Ruta Clínica                                (ClinicalPathway — referencia institucional, 17 pasos + línea de tiempo)

Programa
  Información general                       (ClinicalProgramResource)
  Sedes                                      (SiteResource)
  Equipo y competencias                      (ProgramMembershipResource)
     └ Competencias evaluadas                (StaffCompetenciesRelationManager)
  Competencias                               (CompetencyResource — catálogo)
  Comité                                      (ProgramCommitteeResource)
     ├ Integrantes del comité                (MembersRelationManager)
     └ Reuniones                             (MeetingsRelationManager — invitados, correo, acta PDF)
  Matriz RACI                                (RaciAssignmentResource)
  Recursos y acceso                          (ProgramResourceResource)
  Guías y documentos                         (ProgramDocumentResource)
  Brechas y ajustes del protocolo            (ProtocolGapResource)

Calidad y mejora
  Estándares                                 (QualityStandardResource, solo lectura)
  Evidencias                                 (ComplianceEvidenceResource)
  Seguridad del programa                     (SafetyEventResource)
  Hallazgos y acciones                       (FindingResource)
     └ Plan PHVA (acciones de mejora)        (CorrectiveActionsRelationManager)

Indicadores                                  (SepsisIndicators — 6 institucionales + costo + severidad)
Ficha técnica de indicadores                 (IndicatorDefinitionResource — 4 grupos, exportable a CSV)
Informe mensual                              (MonthlyReport — imprimible / exportable a PDF desde el navegador)
Trazadores                                   (Tracers — trazador de paciente y de sistema)
```

Dentro de **Casos**, cada expediente tiene 10 pestañas fijas (Resumen, Tamizaje y activación, Primera hora, Tres horas, Seis horas, Hemodinámica, Infección y control del foco, Continuidad, Paciente y familia, Historial) más un encabezado permanente visible en todas ellas, y estos relation managers:

```
Caso de Sepsis
 ├ Tamizaje                          (sepsis_screenings)
 ├ Tareas del bundle                 (sepsis_bundle_tasks)
 ├ Reevaluaciones hemodinámicas      (sepsis_hemodynamic_assessments)
 ├ Cultivos                          (sepsis_cultures)
 ├ Antimicrobianos                   (sepsis_antimicrobial_administrations)
 ├ Control del foco                  (sepsis_source_control_actions)
 ├ Continuidad y traslados           (sepsis_care_transitions)
 ├ Educación al paciente y familia   (sepsis_patient_education_records)
 ├ Recuperación postsepsis           (sepsis_postsepsis_followups)
 └ Historial de cambios              (clinical_audits, vía ClinicalAuditsRelationManager)
```

## 2. Flujo del caso

```
Identificación / tamizaje institucional
        ↓
Sospecha de infección + NEWS2 ≥ 6 o signo de disfunción/hipoperfusión/deterioro
        ↓
Activación del Código Sepsis  →  tiempo cero (activation_at, validable con auditoría)
        ↓
Primera hora: lactato, hemocultivos, antibiótico, cristaloides, vasopresor temprano
        ↓
Tres horas: reevaluación de perfusión, PAM, respuesta a volumen, control del foco
        ↓
Seis horas: metas de reanimación, ajuste antimicrobiano, destino definitivo
        ↓
Hemodinámica: reevaluaciones repetibles con fenotipo (respondedor/vasopléjico/bajo gasto/congestión)
        ↓
Infección y control del foco: cultivos, antimicrobianos, decisión/procedimiento de control del foco
        ↓
Continuidad: traslados entre servicios, pendientes, necesidad de UCI
        ↓
Egreso  →  Educación al paciente y familia (teach-back, preparación de egreso)
        ↓
Recuperación postsepsis: seguimientos en 48-72h, 7d, 15d, 30d, 90d
```

Cada paso queda auditado automáticamente (usuario, campo, valor anterior/nuevo, fecha) vía `SepsisCaseObserver` (para el caso) y `SepsisChildRecordObserver` (para las 9 tablas hijas).

## 3. Flujo de auditoría

```
Caso hospitalizado, con egreso y auditor asignado
        ↓
"Enviar a análisis" (líder/administrador)  →  estado Asignado
        ↓
Auditor abre el caso  →  estado En revisión (automático)
        ↓
"Finalizar análisis" (auditor o líder)  →  estado Pendiente de revisión
        ↓
"Finalizar revisión" (líder/administrador)  →  estado Finalizado
        ↓ (excepcional)
"Reabrir caso" (líder/administrador)  →  vuelve a En revisión
```

Historial completo consultable en la pestaña **Historial** de cada caso.

## 4. Flujo PHVA (hallazgo → plan de mejora)

```
Evaluación de cumplimiento (elemento evaluable)
        ↓
Hallazgo (severidad, descripción)
        ↓
5 Porqués (por qué 1 → 2 → 3 → 4 → 5)
        ↓
Causa raíz
        ↓
6 validaciones obligatorias (explica la brecha, continuidad lógica,
sin causas repetidas, no es síntoma, la intervención reduce la brecha,
no ataca solo consecuencias)
        ↓
Plan PHVA — cada acción con:
  Etapa (Planear/Hacer/Verificar/Actuar) · Verbo en infinitivo (validado)
  Responsable · Fecha compromiso · % de avance
  [Si Verificar] Indicador relacionado + meta + frecuencia
        ↓
Evidencia esperada  →  Evidencia cargada (PDF, obligatoria para cerrar)
        ↓
Verificación de efectividad (obligatoria para cerrar)
        ↓
Estado: Abierta → En ejecución → Pendiente de verificación → Efectiva/Cerrada
                                                             ↘ No efectiva → Reabierta
```

## 5. Flujo postsepsis

```
Egreso del caso
        ↓
Checklist de egreso (resumen, conciliación de medicamentos, educación,
teach-back, signos de alarma) — SepsisPatientEducationRecord
        ↓
Seguimiento 48-72 horas   ┐
Seguimiento 7 días        │  cada uno registra: contacto logrado, estado
Seguimiento 15 días        ├  clínico/funcional/cognitivo/emocional, reingreso,
Seguimiento 30 días        │  reconsulta, adherencia, barreras, mortalidad,
Seguimiento 90 días       ┘  riesgo de recurrencia, necesidad de intervención
```

Consultable de forma consolidada (junto con el resto del episodio) en **Trazadores → Trazador de paciente**.
