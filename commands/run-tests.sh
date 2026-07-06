#!/usr/bin/env bash
set -e

echo "=== TabletkovBot: Run Pest Tests ==="

/var/www/tabletkovbot/vendor/bin/pest

echo "=== TabletkovBot: Tests finished ==="
