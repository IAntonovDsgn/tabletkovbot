#!/usr/bin/env bash
set -e

echo "=== TabletkovBot: First Run Setup ==="

cd ../docker

# 1. Установка переменных окружения
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

# 2. Сборка и запуск контейнеров
echo "[*] Building Docker images..."
docker-compose build --no-cache

echo "[*] Starting containers..."
docker-compose up -d

# 3. Ожидание готовности PHP-контейнера
echo "[*] Waiting for PHP container to be ready..."
until docker-compose exec -T php php -v >/dev/null 2>&1; do
    sleep 1
done

# 4. Установка зависимостей Composer (с dev-пакетами)
echo "[*] Installing Composer dependencies (including --dev)..."
docker-compose exec -T php composer install --dev --optimize-autoloader


echo "=== Setup Complete ==="
