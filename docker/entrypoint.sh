#!/bin/sh
set -e

if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL..."
    until php -r "try { new PDO('mysql:host=$DB_HOST;port=$DB_PORT','$DB_USERNAME','$DB_PASSWORD'); echo 'ok'; } catch(PDOException \$e) { echo \$e->getMessage(); exit(1); }" 2>/dev/null | grep -q ok; do
        sleep 1
    done
    echo "MySQL ready."

    php artisan migrate --force
    echo "Migrations complete."
fi

exec "$@"
