# Modelo de gobierno — Programa ICU Liberation

## 1. Carta constitutiva (registrada en `ClinicalProgram`, código `ICULIB`)

| Campo | Contenido |
|---|---|
| Nombre | Programa Institucional de Excelencia en ICU Liberation, Recuperación del Paciente Crítico y Prevención del Síndrome Post-UCI |
| Propósito | Reducir el daño iatrogénico, la ventilación mecánica innecesaria, la sedación profunda, el delirium, la inmovilidad, las restricciones físicas y las secuelas del síndrome post-UCI mediante la aplicación interdisciplinaria, segura, humanizada y medible del bundle ABCDEF. |
| Población objetivo | Pacientes adultos elegibles atendidos en UCI de adultos, cuidado intermedio, UCI cardiovascular, quirúrgica, neurológica u oncológica. |
| Alcance | Desde el ingreso a UCI y la evaluación inicial hasta el traslado, el egreso hospitalario y el seguimiento del síndrome post-UCI (PICS). |
| Criterio de exclusión explícito | Población pediátrica — módulo separado, desactivado, requiere escalas y reglas propias. |
| Estado | `implementation` (en implementación) — cambia a `active` cuando el Comité lo apruebe. |

Editable desde **Programa ICU Liberation → Información general** (`ClinicalProgramResource`, reutilizado).

## 2. Roles de gobierno (reutiliza `App\Enums\ProgramRole`)

No se creó ningún rol nuevo: los 10 roles ya existentes (compartidos entre Sepsis/Infarto/TEP/ICU Liberation) cubren el listado de la sección 7 de la especificación:

| Rol de la especificación | Rol técnico (`ProgramRole`) |
|---|---|
| Patrocinador ejecutivo | `ExecutiveDirection` |
| Director médico / Coordinador ICU Liberation | `Leader` / `Coordinator` |
| Codirector de enfermería / Líder clínico | `ClinicalLeader` |
| Líder de terapia respiratoria, fisioterapia, terapia ocupacional, farmacia, nutrición, psicología, trabajo social, fonoaudiología | `Physician` / `Nurse` (según corresponda) o `CommitteeMember` |
| Líder de calidad | `QualityManager` |
| Líder de seguridad del paciente | `QualityManager` (comparte alcance con calidad) |
| Analista de información | `DataAnalyst` |
| Representante de pacientes y familias | `CommitteeMember` |
| Líder de seguimiento post-UCI | `ClinicalLeader` o `Coordinator` |
| Consulta | `Viewer` |

Asignación desde **Programa ICU Liberation → Equipo y competencias** (`ProgramMembershipResource`, reutilizado). Los permisos por rol (`ProgramPermission`) son los mismos 12 ya existentes (`ViewProgram`, `ManageProgram`, `ViewCases`, `CreateCases`, `EditCases`, `AuditCases`, `ApproveCases`, `ViewIndicators`, `ManageStandards`, `ManageEvidence`, `ManageGovernance`, `ManageProgramUsers`).

## 3. Comité Institucional ICU Liberation

Se crea desde **Programa ICU Liberation → Comité** (`ProgramCommitteeResource`, reutilizado) — no se implementó un modelo `Committee` nuevo: es el mismo `ProgramCommittee`/`CommitteeMeeting`/`MeetingDecision`/`MeetingAction` que usan Sepsis y TEP, con `clinical_program_id` apuntando a `ICULIB`.

Funciones (según sección 7 de la especificación, todas ejercidas a través de los recursos ya existentes):

- Adoptar guías y aprobar protocolos → **Guías y documentos** + **Reglas clínicas** (`ClinicalRuleResource`, cambiando `status` de `draft` a `approved`).
- Revisar resultados, mortalidad y complicaciones → **Indicadores** / **Vista general**.
- Gestionar sedación, delirium, movilidad → auditorías de estancia (`icu_stay_audits`) y hallazgos.
- Evaluar eventos de seguridad → auditoría de estancia + `requires_phva`.
- Aprobar indicadores y gestionar PHVA → **Ficha técnica de indicadores** + **Hallazgos y acciones** (`FindingResource` → `CorrectiveActionsRelationManager`).
- Evaluar competencias → **Equipo y competencias**.
- Rendir cuentas a la alta dirección → informe trimestral (pendiente de plantilla dedicada, ver `MAPA_FUNCIONAL_ICU_LIBERATION.md`).

## 4. Matriz RACI

`RaciAssignmentResource` (reutilizado) — se asigna Responsable/Aprobador/Consultado/Informado por proceso (ronda diaria, SAT/SBT, delirium, movilidad, traslado, PICS) desde **Programa ICU Liberation → Matriz RACI**. No se precargó una matriz demostrativa completa en esta ronda (ver pendientes en `MAPA_FUNCIONAL_ICU_LIBERATION.md`).

## 5. Reglas clínicas configurables, versionadas y trazables

Toda regla asistencial (criterios de exclusión SAT/SBT, selección de escala de dolor, herramienta de delirium, criterios de seguridad de movilidad) vive en `ClinicalRule`:

- `version` + `effective_from`/`effective_until`: vigencia.
- `source_document` + `source_section`: trazabilidad a la fuente.
- `status`: `draft` → requiere `approved_by`/`approved_at` antes de considerarse institucional.
- `configuration` (JSON libre): parámetros propios de cada regla.

Las 5 reglas sembradas para ICU Liberation (`ICUL-SAT-01`, `ICUL-SBT-01`, `ICUL-PAIN-01`, `ICUL-DELIRIUM-01`, `ICUL-MOBILITY-01`) están en `draft` — **ninguna se activa automáticamente**; requieren aprobación explícita del Comité antes de usarse como criterio asistencial vinculante.

## 6. Rendición de cuentas

Semáforos de cumplimiento (Vista general, Censo, Indicadores), informe trimestral (pendiente de plantilla dedicada análoga a `MonthlyReport` de Sepsis) y auditoría de casos priorizados (`icu_stay_audits`) alimentan la rendición de cuentas a la alta dirección.

**Nota de trazabilidad**: a diferencia de Sepsis (que registra cada cambio de campo mediante `ClinicalAudit`/`SepsisChildRecordObserver`), `IcuStay` y sus componentes siguen el patrón más liviano ya adoptado para TEP — sin observador de auditoría por campo. Si el gobierno del programa requiere ese nivel de trazabilidad, se puede activar registrando los mismos modelos en `ClinicalAuditService` (ver `app/Observers/SepsisChildRecordObserver.php` como referencia) sin duplicar el mecanismo.
