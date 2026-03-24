#!/usr/bin/env bash

set -euo pipefail

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_DATABASE="coin"
DB_USERNAME="root"
DB_PASSWORD=""

BACKUP_DIR="$HOME/Downloads"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/${DB_DATABASE}_${TIMESTAMP}.sql"

mkdir -p "$BACKUP_DIR"

echo "Backing up '${DB_DATABASE}' → ${BACKUP_FILE}"

mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    ${DB_PASSWORD:+--password="$DB_PASSWORD"} \
    --single-transaction \
    --routines \
    --triggers \
    --set-gtid-purged=OFF \
    "$DB_DATABASE" > "$BACKUP_FILE"

echo "Done. $(du -h "$BACKUP_FILE" | cut -f1)"
