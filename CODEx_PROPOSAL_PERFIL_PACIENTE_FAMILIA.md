# Propuesta complementaria Codex: perfil del paciente y familia

## Objetivo

Complementar la propuesta del sistema con una visión más integral del paciente y su familia, centrada en la atención clínica, la continuidad del cuidado, la comunicación y la participación del cuidador en la toma de decisiones.

---

## Idea general

El perfil del paciente no debe verse como un registro aislado de datos clínicos, sino como una vista central del cuidado, donde convergen:

- información clínica
- historia del caso
- evolución y seguimiento
- datos demográficos
- contacto de familiares y cuidadores
- consentimiento e información
- educación / comprensión del paciente
- apoyo familiar y red de cuidado
- alertas y necesidades especiales

Esto permite que el equipo interdisciplinario tenga contexto completo para decidir, comunicar y acompañar adecuadamente al paciente y a su familia.

---

## Estructura propuesta visual

```text
┌────────────────────────────────────────────────────────────────────────────┐
│ PERFIL DEL PACIENTE / FAMILIA                                             │
├────────────────────────────────────────────────────────────────────────────┤
│ [Resumen] [Clínico] [Familia] [Educación] [Consentimientos] [Seguimiento] │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Datos básicos                                                          │
│  - Nombre: Ana García                                                    │
│  - Edad: 62 años                                                         │
│  - Documento: 12345678                                                   │
│  - Servicio: Medicina Interna                                            │
│  - Estado: Activo                                                       │
│  - Diagnóstico principal: Sepsis postoperatoria                          │
│  - Riesgo: Alto                                                          │
│  - Fecha de ingreso: 12/09/2026                                          │
│                                                                          │
│  Familia / cuidador principal                                            │
│  - Nombre: María García                                                  │
│  - Relación: Hija y cuidadora principal                                  │
│  - Teléfono: 300 123 4567                                                │
│  - Contacto de emergencia: Sí                                            │
│  - Participa en decisiones: Sí                                            │
│  - Idioma: Español                                                       │
│  - Observación: Requiere información clara y explicación verbal breve     │
│                                                                          │
│  Educación y comunicación                                                 │
│  - Idioma de comunicación: Español                                       │
│  - Necesidad de apoyo para comprensión: Sí                               │
│  - Material recomendado: Visual + verbal                                 │
│  - Participación del familiar: Activa                                     │
│                                                                          │
│  Consentimientos                                                          │
│  - Consentimiento informado: Sí                                           │
│  - Autorización para compartir información: Sí                           │
│  - Observaciones: Familia involucrada en la atención                      │
│                                                                          │
└────────────────────────────────────────────────────────────────────────────┘
```

---

## Propuesta de contenido por sección

### 1. Resumen del paciente

Debe mostrar de forma rápida:

- nombre completo
- edad / sexo
- servicio / unidad
- diagnóstico principal
- estado actual
- ingreso y evolución
- riesgo y prioridad del caso

### 2. Datos clínicos

Sección enfocada en la atención y seguimiento:

- antecedentes relevantes
- diagnósticos activos
- escalas y controles
- medicamentos clave
- procedimientos
- resultados relevantes
- seguimiento médico

### 3. Familia y cuidadores

Aquí se debe incluir la información del apoyo cercano al paciente:

- cuidador principal
- relación con el paciente
- teléfono de contacto
- dirección o barrio si aplica
- participación en decisiones
- nivel de acompañamiento
- observaciones importantes

### 4. Comunicación y educación

Esta sección ayuda a ajustar la información a las necesidades de la familia:

- idioma de preferencia
- nivel de comprensión
- necesidad de apoyo visual / verbal
- barreras de comunicación
- material educativo indicado
- persona responsable de la información

### 5. Consentimientos y autorizaciones

Se recomienda registrar:

- consentimiento informado
- autorización de familiares para recibir información
- permisos de tratamiento o procedimientos relevantes
- restricciones de divulgación
- contacto autorizado

### 6. Seguimiento y continuidad

Sección para hacer seguimiento de la atención y la continuidad del cuidado:

- citas / controles
- seguimiento postalta
- educación entregada
- evolución de la familia / cuidador
- alertas de continuidad o riesgo

---

## Cómo se ve la diferencia con un perfil clásico

### Perfil clásico

```text
Nombre
Edad
Diagnóstico
Servicio
Fecha de ingreso
Estado
```

### Perfil propuesto por Codex

```text
Nombre
Edad
Diagnóstico
Servicio
Estado
Familiar principal
Contacto del cuidador
Idiomas / comunicación
Consentimientos
Educación entregada
Apoyo familiar
Riesgo y continuidad de cuidado
```

La diferencia es clara: el modelo clásico registra el caso, mientras que el modelo propuesto registra a la persona y su entorno de cuidado.

---

## Ejemplo de bloque familiar

```text
FAMILIA / CUIDADORES
────────────────────
- Nombre: María García
- Relación: Hija
- Teléfono: 300 123 4567
- Rol: Cuidadora principal
- Participa en decisiones: Sí
- Idioma: Español
- Necesidad de apoyo: Explicación clara y pausada
- Observación: Acompaña todas las visitas y solicita actualización semanal
```

---

## Lo que propone Codex en términos de funcionalidad

No es solo una mejor vista visual. La propuesta implica que el sistema:

- registre a la familia como parte del cuidado
- diferencie paciente, cuidador y contacto de emergencia
- facilite la comunicación con la familia
- soporte decisiones compartidas
- reduzca la pérdida de información contextual
- mejore continuidad de cuidado entre profesionales
- haga más visible la carga emocional y de apoyo familiar

---

## Recomendación final

El perfil del paciente debería ser una vista híbrida entre:

- historial clínico
- información del caso
- arquitectura del cuidado
- red de apoyo familiar
- consentimiento y educación
- continuidad del tratamiento

Es decir, una visión que refleje no solo lo médico, sino también lo humano y operativo del cuidado.

---

## Mensaje ejecutivo breve

> El perfil del paciente y la familia debe pasar de ser un registro administrativo a ser un centro de cuidado, donde la información clínica, la red de apoyo y la continuidad del tratamiento se integren para mejorar la atención y la comunicación con la familia.

---

## Sugerencia para subir y editar

Puedes guardar este archivo como:

- `CODEx_PROPOSAL_PERFIL_PACIENTE_FAMILIA.md`

Y luego abrirlo en VS Code para:

- ajustar términos técnicos
- ampliar secciones
- convertirlo en una propuesta más formal para comité o dirección
- darle formato visual con títulos, tablas o bloques de ejemplo

---

## Versión resumida para presentación

```text
La propuesta del perfil del paciente y familia consiste en un módulo integrado que combina:

- datos clínicos del paciente
- información del caso y evolución
- familia y cuidador principal
- contacto de emergencia
- comunicación y educación
- consentimiento informado
- seguimiento y continuidad de cuidado

La idea es que el sistema no solo registre al paciente, sino también su entorno de cuidado, la familia, sus necesidades de información y su participación en la atención.
```
