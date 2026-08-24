#!/bin/bash

docker exec tabletkovbot-app /var/www/tabletkovbot/vendor/bin/phpstan analyse -c /var/www/tabletkovbot/phpstan.neon --memory-limit=512M

docker exec tabletkovbot-app composer test
