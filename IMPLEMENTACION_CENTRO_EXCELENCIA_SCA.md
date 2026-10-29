# Implementación del Centro de Excelencia de SCA

## Arquitectura

El centro funciona como un panel Filament independiente en `/infarto`. Usa el mismo inicio de sesión y el control de acceso por membresía de programa.

Se reutilizan sin duplicar `ClinicalProgram`, equipo, competencias, comité, actas, recursos, documentos, estándares, evidencias, hallazgos, acciones correctivas, PHVA, sedes, pacientes, usuarios y permisos.

Las entidades clínicas específicas usan el prefijo `acs_`: casos, ECG, troponina, PCI, medicamentos, complicaciones, egreso, rehabilitación, seguimiento y auditoría.

## Reglas iniciales

- ECG interpretado en máximo 10 minutos.
- FMC–primer dispositivo máximo 90 minutos en ingreso directo.
- FMC–primer dispositivo máximo 120 minutos en traslado.
- Angiografía máximo 24 horas en NSTE-ACS marcado con estrategia invasiva temprana.

Estas reglas calculan cumplimiento para gestión. No emiten órdenes ni decisiones clínicas.

## Seguridad y compatibilidad

El acceso usa `ProgramAccess` con el código `INFARTO`. Los administradores conservan acceso institucional; los demás usuarios requieren membresía activa y permisos del programa.

Las migraciones usan Schema Builder y Eloquent, sin SQL específico de SQLite. Son compatibles con SQLite y PostgreSQL.

## Pendientes controlados

- Cargar y revisar el PDF ACC/AHA 2025.
- Completar la matriz de adopción con página, COR y LOE verificados.
- Parametrizar instituciones remitentes y red regional.
- Aprobar un modelo institucional antes de calcular mortalidad ajustada por riesgo.
- Completar indicadores avanzados de equidad y experiencia tras construir línea base.
- Validar con cardiología, hemodinamia, urgencias, rehabilitación, calidad y seguridad del paciente.
