#!/bin/sh

/usr/bin/supervisord -c /etc/supervisord.conf &
sleep 3

chmod -R 777 /app/storage /app/bootstrap/cache 2>&1 || echo "chmod failed"

php artisan migrate --force 2>&1 || echo "Migration failed, continuing..."
php artisan storage:link 2>&1 || echo "Storage link failed, continuing..."

wait
