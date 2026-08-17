#!/bin/bash

LOCAL_PROJECT_ROOT="/Users/igor/projects/tg-bot-tabletkovbot"
DOCKER_PROJECT_ROOT="/var/www/tabletkovbot"

LOCAL_FILE="$1"

DOCKER_FILE="${LOCAL_FILE/$LOCAL_PROJECT_ROOT/$DOCKER_PROJECT_ROOT}"

docker exec tabletkovbot-app /var/www/tabletkovbot/vendor/bin/phpstan analyse -c /var/www/tabletkovbot/phpstan.neon "$DOCKER_FILE"
