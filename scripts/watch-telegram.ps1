$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$watchPaths = @(
    'app',
    'routes',
    'config',
    'lang',
    'database',
    'docker',
    'composer.json',
    'composer.lock',
    'Dockerfile',
    'docker-compose.yml'
)

function Get-Snapshot {
    $items = foreach ($path in $watchPaths) {
        if (Test-Path $path) {
            Get-ChildItem -LiteralPath $path -Recurse -File -ErrorAction SilentlyContinue
        }
    }

    ($items |
        Sort-Object FullName |
        ForEach-Object { '{0}|{1}|{2}' -f $_.FullName, $_.Length, $_.LastWriteTimeUtc.Ticks }) -join "`n"
}

function Invoke-Reload {
    Write-Host ("[{0}] Change detected. Reloading Telegram workers..." -f (Get-Date -Format 'HH:mm:ss'))
    & "$PSScriptRoot\reload-telegram.ps1"
}

Write-Host 'Watching project files. Press Ctrl+C to stop.'

$lastSnapshot = Get-Snapshot
Invoke-Reload

while ($true) {
    Start-Sleep -Seconds 2

    $currentSnapshot = Get-Snapshot

    if ($currentSnapshot -ne $lastSnapshot) {
        $lastSnapshot = $currentSnapshot
        Invoke-Reload
    }
}
