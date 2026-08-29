# scripts/dusk.ps1 — запуск Dusk-тестов на Windows (OSPanel)
# Эквивалент `make dusk` для Linux/CI.
# Поднимает backend (php artisan serve, env=dusk.local) и Vite dev-сервер,
# затем прогоняет `php artisan dusk --env=dusk.local` и останавливает процессы.

param(
    [string]$BackendPort = '8000',
    [string]$FrontendPort = '5173'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

function Stop-Tree([int]$TargetPid) {
    Get-CimInstance Win32_Process -Filter "ParentProcessId=$TargetPid" -ErrorAction SilentlyContinue |
        ForEach-Object { Stop-Tree $_.ProcessId }
    Stop-Process -Id $TargetPid -Force -ErrorAction SilentlyContinue
}

$backend = $null
$frontend = $null
$exitCode = 1

try {
    Push-Location (Join-Path $root 'backend')
    $backend = Start-Process php -ArgumentList "artisan", "serve", "--env=dusk.local", "--host=127.0.0.1", "--port=$BackendPort" -PassThru -WindowStyle Hidden
    Pop-Location

    Push-Location (Join-Path $root 'frontend')
    $frontend = Start-Process cmd.exe -ArgumentList '/c', "npm run dev -- --port $FrontendPort" -PassThru -WindowStyle Hidden
    Pop-Location

    Start-Sleep -Seconds 8

    Push-Location (Join-Path $root 'backend')
    & php artisan dusk --env=dusk.local
    $exitCode = $LASTEXITCODE
    Pop-Location
} finally {
    if ($backend) { Stop-Tree $backend.Id }
    if ($frontend) { Stop-Tree $frontend.Id }
    Get-Process node -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
}

exit $exitCode
