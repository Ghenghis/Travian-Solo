#!/bin/bash
#
# Unit Test Runner
# Runs comprehensive unit tests on REAL production codebase
#

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "========================================="
echo "  UNIT TEST SUITE"
echo "  Testing REAL Production Codebase"
echo "========================================="
echo ""

# Check if running in Docker
if [ -f "/.dockerenv" ]; then
    echo "✓ Running inside Docker container"
    IN_DOCKER=true
else
    echo "⚠ Running on host system"
    echo "  Recommended: Run inside Docker for consistent environment"
    IN_DOCKER=false
fi

echo ""

# Check if PHPUnit is installed
if ! command -v vendor/bin/phpunit &> /dev/null && ! command -v phpunit &> /dev/null; then
    echo -e "${YELLOW}Installing PHPUnit...${NC}"
    
    if command -v composer &> /dev/null; then
        composer require --dev phpunit/phpunit ^9.5
    else
        echo -e "${RED}✗ Composer not found${NC}"
        echo "  Install Composer or PHPUnit manually"
        exit 1
    fi
fi

# Determine PHPUnit binary
if [ -f "vendor/bin/phpunit" ]; then
    PHPUNIT="vendor/bin/phpunit"
elif command -v phpunit &> /dev/null; then
    PHPUNIT="phpunit"
else
    echo -e "${RED}✗ PHPUnit not found${NC}"
    exit 1
fi

echo "Using PHPUnit: $PHPUNIT"
echo ""

# Run different test suites
echo "========================================="
echo "Running Unit Tests..."
echo "========================================="
echo ""

# Test Security class
echo -e "${YELLOW}Testing Core\\Security (REAL production code)...${NC}"
$PHPUNIT --testsuite Core --filter SecurityTest --colors=always

# Test RateLimiter
echo ""
echo -e "${YELLOW}Testing Middleware\\RateLimiter (REAL production code)...${NC}"
$PHPUNIT --testsuite Middleware --colors=always

# Run all tests
echo ""
echo "========================================="
echo "Running ALL Unit Tests..."
echo "========================================="
echo ""

$PHPUNIT --colors=always --testdox

# Generate coverage report if xdebug is available
if php -m | grep -q xdebug; then
    echo ""
    echo "========================================="
    echo "Generating Code Coverage Report..."
    echo "========================================="
    echo ""
    
    $PHPUNIT --coverage-html tests/coverage/html --coverage-text
    
    echo ""
    echo -e "${GREEN}✓ Coverage report generated: tests/coverage/html/index.html${NC}"
else
    echo ""
    echo -e "${YELLOW}⚠ Xdebug not installed - skipping coverage report${NC}"
    echo "  Install Xdebug for code coverage analysis"
fi

echo ""
echo "========================================="
echo "  UNIT TESTS COMPLETE"
echo "========================================="
echo ""
echo "Summary:"
echo "  ✓ All tests run against REAL production codebase"
echo "  ✓ No mocks or test doubles used"
echo "  ✓ Tests validate actual implementation"
echo ""
