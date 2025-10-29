# Create compatibility views to map legacy table names to T4.4 schema tables
# villages    -> vdata
# alliances   -> alidata
# marketplace -> market

$MYSQL_CONTAINER = "travian-mysql"
$MYSQL_ROOT_PASSWORD = "root_password123"

Write-Host "=== Creating compatibility views in travian_testworld ===" -ForegroundColor Cyan
$sqlTestworld = @"
USE travian_testworld;
DROP VIEW IF EXISTS `villages`;
CREATE VIEW `villages` AS SELECT * FROM `vdata`;
DROP VIEW IF EXISTS `alliances`;
CREATE VIEW `alliances` AS SELECT * FROM `alidata`;
DROP VIEW IF EXISTS `marketplace`;
CREATE VIEW `marketplace` AS SELECT * FROM `market`;
"@

$sqlTestworld | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD
if ($LASTEXITCODE -ne 0) { Write-Host "✗ Failed to create views in travian_testworld" -ForegroundColor Red; exit 1 }
Write-Host "✓ Views created in travian_testworld" -ForegroundColor Green

Write-Host "=== Creating compatibility views in travian_demo ===" -ForegroundColor Cyan
$sqlDemo = @"
USE travian_demo;
DROP VIEW IF EXISTS `villages`;
CREATE VIEW `villages` AS SELECT * FROM `vdata`;
DROP VIEW IF EXISTS `alliances`;
CREATE VIEW `alliances` AS SELECT * FROM `alidata`;
DROP VIEW IF EXISTS `marketplace`;
CREATE VIEW `marketplace` AS SELECT * FROM `market`;
"@

$sqlDemo | docker exec -i $MYSQL_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD
if ($LASTEXITCODE -ne 0) { Write-Host "✗ Failed to create views in travian_demo" -ForegroundColor Red; exit 1 }
Write-Host "✓ Views created in travian_demo" -ForegroundColor Green

Write-Host "=== Compatibility views created successfully ===" -ForegroundColor Green
