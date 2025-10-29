param(
    [switch]$Apply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$IsDryRun = -not $Apply.IsPresent
$RepoRoot = Split-Path $PSScriptRoot -Parent

function Write-Info($msg) { Write-Host "[info] $msg" -ForegroundColor Cyan }
function Write-Run($msg) {
    if ($IsDryRun) { Write-Host "[dry-run] $msg" -ForegroundColor Yellow }
    else { Write-Host "[run] $msg" -ForegroundColor Green }
}
function Write-OK($msg) { Write-Host "[OK] $msg" -ForegroundColor Green }

Push-Location $RepoRoot
try {
    Write-Info "Repo root: $RepoRoot"
    if ($IsDryRun) {
        Write-Info "DRY-RUN mode (use -Apply to execute)"
    }

    # Define single parent folder for organization
    $ProjectFolder = Join-Path $RepoRoot '_project'
    
    # Subfolders under _project
    $Folders = @{
        'testing'     = Join-Path $ProjectFolder 'testing'
        'admin'       = Join-Path $ProjectFolder 'admin'
        'archive'     = Join-Path $ProjectFolder 'archive-docs'
        'temp'        = Join-Path $ProjectFolder 'temp'
    }

    # Create folder structure
    Write-Info "Creating organizational folders under _project/"
    foreach ($folder in $Folders.Values) {
        Write-Run "mkdir $folder"
        if (-not $IsDryRun -and -not (Test-Path $folder)) {
            New-Item -ItemType Directory -Path $folder -Force | Out-Null
        }
    }

    # Files to move - organized by category
    $Moves = @{
        # Test scripts → _project/testing/
        'testing' = @(
            'check-activation-schema.php',
            'check-all-databases.php',
            'check-databases.php',
            'check-registered-users.php',
            'check-server-activation.php',
            'check-servers.php',
            'check-testworld-users.php',
            'check-world-activation.php',
            'check-world-tables.php',
            'test-activate-api.php',
            'test-activate-detailed.php',
            'test-activation-flow.php',
            'test-and-check.php',
            'test-db-class.php',
            'test-db-connection.php',
            'test-db-debug.php',
            'test-db-direct.php',
            'test-email-system.php',
            'test-login-flow.php',
            'test-login.php',
            'test-mysql-connection.php',
            'test-redis-production.php',
            'test-registration-container.php',
            'test-registration-with-email.php',
            'test-registration.php',
            'test-response-detail.php',
            'test-security-features.php',
            'test-serverdb.php',
            'test-web-activation-flow.php',
            'test-which-server.php',
            'verify-world-tables.php'
        )
        # Admin/setup scripts → _project/admin/
        'admin' = @(
            'configure-smtp.php',
            'create-newsletter-table.php',
            'dbbackup.php',
            'fix-activation-table.php',
            'import-world-schema.php',
            'list-all-tables.php',
            'setup-demo-world.php',
            'setup-world-files.php'
        )
        # Old status/summary docs → _project/archive-docs/
        'archive' = @(
            'FINAL-COMPLETION-GUIDE.md',
            'HONEST-PROJECT-STATUS.md',
            'PHASE3-COMPLETE.md',
            'PHASE4-COMPLETE.md',
            'PRODUCTION-DEPLOYMENT.md',
            'PROJECT-COMPLETE.md',
            'QUICK-START.md',
            'REDIS-PRODUCTION-GUIDE.md',
            'SESSION-SUMMARY.md',
            'STEP3-SUMMARY.md',
            'TODO.md',
            'UNIT-TESTING-GUIDE.md',
            'cleanup-TODO.md'
        )
        # Temp files → _project/temp/ (or delete)
        'temp' = @(
            'COMMIT_MSG.txt',
            'COMMIT_MSG_2.txt',
            'COMMIT_MSG_3.txt',
            'registration-result.json'
        )
    }

    # Move files
    foreach ($category in $Moves.Keys) {
        $destFolder = $Folders[$category]
        Write-Info "Moving $category files → _project/$category/"
        
        foreach ($file in $Moves[$category]) {
            $source = Join-Path $RepoRoot $file
            $dest = Join-Path $destFolder $file
            
            if (Test-Path $source) {
                Write-Run "mv $file → _project/$category/"
                if (-not $IsDryRun) {
                    Move-Item -Path $source -Destination $dest -Force
                }
            }
        }
    }

    # Move inventory and schema to existing folders
    Write-Info "Moving data files to existing folders"
    
    $DataMoves = @{
        'repo-files.txt' = 'reports/repo-files.txt'
        'main.sql'       = 'database/main.sql'
    }
    
    foreach ($file in $DataMoves.Keys) {
        $source = Join-Path $RepoRoot $file
        $dest = Join-Path $RepoRoot $DataMoves[$file]
        if (Test-Path $source) {
            Write-Run "mv $file → $($DataMoves[$file])"
            if (-not $IsDryRun) {
                Move-Item -Path $source -Destination $dest -Force
            }
        }
    }

    # Delete cache files (safe to regenerate)
    Write-Info "Cleaning cache files"
    $CacheFiles = @('.phpunit.result.cache')
    foreach ($file in $CacheFiles) {
        $path = Join-Path $RepoRoot $file
        if (Test-Path $path) {
            Write-Run "rm $file"
            if (-not $IsDryRun) {
                Remove-Item -Path $path -Force
            }
        }
    }

    # Create README in _project to explain structure
    $readmePath = Join-Path $ProjectFolder 'README.md'
    Write-Run "Creating _project/README.md"
    if (-not $IsDryRun) {
        $readmeContent = @"
# Project Organization Folder

This folder contains files moved from the root directory to keep the repository clean and organized.

## Structure

- **testing/** - Test and diagnostic PHP scripts
- **admin/** - Administrative and setup scripts
- **archive-docs/** - Historical documentation and status files
- **temp/** - Temporary files (can be safely deleted)

## Usage

These files are no longer in the root directory. If you have scripts or documentation that reference them, update the paths accordingly.

Example:
- Old: ``php test-mysql-connection.php``
- New: ``php _project/testing/test-mysql-connection.php``

For current documentation, see the ``docs/`` folder in the repository root.
"@
        Set-Content -Path $readmePath -Value $readmeContent -Encoding UTF8
    }

    if ($IsDryRun) {
        Write-Info "DRY-RUN complete. Review changes above."
        Write-Info "Run with -Apply to execute moves."
    } else {
        Write-OK "Root cleanup complete!"
        Write-Info "Verifying with tests..."
        
        # Run tests to ensure nothing broke
        $testCmd = 'docker-compose exec php vendor/bin/phpunit --testdox'
        Write-Run $testCmd
        iex $testCmd
        
        Write-OK "All files moved successfully. Root directory is now clean!"
        Write-Info "Review: git status"
        Write-Info "Next: Update any scripts/docs that reference moved files"
    }

} finally {
    Pop-Location
}
