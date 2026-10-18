#!/usr/bin/env bash

set -euo pipefail

# Monetra Database Backup Script
# Creates a compressed SQL dump with SHA-256 checksum verification.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${PROJECT_DIR}/backups"
TIMESTAMP="$(date +"%Y%m%d_%H%M%S")"

mkdir -p "${BACKUP_DIR}"

# Extract DB parameters from .env if not already set in environment
DB_HOST="${DB_HOST:-$(grep -E '^DB_HOST=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_PORT="${DB_PORT:-$(grep -E '^DB_PORT=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_DATABASE="${DB_DATABASE:-$(grep -E '^DB_DATABASE=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_USERNAME="${DB_USERNAME:-$(grep -E '^DB_USERNAME=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_PASSWORD="${DB_PASSWORD:-$(grep -E '^DB_PASSWORD=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"

BACKUP_FILE="${BACKUP_DIR}/monetra_${DB_DATABASE}_${TIMESTAMP}.sql"

echo "=== Monetra Database Backup ==="
echo "Target DB: ${DB_DATABASE}@${DB_HOST}:${DB_PORT}"
echo "Output: ${BACKUP_FILE}.gz"

# Find available dump binary (mysqldump or mariadb-dump)
if command -v mysqldump &> /dev/null; then
    DUMP_BIN="mysqldump"
elif command -v mariadb-dump &> /dev/null; then
    DUMP_BIN="mariadb-dump"
elif [ -x "/Applications/XAMPP/xamppfiles/bin/mysqldump" ]; then
    DUMP_BIN="/Applications/XAMPP/xamppfiles/bin/mysqldump"
else
    echo "Error: Neither mysqldump nor mariadb-dump was found." >&2
    exit 1
fi

PASSWORD_FLAG=""
if [ -n "${DB_PASSWORD}" ]; then
    PASSWORD_FLAG="--password=${DB_PASSWORD}"
fi

"${DUMP_BIN}" \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    ${PASSWORD_FLAG} \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    "${DB_DATABASE}" > "${BACKUP_FILE}"

# Compress
gzip -c "${BACKUP_FILE}" > "${BACKUP_FILE}.gz"
rm -f "${BACKUP_FILE}"

# Compute SHA256 checksum
if command -v shasum &> /dev/null; then
    shasum -a 256 "${BACKUP_FILE}.gz" > "${BACKUP_FILE}.gz.sha256"
elif command -v sha256sum &> /dev/null; then
    sha256sum "${BACKUP_FILE}.gz" > "${BACKUP_FILE}.gz.sha256"
fi

echo "✓ Backup successfully created:"
echo "  Archive:  ${BACKUP_FILE}.gz"
echo "  Checksum: ${BACKUP_FILE}.gz.sha256"
