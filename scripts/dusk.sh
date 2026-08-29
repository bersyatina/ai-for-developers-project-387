#!/usr/bin/env bash
# scripts/dusk.sh — запуск Dusk-тестов на Linux/CI (эквивалент `make dusk`).
# На Windows используйте `powershell -File scripts/dusk.ps1`.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cleanup() {
    [ -n "${BACKEND_PID:-}" ] && kill "$BACKEND_PID" 2>/dev/null || true
    [ -n "${VITE_PID:-}" ] && kill "$VITE_PID" 2>/dev/null || true
    wait 2>/dev/null || true
}
trap cleanup EXIT

# Backend поднимается с env=dusk.local (тестовая БД call_calendar_test).
(cd "$ROOT/backend" && php artisan serve --env=dusk.local --host=127.0.0.1 --port=8000) &
BACKEND_PID=$!

(cd "$ROOT/frontend" && npm run dev -- --port 5173) &
VITE_PID=$!

sleep 8

cd "$ROOT/backend"
php artisan dusk --env=dusk.local
