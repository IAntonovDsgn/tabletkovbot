#!/usr/bin/env bash
set -e

echo "=== Run Setup ==="

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "[✓] .env file created from .env.example"
        echo "    Please edit .env and set your bot token, DB passwords, etc.,"
        echo "    then re-run ./first-run.sh"
        exit 1
    else
        echo "[✗] .env.example not found. Please create .env manually." >&2
        exit 1
    fi
fi

# shellcheck disable=SC1091
source .env

COMPOSE="docker compose -f docker/docker-compose.yml --env-file .env"
APP_CTR="${COMPOSE_PROJECT_NAME:-tabletkovbot}-app"

echo "[*] Building Docker images..."
$COMPOSE build

echo "[*] Starting containers..."
$COMPOSE up -d

echo "[*] Waiting for the app container to be ready..."
until docker exec "$APP_CTR" php -v >/dev/null 2>&1; do
    sleep 1
done

echo "[*] Installing Composer dependencies..."
docker exec "$APP_CTR" sh -c 'cd /var/www/tabletkovbot && composer install --optimize-autoloader'

echo "[*] Running database migrations..."
docker exec "$APP_CTR" sh -c 'cd /var/www/tabletkovbot && composer console -- migrations:migrate -n'

echo "=== Setup Complete ==="
