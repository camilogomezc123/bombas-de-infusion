#!/usr/bin/env bash

set -Eeuo pipefail

APP_NAME="agora"
DATABASE_NAME="agora"
BACKUP_ROOT="/var/backups/agora"
PASSPHRASE_FILE="/root/.config/agora-backup/passphrase"
REMOTE_ROOT="agora-drive:Respaldos ÁGORA/Datos clínicos"
TIMESTAMP="$(date '+%Y-%m-%d_%H-%M-%S')"
WORK_DIR="$(mktemp -d "${BACKUP_ROOT}/run.XXXXXX")"
DUMP_NAME="${APP_NAME}_${TIMESTAMP}.dump"
ENCRYPTED_NAME="${DUMP_NAME}.gpg"
LOG_FILE="/var/log/agora_backup.log"

cleanup() {
    rm -rf "$WORK_DIR"
}

trap cleanup EXIT
umask 077

mkdir -p "$BACKUP_ROOT"
exec 9>"/run/lock/agora-clinical-backup.lock"

if ! flock -n 9; then
    echo "$(date --iso-8601=seconds) Backup omitido: ya existe otra ejecución." >> "$LOG_FILE"
    exit 0
fi

if [[ ! -s "$PASSPHRASE_FILE" ]]; then
    echo "No se encontró la clave de cifrado en $PASSPHRASE_FILE." >&2
    exit 1
fi

echo "$(date --iso-8601=seconds) Inicio del respaldo clínico." >> "$LOG_FILE"

sudo -u postgres pg_dump \
    --format=custom \
    --no-owner \
    --no-acl \
    "$DATABASE_NAME" > "$WORK_DIR/$DUMP_NAME"

gpg \
    --batch \
    --yes \
    --pinentry-mode loopback \
    --cipher-algo AES256 \
    --compress-algo zlib \
    --passphrase-file "$PASSPHRASE_FILE" \
    --symmetric \
    --output "$WORK_DIR/$ENCRYPTED_NAME" \
    "$WORK_DIR/$DUMP_NAME"

rm -f "$WORK_DIR/$DUMP_NAME"
sha256sum "$WORK_DIR/$ENCRYPTED_NAME" > "$WORK_DIR/${ENCRYPTED_NAME}.sha256"

upload_tier() {
    local tier="$1"

    rclone copyto \
        "$WORK_DIR/$ENCRYPTED_NAME" \
        "$REMOTE_ROOT/$tier/$ENCRYPTED_NAME"
    rclone copyto \
        "$WORK_DIR/${ENCRYPTED_NAME}.sha256" \
        "$REMOTE_ROOT/$tier/${ENCRYPTED_NAME}.sha256"
}

upload_tier "diarios"

if [[ "$(date '+%u')" == "7" ]]; then
    upload_tier "semanales"
fi

if [[ "$(date '+%d')" == "01" ]]; then
    upload_tier "mensuales"
fi

LOCAL_SIZE="$(stat -c '%s' "$WORK_DIR/$ENCRYPTED_NAME")"
REMOTE_SIZE="$(rclone lsl "$REMOTE_ROOT/diarios/$ENCRYPTED_NAME" | awk '{print $1}')"

if [[ "$LOCAL_SIZE" != "$REMOTE_SIZE" ]]; then
    echo "El tamaño remoto no coincide con el archivo local." >&2
    exit 1
fi

rclone copyto \
    "$REMOTE_ROOT/diarios/$ENCRYPTED_NAME" \
    "$WORK_DIR/remote-verify.gpg"

gpg \
    --batch \
    --yes \
    --pinentry-mode loopback \
    --passphrase-file "$PASSPHRASE_FILE" \
    --decrypt \
    --output "$WORK_DIR/restore-test.dump" \
    "$WORK_DIR/remote-verify.gpg"

pg_restore --list "$WORK_DIR/restore-test.dump" > /dev/null

rclone delete "$REMOTE_ROOT/diarios" --min-age 30d
rclone delete "$REMOTE_ROOT/semanales" --min-age 84d
rclone delete "$REMOTE_ROOT/mensuales" --min-age 365d

echo "$(date --iso-8601=seconds) Respaldo completado: $ENCRYPTED_NAME ($LOCAL_SIZE bytes)." >> "$LOG_FILE"
