# Diccionario inicial de indicadores SCA

| Código | Indicador | Numerador | Denominador | Meta inicial | Fuente |
|---|---|---|---|---|---|
| PROC-01 | ECG interpretado ≤10 min | Casos elegibles dentro de 10 min | Casos con FMC/ingreso y ECG interpretado | ≥90%, configurable | Caso SCA |
| STEMI-01 | FMC-dispositivo ≤90 min, directo | STEMI directos dentro de 90 min | STEMI directos con tiempos completos | ≥90%, configurable | Caso/PCI |
| STEMI-02 | FMC-dispositivo ≤120 min, traslado | STEMI trasladados dentro de 120 min | STEMI trasladados con tiempos completos | ≥90%, configurable | Caso/PCI |
| STEMI-03 | Mediana FMC-dispositivo | No aplica | STEMI con ambos tiempos | Línea base | Caso/PCI |
| NSTE-07 | Angiografía ≤24 h en estrategia temprana | Elegibles dentro de 24 h | NSTE-ACS/NSTEMI con estrategia temprana y tiempos completos | ≥90%, configurable | Caso SCA |
| EGR-02 | Remisión a rehabilitación | Egresados con remisión | Casos egresados con plan disponible | ≥85%, configurable | Plan de egreso |
| RES-01 | Mortalidad hospitalaria | Fallecidos durante hospitalización | Casos SCA válidos | Línea base y análisis ajustado | Caso SCA |
| RES-06 | MACE a 30 días | Casos con MACE a 30 días | Casos con seguimiento disponible | Línea base | Seguimiento |
| RES-17 | Reingreso a 30 días | Reingresos a 30 días | Casos con seguimiento disponible | Línea base | Seguimiento |

Las inclusiones y exclusiones quedan auditables mediante `is_valid`, `is_cancelled` y `exclusion_reason`. Antes del uso institucional, el comité debe aprobar definiciones, fuentes, exclusiones y metas.
