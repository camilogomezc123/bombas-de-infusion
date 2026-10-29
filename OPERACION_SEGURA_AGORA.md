# Operación segura de ÁGORA

## Ambientes

- Desarrollo: SQLite local, datos ficticios y correo de pruebas.
- Pruebas: base temporal recreada por CI.
- Producción: PostgreSQL, sin seeders de demostración y con almacenamiento privado.

Nunca copie `database.sqlite`, `.env`, exportaciones, evidencias clínicas ni archivos de pacientes al repositorio.

## Copias de seguridad

En producción use preferentemente el respaldo administrado del proveedor PostgreSQL, cifrado y con retención institucional. Como exportación lógica controlada:

```bash
php artisan agora:export-pg storage/app/agora_pg_data.sql
```

El archivo contiene información sensible: debe cifrarse, trasladarse a almacenamiento restringido y eliminarse del servidor después de verificarlo.

## Prueba de recuperación

1. Crear una base PostgreSQL aislada.
2. Ejecutar `php artisan migrate --force`.
3. Cargar la exportación autorizada.
4. Verificar usuarios, programas, casos, auditorías, indicadores y documentos.
5. Registrar fecha, responsable, duración y resultado.

La restauración debe probarse periódicamente; un archivo no verificado no constituye un respaldo confiable.

## Producción

- `APP_ENV=production`
- `APP_DEBUG=false`
- HTTPS obligatorio.
- Cookies seguras.
- Credenciales y claves fuera del repositorio.
- Scheduler y cola supervisados.
- Acceso mínimo por membresía de programa.
- Monitoreo de errores sin datos clínicos sensibles.
