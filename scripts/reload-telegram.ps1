$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

Write-Host 'Clearing Laravel caches...'
docker compose exec -T app php artisan optimize:clear | Out-Host

Write-Host 'Restarting Telegram workers...'
docker compose restart queue scheduler | Out-Host

Write-Host 'Done.'
