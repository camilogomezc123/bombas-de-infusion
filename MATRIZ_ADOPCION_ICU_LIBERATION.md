# Matriz de adopción y adaptación institucional — ICU Liberation

## Estado de los documentos fuente

Ninguno de los siguientes documentos se encontró en el repositorio durante esta implementación (búsqueda por nombre de archivo en todo el árbol de trabajo, `*.pdf`/`*.docx`):

| Documento | Estado |
|---|---|
| `ICU Liberation - Patricia Rosa, Jaspal Singh, Jo.pdf` | No disponible |
| `JCI 4ta edición.pdf` | No disponible |
| `Manual-acreditacion-salud-Hospit y ambulatorio_V 3.1 (1).pdf` | No disponible |
| `Codigo Sepsis 20260506.docx` | No disponible (mismo estado que en la implementación del módulo de Sepsis) |

Por esta razón, la matriz de adopción formal (documento → capítulo → página → recomendación parafraseada → adaptación institucional) **no pudo construirse con contenido real** en esta ronda. Lo que sí se implementó, siguiendo la instrucción de continuar sin los documentos:

- Las 5 reglas clínicas configurables (`ClinicalRule`, tabla `clinical_rules`) que representan los puntos de la especificación que más claramente requieren una fuente documental antes de activarse.
- El catálogo completo de indicadores institucionales de las secciones 23-35 de la especificación (en vivo + catalogados), citando la fuente genérica "ICU Liberation / guía institucional" hasta que se incorpore el documento real.

## Estructura de la matriz (a completar cuando existan los documentos)

| Documento | Capítulo | Tema | Recomendación/práctica (parafraseada) | Población | Aplicabilidad | Herramienta | Adaptación institucional | Indicador | Evidencia requerida | Responsable | Estado | Fecha de aprobación | Fecha de revisión |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| *(pendiente)* | | | | | | | | | | | Pendiente | | |

Estados válidos: `Adoptada`, `Adaptada`, `No aplicable`, `Pendiente`, `En evaluación`, `Retirada` (mismo vocabulario de la especificación).

## Reglas clínicas ya registradas en estado `draft` (pendientes de esta matriz)

| Código | Regla | Fuente citada |
|---|---|---|
| `ICUL-SAT-01` | Criterios de exclusión para SAT | ICU Liberation / guía institucional pendiente de incorporar |
| `ICUL-SBT-01` | Criterios de exclusión para SBT | ICU Liberation / guía institucional pendiente de incorporar |
| `ICUL-PAIN-01` | Selección de escala de dolor según capacidad de autorreporte | ICU Liberation / guía institucional pendiente de incorporar |
| `ICUL-DELIRIUM-01` | Herramienta institucional para evaluación de delirium | ICU Liberation / guía institucional pendiente de incorporar |
| `ICUL-MOBILITY-01` | Criterios de seguridad para movilidad temprana | ICU Liberation / guía institucional pendiente de incorporar |

## Procedimiento para cuando se incorporen los documentos

1. Colocar el archivo fuera de control de versiones (no debe entrar a Git).
2. Registrarlo en **Programa ICU Liberation → Guías y documentos**, con código institucional, versión, capítulo/sección de referencia.
3. Extraer (parafraseado, sin copiar fragmentos extensos protegidos) título, capítulo, tema y recomendación de cada práctica relevante, y completar una fila de esta matriz por recomendación.
4. Actualizar la `ClinicalRule` correspondiente: `source_document`, `source_section`, `configuration` (parámetros concretos de la regla), y dejarla en `draft`.
5. Presentar al Comité Institucional ICU Liberation para aprobación clínica (`status: draft → approved`, con `approved_by`/`approved_at`). **Ninguna regla se activa automáticamente** — la aprobación es siempre un acto humano y documentado.
6. Solo tras la aprobación, la regla puede citarse como criterio institucional vinculante en la ronda, el censo o la auditoría de casos.
