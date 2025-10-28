#!/bin/bash
#
# Database Backup Script for Travian-Solo
# Backs up all databases (global + world databases)
#

set -e

# Configuration
BACKUP_DIR="/var/www/html/backups/databases"
RETENTION_DAYS=7
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
MYSQL_HOST="${DB_HOST:-mysql}"
MYSQL_PORT="${DB_PORT:-3306}"
MYSQL_USER="${DB_USERNAME:-travian_user}"
MYSQL_PASS="${DB_PASSWORD:-travian_password123}"

# Databases to backup
DATABASES=("travian_global" "travian_testworld" "travian_demo")

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "========================================="
echo "  Travian Database Backup Script"
echo "========================================="
echo "Started: $(date)"
echo ""

# Create backup directory if it doesn't exist
mkdir -p "${BACKUP_DIR}"

# Function to backup a database
backup_database() {
    local db_name=$1
    local backup_file="${BACKUP_DIR}/${db_name}_${TIMESTAMP}.sql.gz"
    
    echo -n "Backing up ${db_name}... "
    
    if mysqldump -h "${MYSQL_HOST}" \
                 -P "${MYSQL_PORT}" \
                 -u "${MYSQL_USER}" \
                 -p"${MYSQL_PASS}" \
                 --single-transaction \
                 --routines \
                 --triggers \
                 --events \
                 "${db_name}" | gzip > "${backup_file}"; then
        
        local size=$(du -h "${backup_file}" | cut -f1)
        echo -e "${GREEN}✓${NC} (${size})"
        return 0
    else
        echo -e "${RED}✗ FAILED${NC}"
        return 1
    fi
}

# Backup each database
FAILED=0
for db in "${DATABASES[@]}"; do
    if ! backup_database "${db}"; then
        ((FAILED++))
    fi
done

echo ""
echo "Cleaning up old backups (older than ${RETENTION_DAYS} days)..."

# Remove old backups
REMOVED=$(find "${BACKUP_DIR}" -name "*.sql.gz" -type f -mtime +${RETENTION_DAYS} -delete -print | wc -l)
echo "Removed ${REMOVED} old backup(s)"

# Show backup summary
echo ""
echo "========================================="
echo "  Backup Summary"
echo "========================================="
echo "Total databases: ${#DATABASES[@]}"
echo "Successful: $((${#DATABASES[@]} - FAILED))"
echo "Failed: ${FAILED}"
echo "Backup location: ${BACKUP_DIR}"
echo ""

# List recent backups
echo "Recent backups:"
ls -lht "${BACKUP_DIR}" | head -n 10

echo ""
echo "Completed: $(date)"

exit ${FAILED}
