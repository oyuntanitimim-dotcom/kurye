#!/usr/bin/env bash
set -euo pipefail

ROOT="${DEPLOY_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$ROOT"

echo "==> Permissions"
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 755 artisan 2>/dev/null || true

echo "==> Laravel"
php artisan storage:link 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

echo "==> Deploy OK: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
