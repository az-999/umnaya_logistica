#!/bin/sh
set -e

echo "Waiting for database..."
until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

exec php artisan queue:work rabbitmq \
    --queue=notifications.marketing \
    --tries=3 \
    --backoff=10,60,300 \
    --sleep=1 \
    --timeout=120
