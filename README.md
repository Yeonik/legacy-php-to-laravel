# Legacy PHP → Laravel: a rewrite, in public

A small CMS — articles, comments, an admin panel, image uploads — written the
way a lot of PHP was written in 2010, and then rewritten on Laravel.

Both versions are in this repository. The interesting part is not either one of
them; it is the diff, and the reasoning behind it.

- [`legacy/`](legacy/) — the "before". Procedural PHP, `mysqli`, SQL and HTML in
  the same file. It works. That is why it survived for a decade.
- [`app-laravel/`](app-laravel/) — the "after".
- [`docs/FINDINGS.md`](docs/FINDINGS.md) — **every issue found, what it costs, and
  how it is fixed.** Start here.
- [`docs/MIGRATION.md`](docs/MIGRATION.md) — how you get from one to the other
  without a big-bang cutover.

---

## Why this repository exists

I have spent fifteen years in PHP, most of it on codebases that were already old
when I arrived: legacy modules on CodeIgniter, a WordPress plugin re-architected
from the inside out, a whole extension ecosystem migrated from GraphQL to REST
without breaking existing installs.

Almost none of that is public — it belongs to the companies that paid for it.
So this is the same class of work, done in the open, where the code can be read
instead of described.

The security angle is not decoration. Rewrites of old PHP applications
*always* turn into security work, because the same conditions that produce
`SELECT * FROM articles WHERE id = $id` also produce unsalted MD5 and an admin
panel guarded by `isset($_SESSION['user_id'])`. Pretending otherwise is how you
ship the same holes into a modern framework.

---

## What was actually wrong

19 findings, catalogued in [`docs/FINDINGS.md`](docs/FINDINGS.md).

| | |
|---|---|
| **Security** | 9 |
| **Architecture** | 3 |
| **Performance** | 4 |
| **Operations** | 3 |

The headlines:

| ID | Finding | Fixed by |
|---|---|---|
| F-01 | SQL built by string concatenation — on the login path too | Query builder, bound parameters |
| F-03 | Output never escaped; comments are stored and re-rendered as-is | Blade auto-escaping; one documented exception, sanitised server-side |
| F-04 | Unsalted MD5 passwords, in a `VARCHAR(32)` column | bcrypt + a transparent upgrade-on-login path |
| F-09 | The admin panel's only check was "is anybody logged in" | `auth` middleware + `ArticlePolicy` — authentication and authorisation separated |
| F-11 | File upload with no type check, no size cap, client-supplied filename, web-accessible target | Validation, server-side MIME check, generated filename, private disk |
| F-05 | `UPDATE views = views + 1` synchronously on every page view | Queued, batched; the read path performs no writes |

**No exploit code, no proof-of-concept payloads, anywhere in this repository.**
The value is in the remediation. The legacy app is bound to `127.0.0.1` in
`docker-compose.yml` and is not meant to be deployed.

---

## The part that is actually hard

Everything above is mechanical. This is not:

> **You cannot re-hash a password you do not have.**

The legacy `users` table holds unsalted MD5 digests. There is no offline
conversion to bcrypt — recovering the plaintext is precisely what hashing is
designed to prevent. So a rewrite has three options: force a password reset on
every user, keep MD5 forever, or migrate users one at a time, silently.

This repository does the third:

1. `password` becomes nullable; a `legacy_password` column carries the old digest.
2. At login, the plaintext is verified against the MD5 digest with a
   constant-time comparison.
3. On success — and only then, in the one request where the plaintext legitimately
   exists — it is re-hashed with bcrypt, persisted, and the legacy digest is
   dropped.
4. Users migrate as they log in. Nobody is emailed. Nobody is locked out.
5. After the cutover window, the remaining digests are nulled and those accounts
   go through password reset.

[`app/Auth/LegacyPasswordUpgrader.php`](app-laravel/app/Auth/LegacyPasswordUpgrader.php),
tested in
[`tests/Feature/LegacyPasswordUpgradeTest.php`](app-laravel/tests/Feature/LegacyPasswordUpgradeTest.php).

This is usually the detail that decides whether a rewrite ships or stalls in a
meeting about "the password problem".

---

## Tests

The tests are written as **regression tests against the legacy behaviour** — each
one asserts that a specific finding cannot come back.

```
tests/Feature/AuthorizationTest.php        F-07, F-09 — a reader is not an admin
tests/Feature/LegacyPasswordUpgradeTest.php F-04 — MD5 users migrate silently
tests/Feature/SearchTest.php               F-01, F-03, F-12 — input is data, output is escaped
```

```bash
docker compose run --rm app php artisan test
```

CI runs the suite plus Pint and PHPStan (level 6) on every push.

---

## Running it

The application is committed in full, `composer.lock` included — so the versions
you get are the versions this was written against.

```bash
git clone https://github.com/<user>/legacy-to-laravel
cd legacy-to-laravel/app-laravel

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

php artisan test
php artisan serve
```

`./scripts/bootstrap.sh` from the repository root does all of the above in one go.

Seeded accounts:

| Email | Password | Note |
|---|---|---|
| `admin@example.com` | `password` | bcrypt, admin |
| `legacy@example.com` | `password` | **MD5 only** — log in and watch `legacy_password` go `NULL` and `password` become a bcrypt hash. F-04, live. |

To read the "before" side by side:

```bash
docker compose up -d legacy legacy-db
open http://127.0.0.1:8081
```

It is bound to `127.0.0.1` on purpose.

---

## What this is not

It is not a Laravel tutorial, and it is not a starter kit. The application is
deliberately small — a CMS is about as unoriginal as it gets — because the
subject is the *transition*, not the domain. A more interesting domain would
have made the diff harder to read, which is the opposite of the point.

---

## Author

**Yeonik** — Senior PHP / Backend Engineer, 15+ years.
PHP 7/8, web service architecture, legacy migration, application security.
Certified in Web Application Security and ISO 27001:2013.

[LinkedIn]()
