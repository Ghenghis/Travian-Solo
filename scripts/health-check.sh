#!/bin/bash
#
# Health Check Script for Travian-Solo
# Checks all services are running and healthy
#

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

CHECKS_PASSED=0
CHECKS_FAILED=0

echo "========================================="
echo "  Travian Health Check"
echo "========================================="
echo "Time: $(date)"
echo ""

# Function to check service
check_service() {
    local service_name=$1
    local check_command=$2
    
    echo -n "Checking ${service_name}... "
    
    if eval "${check_command}" &>/dev/null; then
        echo -e "${GREEN}✓ OK${NC}"
        ((CHECKS_PASSED++))
        return 0
    else
        echo -e "${RED}✗ FAILED${NC}"
        ((CHECKS_FAILED++))
        return 1
    fi
}

# 1. Check Docker containers
echo "=== Docker Containers ==="
check_service "MySQL Container" "docker-compose ps mysql | grep -q 'Up'"
check_service "Redis Container" "docker-compose ps redis | grep -q 'Up'"
check_service "PHP Container" "docker-compose ps php | grep -q 'Up'"
check_service "Nginx Container" "docker-compose ps nginx | grep -q 'Up'"
echo ""

# 2. Check Database connectivity
echo "=== Database Connectivity ==="
check_service "MySQL Connection" "docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'SELECT 1' &>/dev/null"
check_service "Global Database" "docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'USE travian_global; SELECT COUNT(*) FROM gameServers' &>/dev/null"
check_service "Testworld Database" "docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'USE travian_testworld; SELECT COUNT(*) FROM users' &>/dev/null"
check_service "Demo Database" "docker-compose exec -T mysql mysql -u travian_user -ptravian_password123 -e 'USE travian_demo; SELECT COUNT(*) FROM users' &>/dev/null"
echo ""

# 3. Check Redis
echo "=== Redis Connectivity ==="
check_service "Redis Connection" "docker-compose exec -T redis redis-cli ping | grep -q 'PONG'"
check_service "Redis Memory" "docker-compose exec -T redis redis-cli info memory &>/dev/null"
echo ""

# 4. Check Nginx
echo "=== Web Server ==="
check_service "Nginx Running" "docker-compose exec -T nginx nginx -t &>/dev/null"
check_service "HTTP Response" "curl -s -o /dev/null -w '%{http_code}' http://localhost | grep -q '200\|301\|302'"
echo ""

# 5. Check Disk Space
echo "=== Disk Space ==="
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "${DISK_USAGE}" -lt 80 ]; then
    echo -e "Disk Usage: ${GREEN}${DISK_USAGE}% ✓${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "Disk Usage: ${RED}${DISK_USAGE}% ✗ (>80%)${NC}"
    ((CHECKS_FAILED++))
fi
echo ""

# 6. Check Memory
echo "=== Memory Usage ==="
MEM_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100}')
if [ "${MEM_USAGE}" -lt 90 ]; then
    echo -e "Memory Usage: ${GREEN}${MEM_USAGE}% ✓${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "Memory Usage: ${RED}${MEM_USAGE}% ✗ (>90%)${NC}"
    ((CHECKS_FAILED++))
fi
echo ""

# Summary
echo "========================================="
echo "  Health Check Summary"
echo "========================================="
echo "Checks Passed: ${CHECKS_PASSED}"
echo "Checks Failed: ${CHECKS_FAILED}"
echo ""

if [ ${CHECKS_FAILED} -eq 0 ]; then
    echo -e "${GREEN}✓ ALL SYSTEMS OPERATIONAL${NC}"
    exit 0
else
    echo -e "${RED}✗ SOME CHECKS FAILED${NC}"
    exit 1
fi
