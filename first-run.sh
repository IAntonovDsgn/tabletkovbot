#!/usr/bin/env bash
set -e

echo "=== TabletkovBot: First Run Setup ==="

cd ./docker

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "[✓] .env file created from .env.example"
        echo "    Please edit .env and set your bot token, DB passwords, etc."
        exit 1
    else
        echo "[✗] .env.example not found. Please create .env manually."
        exit 1
    fi
fi

echo "[*] Building Docker images..."
docker-compose build --no-cache

echo "[*] Starting containers..."
docker-compose up -d

echo "[*] Waiting for PHP container to be ready..."
until docker-compose exec -T php php -v >/dev/null 2>&1; do
    sleep 1
done

echo "[*] Installing Composer dependencies (including --dev)..."
docker-compose exec -T php composer install --dev --optimize-autoloader


echo "=== Setup Complete ==="
