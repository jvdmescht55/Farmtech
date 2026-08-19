@echo off
REM Farmtech turnkey setup — Docker-based (Laravel Sail-style).
REM Run from the repo root: setup.bat
setlocal enabledelayedexpansion

where docker >nul 2>nul
if errorlevel 1 (
    echo ERROR: Docker is not installed. Install Docker Desktop: https://www.docker.com/products/docker-desktop/
    exit /b 1
)

docker compose version >nul 2>nul
if errorlevel 1 (
    echo ERROR: Docker Compose v2 not found. Update Docker Desktop.
    exit /b 1
)

where node >nul 2>nul
if errorlevel 1 (
    echo ERROR: Node.js is not installed. Install from https://nodejs.org/
    exit /b 1
)

echo === Copying environment files ===
if not exist .env copy .env.example .env

echo === Creating upload directories ===
if not exist public\uploads\products mkdir public\uploads\products
if not exist storage\app\public mkdir storage\app\public
if not exist storage\framework\cache mkdir storage\framework\cache
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\logs mkdir storage\logs

echo === Installing worker (Node) dependencies ===
pushd worker
call npm install
popd

echo === Installing frontend (Vite/Tailwind) dependencies ===
call npm install

echo === Building frontend assets ===
call npm run build

echo === Building and starting Docker containers ===
docker compose up -d --build
if errorlevel 1 exit /b 1

echo === Waiting for MySQL (15s) ===
timeout /t 15 /nobreak >nul

echo === Generating APP_KEY ===
docker compose exec -T laravel.test php artisan key:generate --ansi

echo === Running migrations ===
docker compose exec -T laravel.test php artisan migrate --force

echo === Seeding settings, admin user, exchange rate ===
docker compose exec -T laravel.test php artisan db:seed --force

echo === Linking public storage ===
docker compose exec -T laravel.test php artisan storage:link

echo.
echo Setup complete.
echo   Storefront: http://localhost:8000
echo   Admin:      http://localhost:8000/admin/login
echo   Admin login uses ADMIN_EMAIL / ADMIN_PASSWORD from .env.
echo.
echo Next: run the sourcing pipeline against the bundled mock data:
echo   docker compose exec worker node src/pipeline.js --file mock_data.json

endlocal
