# Implementación — Programa Institucional de Excelencia en Sepsis y Recuperación Postsepsis

Este documento describe lo construido dentro de ÁGORA para el programa de Sepsis. Se integró **sobre** la aplicación Laravel + Filament existente — no se creó una aplicación aparte, no se eliminó nada del centro ACV ni de lo ya construido del propio módulo de Sepsis.

## 1. Módulos construidos

| Módulo pedido | Dónde vive en ÁGORA | Estado |
|---|---|---|
| Inicio / dashboard ejecutivo | Página **Vista general** (`ExecutiveSummary`) | Parcial — cubre indicadores en meta, casos del mes, cumplimiento, alertas (evidencias vencidas, hallazgos, acciones, eventos de seguridad), completitud del registro. Faltan las gráficas de distribución por servicio/foco/población especial (ver §5 Pendientes). |
| Programa de Sepsis | **Programa → Información general** (`ClinicalProgramResource`) | Completo — nombre, propósito, misión, visión, población, alcance, inclusiones/exclusiones, patrocinador, líderes, versión, próxima revisión. |
| Gobierno clínico | **Programa → Comité** (`ProgramCommitteeResource` + `MembersRelationManager` + `MeetingsRelationManager`), **Programa → Matriz RACI** (`RaciAssignmentResource`) | Completo — comité, reuniones con invitados automáticos, envío de convocatoria por correo, carga de actas en PDF, decisiones, compromisos, matriz RACI. |
| Ruta clínica | 10 pestañas del caso (`SepsisCaseForm`/`SepsisCaseInfolist`) + relation managers de tamizaje, bundle, hemodinámica, cultivos, antimicrobianos, control del foco, continuidad | Completo como registro estructurado por caso. No es una ruta gráfica interactiva de nodos (ver §5). |
| Código Sepsis (checklist, semáforos) | Pestañas "Tamizaje y activación", "Primera hora", "Tres horas", "Seis horas" + relation manager **Tareas del bundle** (`sepsis_bundle_tasks`, 8 estados: pendiente/cumplido/cumplido fuera de tiempo/no indicado/contraindicado/omitido/no disponible/requiere auditoría) | Completo el registro y los estados. No hay temporizador visual en vivo (JS) ni semáforo automático por vencimiento (ver §5). |
| Casos y auditorías | `SepsisCaseResource` (lista, filtros, detalle) + `ClinicalAuditsRelationManager` (historial de cambios) | Completo. |
| Indicadores | Página **Indicadores** (`SepsisIndicators` + `SepsisIndicatorService`) + **Ficha técnica de indicadores** (`IndicatorDefinitionResource`) | Los 6 indicadores institucionales (proceso/resultado) intactos y sin duplicar. Ficha técnica nueva cubre los 4 grupos (estructura/proceso/resultado/experiencia) — los 6 institucionales se leen en vivo del servicio; el resto son de carga manual (no hay fuente de datos automática todavía). |
| Planes de mejoramiento (PHVA) | **Calidad y mejora → Hallazgos y acciones** (`FindingResource` + `CorrectiveActionsRelationManager`) | Completo — 5 Porqués, causa raíz, 6 validaciones, plan PHVA con etapa, verbo en infinitivo validado, evidencia obligatoria para cerrar, verificación de efectividad obligatoria. |
| Recuperación postsepsis | Pestaña "Paciente y familia" (`SepsisPatientEducationRecord`, checklist de egreso) + relation manager **Recuperación postsepsis** (`SepsisPostsepsisFollowup`, hitos 48-72h/7d/15d/30d/90d) | Completo. |
| Talento humano | **Programa → Equipo y competencias** (`ProgramMembershipResource`) + **Programa → Competencias** (`CompetencyResource` catálogo) + `StaffCompetenciesRelationManager` | Completo — catálogo de competencias por rol/perfil y evaluación por persona. |
| Documentos | **Programa → Guías y documentos** (`ProgramDocumentResource`, reutiliza `EvidenceDocument`) | Completo — 14 documentos principales precargados (metadatos, sin archivo real). |
| Brechas y ajustes del protocolo | **Programa → Brechas y ajustes del protocolo** (`ProtocolGapResource`) | Completo — 10 puntos precargados tal como se entregaron, para revisión del comité. No se modificó ninguna recomendación clínica automáticamente. |
| Configuración | Roles y permisos de ÁGORA (`UserRole`, `ProgramRole`, `ProgramPermission`) | Completo — 10 roles de programa (ver README). No hay una pantalla de "Configuración" separada; los catálogos se administran en cada módulo. |

## 2. Modelo de datos (solo lo nuevo de esta ejecución; ver `MAPA_FUNCIONAL_SEPSIS.md` para el listado completo acumulado)

- `ProtocolGap`, `RaciAssignment`, `IndicatorDefinition`, `Competency`, `StaffCompetency`, `SepsisPostsepsisFollowup`.
- Columnas nuevas: `assessment_findings` (5 porqués, causa raíz, 6 validaciones booleanas), `corrective_actions` (fase PHVA, indicador relacionado, meta, frecuencia, evidencia esperada/cargada, % avance), `committee_members`/`committee_meetings` (correo, acta en PDF), `program_members` (ya existían disciplina/servicio/competencias desde una fase anterior).
- Todas las migraciones son reversibles (`down()` probado con un rollback real de Artisan, no simulado) y usan sintaxis portable — sin SQL específico de SQLite o PostgreSQL.

## 3. Reglas de negocio destacadas

- **Verbo en infinitivo obligatorio** en la acción PHVA (`CorrectiveAction::startsWithInfinitiveVerb()`), validado en el formulario.
- **Evidencia obligatoria para cerrar** una acción (estado Efectiva/Cerrada exige archivo PDF cargado) y **verificación de efectividad obligatoria** para el mismo cierre.
- **Indicador/meta/frecuencia obligatorios** cuando la acción está en la etapa "Verificar".
- **Invitados automáticos** en reuniones del comité (se preseleccionan los integrantes activos) y **envío de convocatoria** por correo solo a quienes tienen correo resuelto (propio o del usuario ÁGORA vinculado).
- **No duplicación de indicadores**: `IndicatorDefinition::liveResult()` lee `SepsisIndicatorService` para los 6 institucionales; nunca se recalculan ni se guardan valores duplicados. Verificado con pruebas que comparan el resultado antes/después de registrar costo o severidad.

## 4. Seguridad

- Autorización por Policy en **todos** los modelos nuevos (ninguno permite `delete`), reutilizando `ProgramAccess`/`ProgramPermission`/`ProgramRole` ya existentes.
- Archivos (actas de comité, evidencias de cierre PHVA) se guardan en el disco `local` (`storage/app/private`, no público) — nunca como binario en la base de datos.
- Sin datos reales identificables de pacientes en seeders; `GovernanceDemoSeeder` es exclusivamente local y está fuera de Git.
- Auditoría de cambios (`ClinicalAudit`) cubre `SepsisCase` y las 9 tablas hijas mediante un observer genérico reutilizable.

## 5. Segunda ronda de mejoras (completadas)

Sobre los pendientes de la entrega anterior:

1. **Multi-sede**: modelo `Site` (aditivo, `site_id` nullable en `sepsis_cases` y `program_resources`), resource **Programa → Sedes**, 2 sedes de demostración.
2. **Dashboard "Inicio" completo**: se agregaron las 4 distribuciones faltantes (servicio de origen, foco infeccioso, comunitaria/hospitalaria, población especial) como nuevos campos en `SepsisCase` (`origin_service`, `acquisition_type`, `special_population`) + gráficas en Vista general.
3. **Ruta clínica gráfica**: nueva página **Ruta Clínica** con los 17 pasos (objetivo, responsable, tiempo esperado, requisitos, evidencia, indicador y documento relacionado, riesgo) y la línea de tiempo de hitos — contenido de referencia institucional, no depende de un caso.
4. **Temporizadores y semáforos en vivo**: el encabezado del caso ahora usa Alpine.js (ya cargado por Filament, sin dependencia nueva) para actualizar el tiempo transcurrido cada segundo y colorear cada tarea del bundle en vivo (verde/amarillo/rojo/gris) según su vencimiento a 60 minutos.
5. **Exportación CSV**: acción "Exportar CSV" nativa (PHP `fputcsv`, sin cola ni dependencias) en Casos y en Ficha técnica de indicadores.
6. **Informe mensual**: página **Informe mensual** imprimible (HTML optimizado para impresión → "Guardar como PDF" del navegador). No se instaló una librería de generación de PDF (p. ej. `barryvdh/laravel-dompdf`) sin confirmarlo contigo primero — es una dependencia nueva real, aunque estándar y liviana. Si la quieres, es un solo `composer require` y puedo generar los 12 informes como PDF real en vez de HTML imprimible.

## 6. Pendientes reales restantes

1. **Comparador visual de versiones de documentos**: existe `replaces_evidence_document_id` (cadena de versiones) pero no una vista de comparación lado a lado.
2. **Generación de PDF nativa** para los 12 tipos de informe (hoy: 1 informe representativo, imprimible) — pendiente de tu decisión sobre la dependencia.
3. **Filtro de Sede en Indicadores**: deliberadamente NO se agregó. Filtrar por sede exigiría tocar `SepsisIndicatorService::baseQuery()` (el único punto de cálculo de los 6 indicadores), y la regla innegociable del proyecto es no modificar ese servicio. El filtro de Sede sí está disponible en el listado de **Casos** (que no calcula indicadores, solo lista).

Ninguno de estos pendientes rompe lo entregado; son extensiones sobre la misma arquitectura ya construida.

## 6. Decisiones técnicas

- Se reutilizó exhaustivamente lo ya construido (modelos, resources, policies, servicios) en vez de crear un segundo conjunto de entidades genéricas (`User`, `Role`, `Program`, etc.) como sugería el prompt original — ÁGORA ya tenía equivalentes funcionando.
- El catálogo de indicadores (`IndicatorDefinition`) es deliberadamente un registro documental, no un motor de cálculo: para los 6 indicadores institucionales delega en `SepsisIndicatorService` en tiempo de lectura.
- Las actas y evidencias de cierre se implementaron con `FileUpload` de Filament sobre el disco `local`, siguiendo el mismo patrón ya usado en el resto de la aplicación (sin nuevas dependencias).
