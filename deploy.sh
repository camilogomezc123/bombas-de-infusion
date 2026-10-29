#!/usr/bin/env bash
# Despliegue de ÁGORA/PICS en el VPS. Uso: bash /root/deploy-pics.sh
# Solo ejecuta lo que realmente cambió en el pull.
set -euo pipefail

APP=/var/www/pics
cd "$APP"

echo "==> git pull"
BEFORE=$(git rev-parse HEAD)
BRANCH=$(git branch --show-current)
git pull --ff-only origin "$BRANCH"
AFTER=$(git rev-parse HEAD)
CHANGED=$(git diff --name-only "$BEFORE" "$AFTER" || true)

if [ "$BEFORE" = "$AFTER" ]; then
    echo "    (sin commits nuevos: $AFTER)"
fi

# Composer solo si cambian dependencias PHP
if echo "$CHANGED" | grep -q '^composer\.lock$'; then
    echo "==> composer install (cambió composer.lock)"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
else
    echo "==> composer: sin cambios, omitido"
fi

# npm ci solo si cambian dependencias JS
if echo "$CHANGED" | grep -qE '^(package-lock\.json|package\.json)$'; then
    echo "==> npm ci (cambiaron dependencias JS)"
    npm ci
fi

# Rebuild de assets si cambió el front (vistas, css, js, config de build)
if echo "$CHANGED" | grep -qE '^(resources/|vite\.config\.js|package(-lock)?\.json)'; then
    echo "==> npm run build (cambió el front)"
    npm run build
else
    echo "==> build: sin cambios de front, omitido"
fi

# Migraciones (no-op si no hay pendientes)
echo "==> migraciones"
php artisan migrate --force

# Cachés de producción (refresca config/rutas/vistas con el código nuevo)
echo "==> cachés"
php artisan optimize

# El código no debe ser modificable por PHP; solo las carpetas de ejecución.
chown -R root:www-data "$APP"
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Recargar PHP-FPM para limpiar OPcache y servir el código nuevo
echo "==> recargar PHP-FPM"
systemctl reload php8.4-fpm

# Autoactualiza este lanzador para la próxima vez
cp -f "$APP/deploy.sh" /root/deploy-pics.sh 2>/dev/null || true

echo "✓ Deploy OK -> $(git rev-parse --short HEAD)"
