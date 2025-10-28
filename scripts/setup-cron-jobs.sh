#!/bin/bash
#
# Automated Cron Jobs Setup Script
# Sets up automated tasks without user prompts
#

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================="
echo "  Cron Jobs Setup Script"
echo "========================================="
echo "Started: $(date)"
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "Project root: ${PROJECT_ROOT}"
echo "Scripts directory: ${SCRIPT_DIR}"
echo ""

# Check if running with proper permissions
if [ "$EUID" -ne 0 ] && ! crontab -l >/dev/null 2>&1; then 
    echo "Warning: You may need sudo access to modify crontab"
    echo "Try running with sudo if you encounter permission errors"
    echo ""
fi

# Backup existing crontab
echo "=== Backing Up Existing Crontab ==="
BACKUP_FILE="${HOME}/crontab_backup_$(date +%Y%m%d_%H%M%S).txt"

if crontab -l >/dev/null 2>&1; then
    crontab -l > "${BACKUP_FILE}"
    echo -e "${GREEN}✓${NC} Existing crontab backed up to: ${BACKUP_FILE}"
else
    echo "No existing crontab found (this is normal for new installations)"
fi
echo ""

# Create temporary cron file
TEMP_CRON=$(mktemp)

# Add existing cron jobs if any
if crontab -l >/dev/null 2>&1; then
    crontab -l > "${TEMP_CRON}"
fi

# Remove any existing Travian-Solo cron jobs to avoid duplicates
sed -i.bak '/# Travian-Solo:/d' "${TEMP_CRON}" 2>/dev/null || true
sed -i.bak '/backup-databases.sh/d' "${TEMP_CRON}" 2>/dev/null || true
sed -i.bak '/health-check.sh/d' "${TEMP_CRON}" 2>/dev/null || true
sed -i.bak '/db-maintenance.sh/d' "${TEMP_CRON}" 2>/dev/null || true

echo "=== Adding Travian-Solo Cron Jobs ==="
echo ""

# Define cron jobs
cat >> "${TEMP_CRON}" <<EOF

# Travian-Solo: Automated Tasks
# Added on: $(date)

# Daily database backups at 2:00 AM
0 2 * * * cd ${PROJECT_ROOT} && docker-compose exec -T php bash /var/www/html/scripts/backup-databases.sh >> ${PROJECT_ROOT}/logs/backup.log 2>&1

# Health checks every 15 minutes
*/15 * * * * cd ${PROJECT_ROOT} && docker-compose exec -T php bash /var/www/html/scripts/health-check.sh >> ${PROJECT_ROOT}/logs/health.log 2>&1

# Weekly database maintenance on Sundays at 3:00 AM
0 3 * * 0 cd ${PROJECT_ROOT} && docker-compose exec -T php bash /var/www/html/scripts/db-maintenance.sh >> ${PROJECT_ROOT}/logs/maintenance.log 2>&1

EOF

# Install the new crontab
echo "Installing cron jobs..."
crontab "${TEMP_CRON}"
rm "${TEMP_CRON}"

echo -e "${GREEN}✓${NC} Cron jobs installed successfully"
echo ""

# Create log directory if it doesn't exist
LOG_DIR="${PROJECT_ROOT}/logs"
mkdir -p "${LOG_DIR}"
echo -e "${GREEN}✓${NC} Log directory created: ${LOG_DIR}"
echo ""

# Set up log rotation
echo "=== Setting Up Log Rotation ==="

LOGROTATE_CONF="/etc/logrotate.d/travian-solo"

if [ -w "/etc/logrotate.d" ] || [ "$EUID" -eq 0 ]; then
    cat > "${LOGROTATE_CONF}" <<EOF
${LOG_DIR}/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 $(whoami) $(whoami)
    sharedscripts
    postrotate
        # Optional: reload services if needed
    endscript
}
EOF
    echo -e "${GREEN}✓${NC} Log rotation configured: ${LOGROTATE_CONF}"
else
    echo -e "${YELLOW}⚠${NC} Could not create log rotation config (need sudo)"
    echo "  Run with sudo to enable automatic log rotation"
    echo "  Or manually create: ${LOGROTATE_CONF}"
fi
echo ""

# Display installed cron jobs
echo "=== Installed Cron Jobs ==="
echo ""
crontab -l | grep -A 10 "Travian-Solo:" || echo "No cron jobs found"
echo ""

# Create a monitoring script
MONITOR_SCRIPT="${SCRIPT_DIR}/check-cron-status.sh"
cat > "${MONITOR_SCRIPT}" <<'EOF'
#!/bin/bash
# Check status of Travian-Solo cron jobs

echo "========================================="
echo "  Cron Jobs Status"
echo "========================================="
echo ""

# Check if cron service is running
if systemctl is-active --quiet cron 2>/dev/null || systemctl is-active --quiet crond 2>/dev/null; then
    echo "✓ Cron service is running"
else
    echo "✗ Cron service is not running"
fi
echo ""

# List Travian-Solo cron jobs
echo "Travian-Solo cron jobs:"
crontab -l | grep -A 10 "Travian-Solo:" || echo "No cron jobs found"
echo ""

# Check recent log files
echo "Recent log files:"
ls -lht logs/*.log 2>/dev/null | head -n 5 || echo "No log files found"
echo ""

# Check last backup
if [ -d "backups/databases" ]; then
    echo "Last backup:"
    ls -lht backups/databases/*.sql.gz 2>/dev/null | head -n 1 || echo "No backups found"
else
    echo "No backup directory found"
fi
EOF

chmod +x "${MONITOR_SCRIPT}"
echo -e "${GREEN}✓${NC} Monitoring script created: ${MONITOR_SCRIPT}"
echo ""

# Summary
echo "========================================="
echo "  Setup Complete!"
echo "========================================="
echo ""
echo "Cron jobs installed:"
echo "  1. Database Backup  - Daily at 2:00 AM"
echo "  2. Health Check     - Every 15 minutes"
echo "  3. DB Maintenance   - Weekly on Sunday at 3:00 AM"
echo ""
echo "Log files location:"
echo "  ${LOG_DIR}/"
echo ""
echo "Backup location:"
echo "  Previous crontab: ${BACKUP_FILE}"
echo ""
echo "Monitoring:"
echo "  Run: ${MONITOR_SCRIPT}"
echo "  to check cron status anytime"
echo ""
echo "To view cron jobs:"
echo "  crontab -l"
echo ""
echo "To remove cron jobs:"
echo "  crontab -e"
echo "  (then delete the Travian-Solo section)"
echo ""
echo "Completed: $(date)"
echo "========================================="
