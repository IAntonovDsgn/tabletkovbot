#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ] && [ -f "vendor/autoload.php" ]; then
    echo "[entrypoint] running migrations..."
    php src/Presentation/Console/Console.php migrations:migrate -n || true
fi

exec "$@"
