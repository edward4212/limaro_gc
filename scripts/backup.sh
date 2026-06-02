#!/bin/bash
# ============================================================
# Backup automático — Limaro SGC / COOPAIPE
# ============================================================
# Instalar en crontab:
#   crontab -e
#   # Backup diario a las 2:00 AM
#   0 2 * * * /home/limarocloud/limaro.limaro.cloud/scripts/backup.sh >> /home/limarocloud/backups/backup.log 2>&1
#
# Requiere: mysqldump, tar, gzip
# Opcional: instalar rclone para sincronizar a Google Drive/S3
# ============================================================

set -euo pipefail

# ── Configuración ──────────────────────────────────────────────────────
DB_HOST="localhost"
DB_NAME="limarocloud_limaro"
DB_USER="limarocloud"
DB_PASS=""                         # ← Completar o usar ~/.my.cnf
APP_ROOT="/home/limarocloud/limaro.limaro.cloud"
BACKUP_DIR="/home/limarocloud/backups"
RETENTION_DAYS=30                  # Días que se guardan los backups

# ── Setup ──────────────────────────────────────────────────────────────
FECHA=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="${BACKUP_DIR}/${FECHA}"
LOG="${BACKUP_DIR}/backup.log"

mkdir -p "${BACKUP_PATH}"
echo "[${FECHA}] Iniciando backup..." | tee -a "${LOG}"

# ── 1. Dump de la base de datos ────────────────────────────────────────
echo "  [BD] Exportando ${DB_NAME}..." | tee -a "${LOG}"
mysqldump \
    --host="${DB_HOST}" \
    --user="${DB_USER}" \
    --password="${DB_PASS}" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    "${DB_NAME}" | gzip > "${BACKUP_PATH}/db_${DB_NAME}_${FECHA}.sql.gz"

BD_SIZE=$(du -sh "${BACKUP_PATH}/db_${DB_NAME}_${FECHA}.sql.gz" | cut -f1)
echo "  [BD] OK — Tamaño: ${BD_SIZE}" | tee -a "${LOG}"

# ── 2. Backup de archivos de storage ──────────────────────────────────
STORAGE="${APP_ROOT}/public/storage"
if [ -d "${STORAGE}" ]; then
    echo "  [Storage] Comprimiendo ${STORAGE}..." | tee -a "${LOG}"
    tar -czf "${BACKUP_PATH}/storage_${FECHA}.tar.gz" \
        -C "${APP_ROOT}/public" storage 2>/dev/null
    ST_SIZE=$(du -sh "${BACKUP_PATH}/storage_${FECHA}.tar.gz" | cut -f1)
    echo "  [Storage] OK — Tamaño: ${ST_SIZE}" | tee -a "${LOG}"
else
    echo "  [Storage] WARN: carpeta storage no encontrada" | tee -a "${LOG}"
fi

# ── 3. Comprimir todo el backup del día ───────────────────────────────
FINAL_ZIP="${BACKUP_DIR}/limaro_backup_${FECHA}.tar.gz"
tar -czf "${FINAL_ZIP}" -C "${BACKUP_DIR}" "${FECHA}/"
rm -rf "${BACKUP_PATH}"
TOTAL_SIZE=$(du -sh "${FINAL_ZIP}" | cut -f1)
echo "  [ZIP] Backup final: ${FINAL_ZIP} (${TOTAL_SIZE})" | tee -a "${LOG}"

# ── 4. Limpieza de backups antiguos ───────────────────────────────────
echo "  [Limpieza] Eliminando backups de más de ${RETENTION_DAYS} días..." | tee -a "${LOG}"
find "${BACKUP_DIR}" -name "limaro_backup_*.tar.gz" -mtime +${RETENTION_DAYS} -delete
QUEDAN=$(find "${BACKUP_DIR}" -name "limaro_backup_*.tar.gz" | wc -l)
echo "  [Limpieza] Backups disponibles: ${QUEDAN}" | tee -a "${LOG}"

# ── 5. Sync a remoto (opcional — requiere rclone configurado) ──────────
# Descomenta si tienes rclone instalado y configurado:
# REMOTE="gdrive:Limaro-Backups"
# if command -v rclone &>/dev/null; then
#     echo "  [Rclone] Sincronizando a ${REMOTE}..." | tee -a "${LOG}"
#     rclone copy "${FINAL_ZIP}" "${REMOTE}/" --log-level ERROR
#     echo "  [Rclone] OK" | tee -a "${LOG}"
# fi

echo "[${FECHA}] Backup completado exitosamente." | tee -a "${LOG}"
echo "─────────────────────────────────────────" >> "${LOG}"
