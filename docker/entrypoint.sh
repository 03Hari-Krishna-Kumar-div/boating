#!/bin/sh

php artisan migrate --force 2>&1 || echo "Migration failed, continuing..."
php artisan storage:link 2>&1 || echo "Storage link failed, continuing..."

exec /usr/bin/supervisord -c /etc/supervisord.conf
