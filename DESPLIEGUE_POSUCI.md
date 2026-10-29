# Guía de despliegue — POSUCI 360 Conecta

Pasos concretos para llevar el portal de recuperación (paciente/familia) de este entorno de desarrollo local a un servidor real, con pacientes reales. No repite lo que ya está en `README.md` (arquitectura general de ÁGORA/PICS) — se enfoca en lo que hace falta específicamente para que **funcionen de verdad** las piezas construidas para POSUCI 360 Conecta: notificaciones (correo, panel, push), el scheduler, y HTTPS.

## 1. Requisitos del servidor

- PHP 8.3 o superior, con las extensiones: `pdo_pgsql`, `pgsql`, `openssl`, `curl`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`.
  - `openssl` es indispensable — sin ella, las notificaciones push (VAPID/Web Push) no funcionan en absoluto.
- PostgreSQL 17 (recomendado — ver `README.md`).
- Composer 2.
- Node.js 22 y npm 10 (solo para compilar los assets de Vite una vez; no se necesitan en tiempo de ejecución).
- **Un certificado HTTPS válido** (Let's Encrypt/Certbot es gratis y suficiente). Esto no es opcional: los service workers y las notificaciones push del portal (`/manifest.json`, `/sw.js`, suscripción push) **solo funcionan sobre HTTPS real** — en este entorno de desarrollo funcionan porque `localhost`/`127.0.0.1` son excepciones especiales de los navegadores, pero un dominio real sin HTTPS los deja rotos silenciosamente.

## 2. Instalación inicial

```bash
git clone <repo> posuci360
cd posuci360
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

## 3. Variables de entorno — checklist completo

Edita `.env` con los valores reales de producción:

```dotenv
APP_NAME="POSUCI 360 Conecta"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio-real.com

DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="notificaciones@tu-dominio-real.com"
MAIL_FROM_NAME="POSUCI 360 Conecta"

VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT="mailto:soporte@tu-dominio-real.com"

AGORA_ADMIN_NAME="Administrador POSUCI"
AGORA_ADMIN_USERNAME=admin
AGORA_ADMIN_EMAIL=...
AGORA_ADMIN_PASSWORD=...
```

**`APP_DEBUG=false` es crítico**: con `true`, cualquier error de la aplicación le muestra a un visitante el stack trace completo (rutas del servidor, consultas SQL, variables de entorno parcialmente visibles). Nunca debe quedar en `true` en producción.

### Generar las llaves VAPID de producción

No reutilices las llaves de desarrollo. Genera un par nuevo en el servidor real:

```bash
php artisan agora:generate-vapid-keys
```

Copia el `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` que imprime a tu `.env`.

## 4. Base de datos y administrador inicial

```bash
php artisan migrate --force
php artisan db:seed --force
```

`db:seed` (clase `DatabaseSeeder`) crea el usuario administrador **solo si** definiste las variables `AGORA_ADMIN_*` en el paso anterior — no antes de eso. El administrador queda con `must_change_password = true`: la primera vez que entre, el sistema lo obliga a poner una contraseña propia.

**Nunca** corras `GovernanceDemoSeeder`, `AcsDemoSeeder`, `PosuciDemoSeeder` ni ningún seeder que termine en `*Demo*` en producción — están excluidos de Git intencionalmente y contienen datos ficticios de prueba, no reales.

## 5. Servidor web (ejemplo con nginx)

El *document root* debe apuntar a `public/`, nunca a la raíz del proyecto:

```nginx
server {
    listen 443 ssl http2;
    server_name tu-dominio-real.com;
    root /var/www/posuci360/public;

    ssl_certificate     /etc/letsencrypt/live/tu-dominio-real.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio-real.com/privkey.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

server {
    listen 80;
    server_name tu-dominio-real.com;
    return 301 https://$host$request_uri;
}
```

Permisos de escritura para el usuario del servidor web:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 6. Queue worker — obligatorio, sin excepción

**Toda notificación de este proyecto usa `ShouldQueue`**: la alerta al equipo por inactividad, el aviso a la familia si el paciente no entró, la respuesta a una solicitud de ayuda, la confirmación de una cita. Sin un queue worker corriendo, estas notificaciones quedan encoladas en la tabla `jobs` **y nunca se entregan** — el portal sigue funcionando con normalidad, pero nadie recibe avisos, y no hay ningún error visible que lo delate. Esto no es opcional para que el proyecto cumpla su propósito.

Con `supervisor` (Linux):

```ini
[program:posuci360-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/posuci360/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/posuci360
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/posuci360/storage/logs/queue-worker.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start posuci360-queue:*
```

## 7. Scheduler — necesario para las alertas automáticas

Tres procesos de POSUCI dependen del scheduler de Laravel (`routes/console.php`):

| Comando | Hora (America/Bogota) | Qué hace |
|---|---|---|
| `agora:check-portal-inactivity` | 7:30 a.m. | Avisa al equipo clínico si un caso lleva 15+ días sin actividad de portal |
| `agora:notify-caregiver-of-inactive-patient-today` | 8:00 p.m. | Avisa al cuidador si el paciente no entró al portal hoy |
| `agora:send-daily-portal-push` | 8:00 a.m. | Manda el recordatorio matutino a quien activó las notificaciones push |

Agrega una única línea a crontab (`crontab -e`):

```cron
* * * * * cd /var/www/posuci360 && php artisan schedule:run >> /dev/null 2>&1
```

Laravel decide internamente, cada minuto, si es el momento de correr cada tarea — no hay que programar cada comando por separado.

## 8. Optimización para producción

Después de cada despliegue (código nuevo):

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

`queue:restart` es importante: sin él, los workers ya corriendo siguen usando el código *viejo* hasta que se reinicien solos por `max-time`.

## 9. Respaldos

La base de datos contiene información clínica real. Como mínimo:

```bash
pg_dump -Fc posuci360 > /respaldos/posuci360_$(date +%F).dump
```

Automatízalo con cron (fuera del horario pico) y guarda los respaldos en un lugar distinto al servidor de la aplicación.

## 10. Checklist final antes de abrir a pacientes reales

- [ ] `APP_DEBUG=false` y `APP_ENV=production`.
- [ ] HTTPS activo y el certificado es válido (no autofirmado) — pruébalo entrando desde un celular real, no solo desde el servidor.
- [ ] El queue worker está corriendo (`supervisorctl status`) y el cron del scheduler está activo.
- [ ] Un correo real de prueba llega a una bandeja de entrada real (no solo al log) — invita a un usuario de prueba y confirma que el correo de invitación llega.
- [ ] Las llaves VAPID son las de producción, generadas en el servidor real, no las de desarrollo.
- [ ] Con un teléfono real: instalar el portal ("📲 Instalar app"), activar notificaciones ("🔔 Activar notificaciones") y confirmar que llega un push real (puedes forzarlo corriendo `php artisan agora:send-daily-portal-push` manualmente después de suscribirte).
- [ ] Se hizo al menos una prueba de extremo a extremo con datos reales de prueba (crear un caso, invitar paciente y familiar, usar el portal, responder una solicitud) — igual a la que se corrió en desarrollo antes de escribir esta guía.
- [ ] Hay un respaldo automático de la base de datos configurado y probado (restaurarlo una vez, en un ambiente aparte, para confirmar que el respaldo sí sirve).
- [ ] El administrador inicial cambió su contraseña temporal (se lo pide el sistema automáticamente al primer ingreso).
