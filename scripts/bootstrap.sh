#!/usr/bin/env bash
#
# Creates the Laravel skeleton and overlays the code from this repository on top.
#
# The skeleton itself (composer.json, artisan, bootstrap/, config/, vendor/) is
# not committed — it is what `composer create-project` generates and there is no
# reason to review it. What IS committed is everything under app-laravel/ that
# was actually written for this case study.
#
# Run once, from the repository root:
#     ./scripts/bootstrap.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OVERLAY="$ROOT/app-laravel"
TMP="$(mktemp -d)"

echo "==> Creating Laravel skeleton"
composer create-project laravel/laravel "$TMP/skeleton" --no-interaction --quiet

echo "==> Overlaying case-study code"
# Copy the skeleton in first, WITHOUT clobbering our files.
cp -rn "$TMP/skeleton/." "$OVERLAY/"

echo "==> Installing dev tooling"
cd "$OVERLAY"
composer require --dev laravel/pint phpstan/phpstan larastan/larastan --no-interaction --quiet

echo "==> Environment"
[ -f .env ] || cp .env.example .env
php artisan key:generate

echo
echo "==> One manual step remains."
echo "    Add the 'covers' disk to config/filesystems.php."
echo "    The block to paste is in:"
echo "        app-laravel/config/filesystems-covers.php.snippet"
echo
echo "    Then:"
echo "        php artisan migrate --seed"
echo "        php artisan test"
echo
echo "    Seeded accounts:"
echo "        admin@example.com  / password   (bcrypt, admin)"
echo "        legacy@example.com / password   (MD5 only — watch it upgrade on login)"

rm -rf "$TMP"
