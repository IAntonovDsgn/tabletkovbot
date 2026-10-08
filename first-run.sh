#!/usr/bin/env bash
set -e

echo "=== Run Setup ==="

cd "$(dirname "$0")"

# shellcheck disable=SC1091
source .env

COMPOSE="docker compose -f docker/docker-compose.yml --env-file .env"

echo "[*] Building Docker images..."
$COMPOSE build

if [ ! -f "vendor/autoload.php" ]; then
    echo "[*] Initial composer install..."
    $COMPOSE run --rm --no-deps app composer install --optimize-autoloader
fi

echo "[*] Starting infrastructure (DB & RabbitMQ)..."
$COMPOSE up -d db rabbitmq

echo "[*] Waiting for DB to be healthy..."
until [ "$($COMPOSE ps db --format '{{.Health}}')" = "healthy" ]; do
    sleep 1
done

echo "[*] Starting app and workers..."
$COMPOSE up -d

echo "[*] Running database migrations..."
$COMPOSE exec -T app php src/Presentation/Console/Console.php migrations:migrate -n

echo "=== Setup Complete ==="
