#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] running migrations..."
    php src/Presentation/Console/Console.php migrations:migrate -n
fi

exec "$@"
