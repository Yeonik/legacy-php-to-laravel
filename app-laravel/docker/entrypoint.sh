#!/bin/sh
#
# Container start-up for the "after" app.
#
# `php artisan serve` reads .env, not the container environment, so the DB
# settings the compose file supplies have to land in .env — otherwise the
# migrations (which do see the environment) and the served pages disagree on
# which database they are talking to. A fresh checkout has no .env at all
# (it is gitignored), so we create one wired to the compose MySQL service.
set -e

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
    sed -i \
        -e "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" \
        -e "s/^# *DB_HOST=.*/DB_HOST=${DB_HOST:-db}/" \
        -e "s/^# *DB_PORT=.*/DB_PORT=${DB_PORT:-3306}/" \
        -e "s/^# *DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE:-cms}/" \
        -e "s/^# *DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME:-cms}/" \
        -e "s/^# *DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD:-secret}/" \
        .env
fi

# Retry until the db service accepts connections, then build a fresh, seeded schema.
until php artisan migrate:fresh --seed --force 2>/dev/null; do
    echo "Waiting for the database to be ready..."
    sleep 2
done

exec php artisan serve --host=0.0.0.0 --port=8000
