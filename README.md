# ÁGORA

## Centro de Excelencia de Infarto

El panel `/infarto` administra el Programa Institucional de Excelencia en Síndrome Coronario Agudo e Infarto Agudo de Miocardio. Reutiliza la estructura institucional de programa, equipo, competencias, comité, recursos, documentos, estándares, evidencias, hallazgos y PHVA; sus datos clínicos permanecen aislados en tablas `acs_*`.

Incluye registro longitudinal, ruta SCA, hitos STEMI, estrategia NSTE-ACS/NSTEMI, ECG, troponinas, PCI, medicamentos documentados, complicaciones, egreso, rehabilitación, seguimientos, auditoría e indicadores iniciales.

La plataforma apoya gestión y trazabilidad; no diagnostica, prescribe ni activa procedimientos automáticamente.

El PDF ACC/AHA 2025 debe cargarse desde **Programa → Guías y documentos**. Después, el comité debe completar y aprobar `MATRIZ_ADOPCION_GUIA_ACC_AHA_2025.md`.

Los datos ficticios locales se cargan únicamente en desarrollo:

```bash
php artisan db:seed --class=AcsDemoSeeder
```

`AcsDemoSeeder.php` está excluido por `.gitignore` y se bloquea en producción.

**Analítica y Gestión Operacional para Resultados Asistenciales**

Aplicación de la Clínica de Occidente para gestionar los centros de excelencia. Incluye el centro de ACV y el **Programa Institucional de Excelencia en Sepsis y Recuperación Postsepsis** (panel `/sepsis`); SCA y Colon y Recto quedan preparados para fases posteriores.

## Funcionalidades actuales

- Inicio de sesión con usuario y contraseña.
- Roles de administrador, líder, auditor y consulta.
- Gestión de pacientes con múltiples casos y detección de recurrencias.
- Numeración incremental de casos ACV con prefijo `S`.
- Flujo de estados desde hospitalización hasta auditoría y finalización.
- Restricción de edición por auditor asignado.
- Historial de cambios por campo, usuario y fecha.
- Hilos de comentarios que pueden marcarse como resueltos.
- Importación idempotente de la base histórica ACV.
- Tablero general y tendencia mensual.
- Indicadores mensuales con semáforos de cumplimiento.
- Notificación al asignar casos y resumen semanal de los lunes.

## Requisitos

- PHP 8.3 o superior con `pdo_pgsql`, `pgsql`, `zip`, `gd`, `intl` y `mbstring`.
- PostgreSQL 17 recomendado.
- Composer 2.
- Node.js 22 y npm 10.

## Instalación

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Configura en `.env` la conexión PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agora
DB_USERNAME=agora
DB_PASSWORD=
```

El administrador inicial se crea únicamente si se definen estas variables locales:

```dotenv
AGORA_ADMIN_NAME=
AGORA_ADMIN_USERNAME=
AGORA_ADMIN_EMAIL=
AGORA_ADMIN_PASSWORD=
```

## Importar ACV

El archivo fuente no debe almacenarse en Git. Para importar:

```bash
php artisan agora:import-acv bdACV.xlsx
```

Para reconstruir pacientes y casos desde el archivo:

```bash
php artisan agora:import-acv bdACV.xlsx --fresh
```

La importación conserva todas las columnas originales dentro de `clinical_data`, normaliza los campos usados para consultas e indicadores y relaciona recurrencias por identificación.

## Tareas programadas

En producción debe mantenerse activo el scheduler:

```bash
php artisan schedule:work
```

El resumen de casos se genera cada lunes a las 7:00 a. m. en `America/Bogota`.

## Programa Institucional de Excelencia en Sepsis y Recuperación Postsepsis

Panel Filament independiente en `/sepsis`, aislado del panel ACV (`/admin`). Reutiliza el stack existente (Laravel 13, Filament 5, PHP 8.4, SQLite en desarrollo / PostgreSQL en producción) — no se agregaron dependencias nuevas.

### Arquitectura

- **Modelo de datos**: `ClinicalProgram` es la cabecera del programa; `SepsisCase` es la cabecera del episodio clínico, con tablas hijas repetibles (tamizaje, bundle, hemodinámica, cultivos, antimicrobianos, control del foco, continuidad, educación, seguimiento postsepsis).
- **Gobierno**: `ProgramCommittee` → `CommitteeMeeting` → `MeetingDecision`/`MeetingAction`, más `RaciAssignment` (matriz RACI) y `ProgramMember` (equipo, roles, competencias).
- **Calidad y mejora**: cadena `AccreditationStandard` → `MeasurableElement` → `ComplianceAssessment` → `AssessmentFinding` (con 5 Porqués y causa raíz) → `CorrectiveAction` (plan PHVA con evidencia obligatoria de cierre).
- **Indicadores**: `SepsisIndicatorService` es la **única fuente** de los 6 indicadores institucionales (bundle, PAM, mortalidad hospitalaria y a 30 días). `IndicatorDefinition` es un catálogo/ficha técnica documental que, para esos 6, **lee en vivo** el resultado del servicio anterior — nunca lo recalcula ni lo duplica. `SepsisCostService` y `SepsisSeverityService` son análisis administrativos independientes (costo, NEWS2/SOFA) que tampoco tocan esa fórmula.
- **Auditoría**: `ClinicalAudit` (polimórfico) registra cambios de campo en `SepsisCase` y en todas sus tablas hijas mediante `SepsisChildRecordObserver`.
- **Separación de capas**: Filament Resources/Pages (interfaz) → Services (`SepsisIndicatorService`, `SepsisCostService`, `SepsisSeverityService`, `ClinicalAuditService`) y Policies (reglas de negocio/autorización) → Eloquent Models (persistencia).

### Documento fuente "Codigo Sepsis 20260506.docx"

El archivo **no se encontró** en el repositorio durante esta implementación. Si lo consigues después:

1. Colócalo en una ruta local fuera de control de versiones (no debe entrar a Git — ver "Seguridad").
2. Regístralo como versión documental inicial desde **Programa → Guías y documentos** (tipo "Guía clínica", código y versión del documento).
3. Usa su contenido para completar/ajustar las definiciones, algoritmos y listas de chequeo ya precargadas como datos iniciales (ver `database/seeders/DatabaseSeeder.php`, método `seedProtocolGaps`/`seedIndicatorCatalog`/`seedPrincipalDocuments`).
4. Cualquier diferencia entre el documento real y lo ya implementado debe pasar por **Programa → Brechas y ajustes del protocolo**, para revisión y aprobación del Comité Institucional de Sepsis — el sistema no modifica recomendaciones clínicas automáticamente.

### Variables de entorno relevantes

Ninguna variable nueva obligatoria. El módulo de Sepsis usa la misma conexión de base de datos, el mismo disco `local` (`storage/app/private`) para archivos privados (actas de comité en PDF, evidencias de cierre PHVA) y el mismo `MAIL_MAILER`/`QUEUE_CONNECTION` que el resto de ÁGORA.

### Migraciones y datos iniciales

```bash
php artisan migrate
php artisan db:seed
```

`db:seed` (clase `DatabaseSeeder`) precarga, de forma idempotente (`updateOrCreate`) y **sin datos de pacientes**: la matriz de armonización del protocolo (10 puntos), la ficha técnica de indicadores (los 6 institucionales enlazados en vivo + indicadores de estructura/proceso/experiencia adicionales), los 14 documentos principales (metadatos, sin archivo cargado), la matriz RACI y el catálogo de competencias por rol.

Para datos de **demostración** (ficticios, exclusivamente locales, nunca en producción):

```bash
php artisan db:seed --class=GovernanceDemoSeeder
```

Crea 5 usuarios demostrativos (contraseña `Demo2026!`), un comité con reuniones y actas, estándares/evidencias de ejemplo, y un caso con sus 5 seguimientos postsepsis (48-72h, 7d, 15d, 30d, 90d).

### Ejecución local

```bash
.\serve.ps1
```

Luego entra a `http://127.0.0.1:8000/sepsis` (requiere una cuenta con membresía activa en el programa Sepsis, o rol Administrador).

### Roles del programa

Además de los roles globales (Administrador, Líder, Auditor, Seguimiento, Consulta), el programa de Sepsis tiene sus propios roles (`App\Enums\ProgramRole`), asignables desde **Programa → Equipo y competencias**:

| Rol | Alcance |
|---|---|
| Líder del programa | Acceso completo |
| Líder clínico | Acceso completo |
| Coordinador del programa | Acceso completo (gestión integral) |
| Dirección | Solo lectura + gobierno |
| Médico / Enfermería | Registro y edición de casos, sin auditoría ni aprobación |
| Auditor | Casos asignados + auditoría |
| Gestor de calidad | Estándares, evidencias, hallazgos, seguridad |
| Analista de datos | Lectura de casos e indicadores, registro de datos |
| Miembro del comité | Programa, comité, reuniones |
| Consulta | Solo lectura |

### Pendientes conocidos (ver `IMPLEMENTACION_PROGRAMA_SEPSIS.md`)

Multi-sede, generación de informes PDF/CSV, temporizadores/semáforos en vivo (JS) desde tiempo cero, y un tablero de "Inicio" con la totalidad de gráficas descritas en la especificación original quedan documentados como trabajo futuro — no se simuló ni se dejó a medias ninguna funcionalidad expuesta en la interfaz.

## Programa Institucional de Excelencia en ICU Liberation, Recuperación del Paciente Crítico y Prevención del Síndrome Post-UCI

Panel Filament independiente en `/icu-liberation`. Reutiliza el mismo stack y, sobre todo, la misma capa de gobierno/calidad/documentos/competencias/indicadores ya compartida entre Sepsis, Infarto y TEP (`ClinicalProgram`, `ProgramMember`, `ProgramCommittee`, `RaciAssignment`, `ProgramResource`, `EvidenceDocument`, `ClinicalRule`, `AccreditationStandard`→`MeasurableElement`→`ComplianceAssessment`→`AssessmentFinding`→`CorrectiveAction`, `IndicatorDefinition`, `Competency`/`StaffCompetency`) — ningún modelo de gobierno se duplicó.

> ⚠️ **Advertencia clínica permanente** (visible en el panel): esta plataforma apoya la gestión, trazabilidad y evaluación del Programa ICU Liberation. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados. No prescribe, no calcula dosis para administración real, no ordena suspensión de sedantes ni extubación, no ejecuta pruebas SAT/SBT automáticamente y no diagnostica delirium — solo registra lo que el equipo interdisciplinario ya decidió y evaluó.

### Arquitectura

- **Modelo de datos**: `IcuUnit` (unidad crítica) y `IcuStay` (cabecera de la estancia, equivalente a `SepsisCase`/`TepCase`), con 12 tablas hijas repetibles para el bundle ABCDEF + sueño (`IcuPainAssessment`, `IcuSatTrial`, `IcuSbtTrial`, `IcuSedationAssessment`, `IcuDeliriumAssessment`, `IcuMobilitySession`, `IcuFamilyEngagement`, `IcuSleepAssessment`, `IcuPhysicalRestraint`, `IcuDeviceReview`, `IcuPicsFollowup`, `IcuStayAudit`), más `IcuLiberationRound` (ronda diaria) e `IcuTransferChecklist` (checklist de traslado y entrega estructurada, 1:1 con la estancia).
- **Gobierno, calidad, documentos, RACI, reglas clínicas, competencias e indicadores**: 100% reutilizados de la capa compartida (`App\Filament\Sepsis\Resources\*` y `App\Filament\Programs\Shared\Resources\ClinicalRules`), simplemente reescopeados al código de programa `ICULIB` mediante `ProgramContext`/`ProgramAccess::currentCode()`.
- **Indicadores**: `IcuLiberationIndicatorService` (análogo a `TepIndicatorService`/`SepsisIndicatorService`) es la única fuente de los indicadores en vivo (cumplimiento del bundle por oportunidades, dolor evaluado, SAT/SBT realizado entre ventilados, objetivo de sedación documentado, evaluación/prevalencia de delirium, movilidad realizada, días de ventilación y estancia en UCI, mortalidad en UCI/hospitalaria, reintubación, autoextubación, tasa de seguimiento post-UCI). El resto del catálogo institucional completo (secciones EST-ICUL/A-F/SLP/RES-ICUL/PICS/BAL de la especificación) queda **catalogado** en `IndicatorDefinition` como referencia documental, pendiente de fuente automática o de aprobación de meta tras construir línea base — nunca se le atribuyó una meta que la especificación no definiera explícitamente.
- **Reglas clínicas configurables**: catálogos de exclusión de SAT/SBT, selección de escala de dolor, herramienta de delirium y criterios de movilidad viven en `ClinicalRule` en estado `draft`, pendientes de aprobación institucional antes de activarse como reglas asistenciales (sección 4 del programa).
- **Auditoría de casos**: `IcuStayAudit` (por estancia, con bandera `requires_phva` que remite al plan de mejoramiento genérico en Calidad y mejora → Hallazgos y acciones correctivas — el mismo motor PHVA/5-porqués de Sepsis/TEP, sin duplicar).
- **Censo y ronda**: `Censo UCI` (semáforo verde/amarillo/rojo por cumplimiento del bundle del día — la escala completa de 6 colores de la especificación, incluidos azul "programado" y morado "requiere decisión interdisciplinaria", queda documentada como siguiente fase) y `Ronda ICU Liberation` (herramienta diaria con "mapa cerebral": RASS/SAS objetivo vs. real, CAM-ICU/ICDSC, barreras para la vigilia, plan de ajuste).

### Documentos fuente

Ninguno de los 4 documentos solicitados (`ICU Liberation - Patricia Rosa, Jaspal Singh, Jo.pdf`, `JCI 4ta edición.pdf`, `Manual-acreditacion-salud-Hospit y ambulatorio_V 3.1 (1).pdf`, `Codigo Sepsis 20260506.docx`) se encontró en el repositorio durante esta implementación (búsqueda por nombre de archivo en todo el árbol de trabajo). Se continuó la implementación con datos demostrativos y reglas en borrador, según lo instruido. Si consigues alguno después:

1. Colócalo fuera de control de versiones (no debe entrar a Git).
2. Regístralo en **Programa ICU Liberation → Guías y documentos** (código institucional, versión, tipo).
3. Usa su contenido para completar/ajustar las `ClinicalRule` en borrador (criterios de exclusión SAT/SBT, escalas) — cualquier cambio a una regla asistencial requiere aprobación institucional antes de activarse (`status: draft → approved`), nunca se activa automáticamente.
4. La matriz de adopción formal (documento → capítulo → recomendación → adaptación institucional) descrita en la especificación queda documentada como pendiente en `MATRIZ_ADOPCION_ICU_LIBERATION.md` — a construir cuando existan los documentos fuente reales.

### Migraciones y datos iniciales

```bash
php artisan migrate
```

La migración `2026_07_27_040000_create_icu_liberation_program_tables.php` es autocontenida (mismo patrón que TEP): crea el registro `ClinicalProgram` (código `ICULIB`), la ficha técnica de indicadores (en vivo + catalogados) y las reglas clínicas en borrador directamente en su `up()`, además de las 17 tablas nuevas. `down()` revierte todo, incluidas las filas sembradas.

Para datos de **demostración** (ficticios, exclusivamente locales, nunca en producción):

```bash
php artisan db:seed --class=IcuLiberationDemoSeeder
```

Crea 3 unidades críticas (adultos/cardiovascular/quirúrgica), 20 pacientes/estancias distribuidas en 6 meses (10 ventilados, 8 con SAT, 7 con SBT, 5 con delirium positivo, 10 movilizados, 3 con restricción física activa, 10 con familiar registrado, 5 con seguimiento PICS, 3 auditorías de caso), y 6 documentos institucionales en borrador. El listado completo de 15 documentos, 20 competencias de personal y 3 actas de comité de la sección 48 de la especificación queda como alcance pendiente (ver `MAPA_FUNCIONAL_ICU_LIBERATION.md`).

### Ejecución local

```bash
.\serve.ps1
```

Luego entra a `http://127.0.0.1:8000/icu-liberation` (requiere una cuenta con membresía activa en el programa ICU Liberation, o rol Administrador).

### Alcance de esta primera versión funcional

Implementado: gobierno clínico (reutilizado), censo UCI, ronda ICU Liberation, bundle ABCDEF completo por estancia (dolor, SAT, SBT, sedación, delirium, movilidad, familia, sueño, restricciones, dispositivos), indicadores en vivo, dashboard ejecutivo, auditoría de casos, PHVA (reutilizado), traslado/egreso con checklist estructurado, y seguimiento post-UCI (PICS).

Pendiente para la siguiente fase (no simulado, documentado explícitamente): semáforo completo de 6 colores en el censo, comparaciones entre unidades/turnos, control estadístico de procesos, exportación PDF/CSV dedicada, módulo pediátrico (deliberadamente desactivado), investigación/docencia, y la matriz de adopción documental formal una vez se incorporen los documentos fuente reales.

## Pruebas

```bash
php artisan test
npm run build
```

Si el PHP global no tiene `pdo_sqlite`, utiliza una configuración local que habilite la extensión o ejecuta las pruebas sobre PostgreSQL.

## Seguridad

No deben entrar al repositorio:

- `.env` y credenciales.
- Archivos Excel con información clínica.
- Bases SQLite o respaldos PostgreSQL.
- Archivos importados y logs.

Los casos se anulan lógicamente y permanecen en el historial, pero se excluyen de los indicadores.
