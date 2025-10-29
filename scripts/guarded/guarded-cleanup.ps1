param(
    [ValidateSet('Stage0','Stage1','Stage4','Stage5','All')]
    [string]$Stage = 'Stage1',
    [switch]$Apply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Defaults: DRY-RUN unless -Apply is provided
$IsDryRun = -not $Apply.IsPresent

function Write-Info($msg) { Write-Host "[info] $msg" -ForegroundColor Cyan }
function Write-Run($cmd) { if ($IsDryRun) { Write-Host "[dry-run] $cmd" -ForegroundColor Yellow } else { Write-Host "[run] $cmd" -ForegroundColor Green } }
function Ensure-Dir($path) { if (-not (Test-Path -LiteralPath $path)) { if ($IsDryRun) { Write-Info "Would create directory: $path" } else { New-Item -ItemType Directory -Path $path | Out-Null } } }

# Repo root = two levels up from scripts/guarded
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Push-Location $RepoRoot
try {
    Write-Info "Repo root: $RepoRoot"

    function Stage0-Snapshot {
        # Create or refresh a baseline tag (non-versioned safety point)
        $tagName = 'pre-cleanup-baseline'
        if ($IsDryRun) {
            Write-Run "git tag -f $tagName"
        } else {
            git tag -f $tagName | Out-Null
        }
        Write-Info "Baseline tag ensured: $tagName"
    }

    function Stage1-Inventory {
        Write-Info 'Stage 1: Repository inventory and mapping'

        $reportsDir = Join-Path $RepoRoot 'reports'
        Ensure-Dir $reportsDir

        # 1) Tracked file inventory
        $inventoryFile = Join-Path $RepoRoot 'repo-files.txt'
        if ($IsDryRun) {
            Write-Run "git ls-tree -r HEAD --name-only > repo-files.txt"
            # Show a preview count
            try {
                $count = (git ls-tree -r HEAD --name-only | Measure-Object).Count
                Write-Info "Tracked files (preview): $count"
            } catch { Write-Info 'git inventory preview skipped (git not available?)' }
        } else {
            git ls-tree -r HEAD --name-only | Out-File -FilePath $inventoryFile -Encoding utf8
            $count = (Get-Content -LiteralPath $inventoryFile | Measure-Object).Count
            Write-Info "Tracked files written: $inventoryFile ($count entries)"
        }

        # 2) Include/require audit for PHP files
        $includeReport = Join-Path $reportsDir 'include-audit.txt'
        $phpFiles = Get-ChildItem -Path $RepoRoot -Recurse -Include *.php -File -ErrorAction SilentlyContinue
        $pattern = '(?<![A-Za-z_])(require|include)(_once)?\s*\('
        if ($IsDryRun) {
            $hits = 0
            foreach ($f in $phpFiles) {
                try { $m = (Select-String -Path $f.FullName -Pattern $pattern -AllMatches).Matches.Count } catch { $m = 0 }
                $hits += $m
            }
            Write-Info "PHP include/require hits (preview): $hits across $($phpFiles.Count) files"
            Write-Run "Write include/require audit to $includeReport"
        } else {
            $out = New-Object System.Collections.Generic.List[string]
            foreach ($f in $phpFiles) {
                try {
                    $matches = Select-String -Path $f.FullName -Pattern $pattern -AllMatches
                    foreach ($mm in $matches) { $out.Add("$($f.FullName):$($mm.LineNumber): $($mm.Line.Trim())") }
                } catch {}
            }
            $out | Out-File -FilePath $includeReport -Encoding utf8
            Write-Info "Include/require audit written: $includeReport ($($out.Count) lines)"
        }
    }

    function Stage4-LinkCheck {
        Write-Info 'Stage 4: Markdown link hygiene'
        $excludeFile = Join-Path $RepoRoot 'docs/link-exclude.txt'
        if (-not (Test-Path $excludeFile)) {
            if ($IsDryRun) {
                Write-Run "Create $excludeFile"
            } else {
                Ensure-Dir (Split-Path $excludeFile -Parent)
                @('# Add patterns or exact URLs to exclude from link checks') | Out-File -FilePath $excludeFile -Encoding utf8
            }
        }
        # Prefer Docker lychee if available
        $docker = (Get-Command docker -ErrorAction SilentlyContinue)
        if ($docker) {
            $vol = ("{0}:{1}" -f $RepoRoot, '/data')
            $lycheeCfg = Join-Path $RepoRoot 'lychee.toml'
            if (Test-Path $lycheeCfg) {
                $args = @(
                    'run','--rm','-v',$vol,
                    'lycheeverse/lychee:latest',
                    '--no-progress','--verbose',
                    '--config','/data/lychee.toml',
                    '/data/docs','/data/README.md'
                )
            } else {
                $args = @(
                    'run','--rm','-v',$vol,
                    'lycheeverse/lychee:latest',
                    '--no-progress','--verbose',
                    '--accept','429',
                    '--max-concurrency','6',
                    '--exclude-file','/data/docs/link-exclude.txt',
                    '/data/docs','/data/README.md'
                )
            }
            Write-Run ("docker " + ($args -join ' '))
            if (-not $IsDryRun) { & docker @args }
        } else {
            # Fallback: npx markdown-link-check
            $npx = (Get-Command npx -ErrorAction SilentlyContinue)
            if ($npx) {
                $cmd = 'npx markdown-link-check -q "docs/**/*.md" "README.md"'
                Write-Run $cmd
                if (-not $IsDryRun) { iex $cmd }
            } else {
                Write-Info 'No link checker available (docker or npx). Skipping Stage 4 run.'
            }
        }
    }

    function Stage5-Tests {
        Write-Info 'Stage 5: Full PHPUnit test gating'
        $cmd = 'docker-compose exec php vendor/bin/phpunit --colors=always --testdox'
        Write-Run $cmd
        if (-not $IsDryRun) { iex $cmd }
    }

    switch ($Stage) {
        'Stage0' { Stage0-Snapshot }
        'Stage1' { Stage1-Inventory }
        'Stage4' { Stage4-LinkCheck }
        'Stage5' { Stage5-Tests }
        'All'    { Stage0-Snapshot; Stage1-Inventory; Stage4-LinkCheck; Stage5-Tests }
    }
}
finally {
    Pop-Location
}
