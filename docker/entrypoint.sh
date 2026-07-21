#!/bin/sh
set -e

php artisan migrate --force
php artisan storage:link

exec /usr/bin/supervisord -c /etc/supervisord.conf
