#!/usr/bin/env bash
#
# One-shot local setup. The application is fully committed — composer.lock pins
# every version — so this only installs dependencies and prepares a database.
#
# Run once, from the repository root:
#     ./scripts/bootstrap.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT/app-laravel"

echo "==> Installing dependencies"
composer install --prefer-dist --no-interaction

echo "==> Environment"
[ -f .env ] || cp .env.example .env
php artisan key:generate

echo "==> Database"
php artisan migrate --seed

echo
echo "==> Ready."
echo "        php artisan test"
echo "        php artisan serve"
echo
echo "    Seeded accounts:"
echo "        admin@example.com  / password   (bcrypt, admin)"
echo "        legacy@example.com / password   (MD5 only — watch it upgrade on login)"
