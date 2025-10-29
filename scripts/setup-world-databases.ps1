# Setup World Databases - Phase FIX-02
# Creates travian_testworld and travian_demo databases and imports schema

Write-Host "=== FIX-02: Game World Database Setup ===" -ForegroundColor Cyan
Write-Host ""

$MYSQL_CONTAINER = "travian-mysql"
$MYSQL_ROOT_PASSWORD = "root_password123"
$SCHEMA_FILE = "main_script/include/schema/T4.4.sql"

# Step 1: Create world databases
Write-Host "Step 1: Creating world databases..." -ForegroundColor Yellow

$createDbs = @"
CREATE DATABASE IF NOT EXISTS travian_testworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS travian_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES LIKE 'travian%';
"@

$createDbs | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Databases created successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to create databases" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 2: Import schema to testworld
Write-Host "Step 2: Importing schema to travian_testworld (90+ tables)..." -ForegroundColor Yellow

Get-Content $SCHEMA_FILE | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD travian_testworld

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Schema imported to testworld" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to import to testworld" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 3: Import schema to demo
Write-Host "Step 3: Importing schema to travian_demo (90+ tables)..." -ForegroundColor Yellow

Get-Content $SCHEMA_FILE | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD travian_demo

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Schema imported to demo" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to import to demo" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 4: Verify table counts
Write-Host "Step 4: Verifying table counts..." -ForegroundColor Yellow

$verifyQuery = @"
SELECT 
    'testworld' as world,
    COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'travian_testworld'
UNION ALL
SELECT 
    'demo' as world,
    COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'travian_demo';
"@

$verifyQuery | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD

Write-Host ""

# Step 5: Grant permissions
Write-Host "Step 5: Granting permissions to travian_user..." -ForegroundColor Yellow

$grantPerms = @"
GRANT ALL PRIVILEGES ON travian_testworld.* TO 'travian_user'@'%';
GRANT ALL PRIVILEGES ON travian_demo.* TO 'travian_user'@'%';
FLUSH PRIVILEGES;
"@

$grantPerms | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Permissions granted" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to grant permissions" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== World Databases Setup Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Update gameServers.configFileLocation paths"
Write-Host "  2. Run test-world-connection.php"
Write-Host "  3. Proceed to FIX-03 (Login/Registration Testing)"
