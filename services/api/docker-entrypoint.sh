#!/bin/sh
set -e

echo "Waiting for database..."
until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8080
