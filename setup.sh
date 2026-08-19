#!/usr/bin/env bash
# Farmtech turnkey setup — Docker-based (Laravel Sail-style).
# Run from the repo root: ./setup.sh
set -euo pipefail

log() { printf '\n\033[1;32m==>\033[0m %s\n' "$1"; }
fail() { printf '\n\033[1;31mERROR:\033[0m %s\n' "$1" >&2; exit 1; }

command -v docker >/dev/null 2>&1 || fail "Docker is not installed. Install Docker Desktop: https://www.docker.com/products/docker-desktop/"
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 not found. Update Docker Desktop."
command -v node >/dev/null 2>&1 || fail "Node.js is not installed (needed for the sourcing/vetting worker and asset build). Install from https://nodejs.org/"

log "Copying environment files"
[ -f .env ] || cp .env.example .env

log "Creating upload directories"
mkdir -p public/uploads/products
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R 775 storage public/uploads 2>/dev/null || true

log "Installing worker (Node) dependencies"
(cd worker && npm install)

log "Installing frontend (Vite/Tailwind) dependencies"
npm install

log "Building frontend assets"
npm run build

log "Building and starting Docker containers (php, mysql, worker)"
docker compose up -d --build

log "Waiting for MySQL to be healthy"
tries=0
until docker compose exec -T mysql healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; do
    tries=$((tries + 1))
    [ "$tries" -gt 30 ] && fail "MySQL did not become healthy in time. Check: docker compose logs mysql"
    sleep 2
done

log "Generating APP_KEY (if not already set)"
docker compose exec -T laravel.test php artisan key:generate --ansi || true

log "Running migrations"
docker compose exec -T laravel.test php artisan migrate --force

log "Seeding settings, admin user, and exchange rate defaults"
docker compose exec -T laravel.test php artisan db:seed --force

log "Linking public storage"
docker compose exec -T laravel.test php artisan storage:link || true

log "Setup complete."
echo "  Storefront: http://localhost:${APP_PORT:-8000}"
echo "  Admin:      http://localhost:${APP_PORT:-8000}/admin/login"
echo "  Admin login uses ADMIN_EMAIL / ADMIN_PASSWORD from .env (defaults: admin@farmtech.co.za / change-me-immediately)."
echo ""
echo "Next: run the sourcing pipeline against the bundled mock data:"
echo "  docker compose exec worker node src/pipeline.js --file mock_data.json"
