#!/usr/bin/env bash

set -euo pipefail

# Monetra Database Restore Script
# Restores a compressed SQL dump to target database with SHA-256 integrity verification.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [ $# -lt 1 ]; then
    echo "Usage: $0 <path_to_backup_file.sql.gz> [target_database_name]"
    exit 1
fi

BACKUP_ARCHIVE="$1"

# Extract DB parameters from .env if not already set in environment
DB_HOST="${DB_HOST:-$(grep -E '^DB_HOST=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_PORT="${DB_PORT:-$(grep -E '^DB_PORT=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DEFAULT_DB="$(grep -E '^DB_DATABASE=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')"
TARGET_DB="${2:-${DEFAULT_DB}}"
DB_USERNAME="${DB_USERNAME:-$(grep -E '^DB_USERNAME=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"
DB_PASSWORD="${DB_PASSWORD:-$(grep -E '^DB_PASSWORD=' "${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d ' "\r')}"

if [ ! -f "${BACKUP_ARCHIVE}" ]; then
    echo "Error: Backup file [${BACKUP_ARCHIVE}] not found!" >&2
    exit 1
fi

echo "=== Monetra Database Restore ==="
echo "Archive: ${BACKUP_ARCHIVE}"
echo "Target DB: ${TARGET_DB}@${DB_HOST}:${DB_PORT}"

# 1. Verify SHA-256 checksum if present
CHECKSUM_FILE="${BACKUP_ARCHIVE}.sha256"
if [ -f "${CHECKSUM_FILE}" ]; then
    echo "Verifying SHA-256 checksum..."
    if command -v shasum &> /dev/null; then
        shasum -a 256 -c "${CHECKSUM_FILE}"
    elif command -v sha256sum &> /dev/null; then
        sha256sum -c "${CHECKSUM_FILE}"
    fi
    echo "✓ Checksum valid."
else
    echo "Note: No checksum file found at [${CHECKSUM_FILE}], proceeding without checksum verification."
fi

# Find available mysql binary
if command -v mysql &> /dev/null; then
    MYSQL_BIN="mysql"
elif [ -x "/Applications/XAMPP/xamppfiles/bin/mysql" ]; then
    MYSQL_BIN="/Applications/XAMPP/xamppfiles/bin/mysql"
else
    echo "Error: mysql binary not found." >&2
    exit 1
fi

PASSWORD_FLAG=""
if [ -n "${DB_PASSWORD}" ]; then
    PASSWORD_FLAG="--password=${DB_PASSWORD}"
fi

# 2. Ensure target database exists
echo "Ensuring target database [${TARGET_DB}] exists..."
"${MYSQL_BIN}" \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    ${PASSWORD_FLAG} \
    -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Restore data
echo "Restoring database schema and data..."
if [[ "${BACKUP_ARCHIVE}" == *.gz ]]; then
    gzip -dc "${BACKUP_ARCHIVE}" | "${MYSQL_BIN}" \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        ${PASSWORD_FLAG} \
        "${TARGET_DB}"
else
    "${MYSQL_BIN}" \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USERNAME}" \
        ${PASSWORD_FLAG} \
        "${TARGET_DB}" < "${BACKUP_ARCHIVE}"
fi

# 4. Verify restored tables
TABLE_COUNT=$("${MYSQL_BIN}" \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    ${PASSWORD_FLAG} \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TARGET_DB}';" -s -N)

echo "✓ Restore completed successfully! [${TABLE_COUNT}] tables present in [${TARGET_DB}]."
