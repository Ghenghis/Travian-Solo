#!/bin/bash
#
# Database Maintenance Script
# Optimizes tables, cleans old data, updates statistics
#

set -e

# Configuration
MYSQL_HOST="${DB_HOST:-mysql}"
MYSQL_PORT="${DB_PORT:-3306}"
MYSQL_USER="${DB_USERNAME:-travian_user}"
MYSQL_PASS="${DB_PASSWORD:-travian_password123}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================="
echo "  Database Maintenance"
echo "========================================="
echo "Started: $(date)"
echo ""

# Function to run MySQL command
mysql_cmd() {
    mysql -h "${MYSQL_HOST}" \
          -P "${MYSQL_PORT}" \
          -u "${MYSQL_USER}" \
          -p"${MYSQL_PASS}" \
          "$@"
}

# Function to optimize database
optimize_database() {
    local db_name=$1
    
    echo "Optimizing ${db_name}..."
    
    # Get list of tables
    local tables=$(mysql_cmd -N -e "USE ${db_name}; SHOW TABLES;")
    local table_count=$(echo "${tables}" | wc -l)
    local current=0
    
    while IFS= read -r table; do
        ((current++))
        echo -ne "\r  Progress: ${current}/${table_count} tables (${table})                    "
        mysql_cmd -e "USE ${db_name}; OPTIMIZE TABLE \`${table}\`;" &>/dev/null || true
    done <<< "${tables}"
    
    echo ""
    echo -e "${GREEN}✓${NC} Optimized ${table_count} tables"
}

# Function to analyze database
analyze_database() {
    local db_name=$1
    
    echo "Analyzing ${db_name}..."
    
    local tables=$(mysql_cmd -N -e "USE ${db_name}; SHOW TABLES;")
    local table_count=$(echo "${tables}" | wc -l)
    local current=0
    
    while IFS= read -r table; do
        ((current++))
        echo -ne "\r  Progress: ${current}/${table_count} tables                    "
        mysql_cmd -e "USE ${db_name}; ANALYZE TABLE \`${table}\`;" &>/dev/null || true
    done <<< "${tables}"
    
    echo ""
    echo -e "${GREEN}✓${NC} Analyzed ${table_count} tables"
}

# Function to clean old sessions
clean_old_data() {
    local db_name=$1
    
    echo "Cleaning old data from ${db_name}..."
    
    # Clean old sessions (older than 7 days)
    local deleted=$(mysql_cmd -N -e "
        USE ${db_name};
        DELETE FROM login_handshake WHERE time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));
        SELECT ROW_COUNT();
    " 2>/dev/null || echo "0")
    
    echo -e "${GREEN}✓${NC} Cleaned ${deleted} old session(s)"
}

# Main maintenance
echo "=== Global Database ==="
optimize_database "travian_global"
analyze_database "travian_global"
echo ""

echo "=== Testworld Database ==="
optimize_database "travian_testworld"
analyze_database "travian_testworld"
clean_old_data "travian_testworld"
echo ""

echo "=== Demo Database ==="
optimize_database "travian_demo"
analyze_database "travian_demo"
clean_old_data "travian_demo"
echo ""

# Show database sizes
echo "=== Database Sizes ==="
mysql_cmd -e "
    SELECT 
        table_schema AS 'Database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
    FROM information_schema.tables
    WHERE table_schema IN ('travian_global', 'travian_testworld', 'travian_demo')
    GROUP BY table_schema;
"

echo ""
echo "========================================="
echo "  Maintenance Complete"
echo "========================================="
echo "Completed: $(date)"
