#!/bin/bash
#
# Basic Monitoring Setup Script
# Sets up simple monitoring with email alerts (optional)
#

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "========================================="
echo "  Monitoring Setup Script"
echo "========================================="
echo "Started: $(date)"
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Create monitoring directory
MONITOR_DIR="${PROJECT_ROOT}/monitoring"
mkdir -p "${MONITOR_DIR}"

echo "Monitoring directory: ${MONITOR_DIR}"
echo ""

# Create alert script
echo "=== Creating Alert Script ==="

ALERT_SCRIPT="${SCRIPT_DIR}/send-alert.sh"
cat > "${ALERT_SCRIPT}" <<'ALERT_EOF'
#!/bin/bash
# Send alert notifications

ALERT_EMAIL="${ADMIN_EMAIL:-admin@localhost}"
ALERT_TYPE="${1:-info}"
ALERT_MESSAGE="${2:-No message provided}"
PROJECT_NAME="Travian-Solo"

# Colors for terminal output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m'

# Log alert
LOG_DIR="$(dirname "$(dirname "$0")")/logs"
mkdir -p "${LOG_DIR}"
ALERT_LOG="${LOG_DIR}/alerts.log"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] [${ALERT_TYPE}] ${ALERT_MESSAGE}" >> "${ALERT_LOG}"

# Console output
case "${ALERT_TYPE}" in
    error|critical)
        echo -e "${RED}✗ ALERT [${ALERT_TYPE}]:${NC} ${ALERT_MESSAGE}"
        ;;
    warning)
        echo -e "${YELLOW}⚠ WARNING:${NC} ${ALERT_MESSAGE}"
        ;;
    *)
        echo -e "${GREEN}✓ INFO:${NC} ${ALERT_MESSAGE}"
        ;;
esac

# Send email if configured (requires mail command)
if command -v mail >/dev/null 2>&1 && [ -n "${ALERT_EMAIL}" ] && [ "${ALERT_EMAIL}" != "admin@localhost" ]; then
    SUBJECT="${PROJECT_NAME} Alert: ${ALERT_TYPE}"
    BODY="Alert Type: ${ALERT_TYPE}
Time: $(date)
Message: ${ALERT_MESSAGE}

Server: $(hostname)
Project: ${PROJECT_NAME}

This is an automated alert from your Travian-Solo monitoring system."
    
    echo "${BODY}" | mail -s "${SUBJECT}" "${ALERT_EMAIL}" 2>/dev/null || true
fi
ALERT_EOF

chmod +x "${ALERT_SCRIPT}"
echo -e "${GREEN}✓${NC} Alert script created: ${ALERT_SCRIPT}"
echo ""

# Create monitoring wrapper
echo "=== Creating Monitoring Wrapper ==="

MONITOR_WRAPPER="${SCRIPT_DIR}/monitor-with-alerts.sh"
cat > "${MONITOR_WRAPPER}" <<'MONITOR_EOF'
#!/bin/bash
# Wrapper for health check with alerting

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
HEALTH_SCRIPT="${SCRIPT_DIR}/health-check.sh"
ALERT_SCRIPT="${SCRIPT_DIR}/send-alert.sh"

# Run health check
if bash "${HEALTH_SCRIPT}"; then
    # All checks passed
    exit 0
else
    # Some checks failed
    bash "${ALERT_SCRIPT}" "error" "Health check failed! Check logs for details."
    exit 1
fi
MONITOR_EOF

chmod +x "${MONITOR_WRAPPER}"
echo -e "${GREEN}✓${NC} Monitoring wrapper created: ${MONITOR_WRAPPER}"
echo ""

# Create metrics collector
echo "=== Creating Metrics Collector ==="

METRICS_SCRIPT="${SCRIPT_DIR}/collect-metrics.sh"
cat > "${METRICS_SCRIPT}" <<'METRICS_EOF'
#!/bin/bash
# Collect and log system metrics

METRICS_DIR="$(dirname "$(dirname "$0")")/monitoring/metrics"
mkdir -p "${METRICS_DIR}"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
METRICS_FILE="${METRICS_DIR}/metrics_${TIMESTAMP}.json"

# Collect metrics
cat > "${METRICS_FILE}" <<EOF
{
  "timestamp": "$(date -Iseconds)",
  "system": {
    "cpu_usage": "$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)",
    "memory_usage": "$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')",
    "disk_usage": "$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')",
    "load_average": "$(uptime | awk -F'load average:' '{print $2}')"
  },
  "docker": {
    "containers_running": "$(docker-compose ps --services --filter "status=running" | wc -l)",
    "containers_total": "$(docker-compose ps --services | wc -l)"
  },
  "database": {
    "size_global": "$(docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = "travian_global"' -N 2>/dev/null || echo '0')",
    "size_testworld": "$(docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = "travian_testworld"' -N 2>/dev/null || echo '0')",
    "size_demo": "$(docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = "travian_demo"' -N 2>/dev/null || echo '0')"
  },
  "redis": {
    "memory_used": "$(docker-compose exec -T redis redis-cli info memory | grep used_memory_human | cut -d':' -f2 | tr -d '\r' || echo 'N/A')",
    "connected_clients": "$(docker-compose exec -T redis redis-cli info clients | grep connected_clients | cut -d':' -f2 | tr -d '\r' || echo 'N/A')"
  }
}
EOF

echo "Metrics collected: ${METRICS_FILE}"

# Cleanup old metrics (keep last 7 days)
find "${METRICS_DIR}" -name "metrics_*.json" -mtime +7 -delete 2>/dev/null || true
METRICS_EOF

chmod +x "${METRICS_SCRIPT}"
echo -e "${GREEN}✓${NC} Metrics collector created: ${METRICS_SCRIPT}"
echo ""

# Create dashboard script
echo "=== Creating Dashboard Script ==="

DASHBOARD_SCRIPT="${SCRIPT_DIR}/dashboard.sh"
cat > "${DASHBOARD_SCRIPT}" <<'DASH_EOF'
#!/bin/bash
# Simple monitoring dashboard

clear

echo "========================================="
echo "  TRAVIAN-SOLO MONITORING DASHBOARD"
echo "========================================="
echo "Updated: $(date)"
echo ""

# Docker Containers
echo "=== Docker Containers ==="
docker-compose ps
echo ""

# System Resources
echo "=== System Resources ==="
echo "CPU Usage:    $(top -bn1 | grep "Cpu(s)" | awk '{print $2}')"
echo "Memory Usage: $(free -h | grep Mem | awk '{print $3 "/" $2}')"
echo "Disk Usage:   $(df -h / | awk 'NR==2 {print $5 " (" $3 "/" $2 ")"}')"
echo ""

# Database Status
echo "=== Database Status ==="
docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema IN ('travian_global', 'travian_testworld', 'travian_demo') GROUP BY table_schema" 2>/dev/null || echo "Database connection failed"
echo ""

# Redis Status
echo "=== Redis Status ==="
echo "Memory: $(docker-compose exec -T redis redis-cli info memory | grep used_memory_human | cut -d':' -f2 | tr -d '\r')"
echo "Clients: $(docker-compose exec -T redis redis-cli info clients | grep connected_clients | cut -d':' -f2 | tr -d '\r')"
echo ""

# Recent Logs
echo "=== Recent Alerts ==="
if [ -f "logs/alerts.log" ]; then
    tail -n 5 logs/alerts.log || echo "No recent alerts"
else
    echo "No alerts log file"
fi
echo ""

# Backups
echo "=== Recent Backups ==="
if [ -d "backups/databases" ]; then
    ls -lht backups/databases/*.sql.gz 2>/dev/null | head -n 3 || echo "No backups found"
else
    echo "No backup directory"
fi
echo ""

echo "========================================="
echo "Press Ctrl+C to exit, or wait 30 seconds for refresh..."
echo "========================================="

sleep 30
exec "$0"
DASH_EOF

chmod +x "${DASHBOARD_SCRIPT}"
echo -e "${GREEN}✓${NC} Dashboard script created: ${DASHBOARD_SCRIPT}"
echo ""

# Create configuration file
echo "=== Creating Configuration ==="

CONFIG_FILE="${MONITOR_DIR}/monitoring.conf"
cat > "${CONFIG_FILE}" <<EOF
# Monitoring Configuration
# Edit this file to customize monitoring settings

# Email for alerts (leave blank to disable email alerts)
ADMIN_EMAIL=""

# Alert thresholds
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
DISK_THRESHOLD=80

# Monitoring intervals (in minutes)
HEALTH_CHECK_INTERVAL=15
METRICS_COLLECTION_INTERVAL=60

# Retention periods (in days)
METRICS_RETENTION=7
LOG_RETENTION=14
BACKUP_RETENTION=7
EOF

echo -e "${GREEN}✓${NC} Configuration file created: ${CONFIG_FILE}"
echo ""

# Summary
echo "========================================="
echo "  Monitoring Setup Complete!"
echo "========================================="
echo ""
echo "Created scripts:"
echo "  1. ${ALERT_SCRIPT}"
echo "  2. ${MONITOR_WRAPPER}"
echo "  3. ${METRICS_SCRIPT}"
echo "  4. ${DASHBOARD_SCRIPT}"
echo ""
echo "Configuration:"
echo "  ${CONFIG_FILE}"
echo ""
echo "Usage:"
echo ""
echo "  View dashboard:"
echo "    bash ${DASHBOARD_SCRIPT}"
echo ""
echo "  Run health check with alerts:"
echo "    bash ${MONITOR_WRAPPER}"
echo ""
echo "  Collect metrics:"
echo "    bash ${METRICS_SCRIPT}"
echo ""
echo "  Configure alerts:"
echo "    Edit ${CONFIG_FILE}"
echo "    Set ADMIN_EMAIL to receive email notifications"
echo ""
echo "To enable email alerts:"
echo "  1. Edit ${CONFIG_FILE}"
echo "  2. Set ADMIN_EMAIL=your@email.com"
echo "  3. Install 'mailutils' package:"
echo "     sudo apt-get install mailutils"
echo ""
echo "Completed: $(date)"
echo "========================================="
