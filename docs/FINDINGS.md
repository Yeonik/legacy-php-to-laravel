# Findings

Every issue found in the legacy codebase, what it costs in production, and how
it is addressed in the Laravel rewrite.

This document deliberately contains **no exploit code and no proof-of-concept
payloads**. The point of the repository is the remediation, not the attack.

Legend: **Sec** = security, **Arch** = architecture, **Perf** = performance,
**Ops** = operations.

---

## Security

### F-01 — SQL built by string concatenation
**Where:** `legacy/public/index.php`, `article.php`, `login.php`, `admin.php` — every query.
**Class:** CWE-89 (SQL injection). Reachable pre-authentication, including on the login path.
**Why it survived:** it works. The code was written before PDO was common in
this codebase, and nobody revisited it once the site was live.

**Fix:** Eloquent and the query builder parameterise everything. No raw SQL in
the rewrite. Where raw expressions are unavoidable, bindings are used explicitly.

```php
// after — app-laravel/app/Http/Controllers/ArticleController.php
$articles = Article::query()
    ->published()
    ->when($request->string('q')->toString(), fn ($q, $term) =>
        $q->where('title', 'like', "%{$term}%"))   // bound, not concatenated
    ->latest()
    ->paginate(15);
```

---

### F-02 — No CSRF protection anywhere
**Where:** every form; and `admin.php?action=delete` performs a destructive
write over **GET**.
**Class:** CWE-352.
**Impact:** an authenticated admin visiting an unrelated page can be made to
delete content.

**Fix:** Laravel's `VerifyCsrfToken` middleware is on by default; `@csrf` in
every Blade form. Destructive routes are `DELETE`, never `GET`.

---

### F-03 — Unescaped output (stored and reflected XSS)
**Where:** article title, article body, comment author, comment body, the search
term echoed back into the input `value`.
**Class:** CWE-79.
**Impact:** stored XSS via comments — a comment is written once and executes in
every visitor's browser, including the admin's.

**Fix:** Blade's `{{ }}` escapes by default. `{!! !!}` is used in exactly one
place — the article body — and only after the HTML has been sanitised
server-side through an allow-list purifier. That single exception is documented
inline so a future reader does not "clean it up" by accident.

---

### F-04 — Unsalted MD5 passwords
**Where:** `legacy/public/login.php`; `users.password VARCHAR(32)`.
**Class:** CWE-916. MD5 is not a password hash — it is fast by design, which is
exactly the wrong property here. No salt means identical passwords produce
identical digests.

**Fix:** bcrypt via Laravel's `Hash` facade. The column widens to
`VARCHAR(255)`.

**The migration problem, and how it is handled.** You cannot re-hash a password
you do not have. So the rewrite does not try to. Instead:

1. `users` gains a nullable `legacy_password` column and `password` becomes nullable.
2. On successful login against the legacy MD5 digest, the plaintext supplied by
   the user — which we hold for exactly one request — is hashed with bcrypt,
   written to `password`, and `legacy_password` is set to `NULL`.
3. Users migrate transparently as they log in. No forced reset, no mass email.
4. After a cutover window, remaining `legacy_password` rows are nulled and those
   accounts go through password reset.

This is in `app-laravel/app/Auth/LegacyPasswordUpgrader.php` and is covered by a
test. It is the kind of detail that decides whether a rewrite ships or stalls.

---

### F-06 — No session regeneration on login
**Class:** CWE-384 (session fixation).
**Fix:** `$request->session()->regenerate()` on login, `invalidate()` +
`regenerateToken()` on logout. Laravel's starter auth does this; the point is
that the legacy code did not.

---

### F-07 — Role copied into the session and trusted forever
**Where:** `$_SESSION['role'] = $user['role'];`
**Impact:** revoking an admin's rights in the database has no effect until their
session expires.

**Fix:** authorisation reads from the authenticated model, not from session
state. Policies (`ArticlePolicy`) are the single place where "may this user do
this" is answered.

---

### F-08 — No rate limiting on login
**Class:** CWE-307. No lockout, no backoff, no logging of failed attempts.

**Fix:** `ThrottleRequests` on the auth routes plus Laravel's
`AuthenticateSession` throttling; failed attempts emit a `Failed` event that is
logged with IP and email.

---

### F-09 — Authorisation check is "is anybody logged in"
**Where:** `admin.php` — the single `isset($_SESSION['user_id'])` guard.
**Impact:** any registered user has full admin. This is the most expensive bug in
the file and the easiest to miss, because the code *looks* like it checks something.

**Fix:** `auth` middleware for authentication, `ArticlePolicy` for authorisation,
`can:` middleware on routes. Authentication and authorisation are separated —
conflating them is what created the hole.

---

### F-11 — Unrestricted file upload
**Where:** `admin.php`, cover image.
**Class:** CWE-434. No extension allow-list, no MIME verification, no size limit,
the client-supplied filename is trusted, and the target directory is served by
the web server.

**Fix, defence in depth:**
- validation rule: `image|mimes:jpg,jpeg,png,webp|max:4096`
- MIME verified server-side, not from the request header
- filename discarded; storage generates its own
- files land in `storage/app/private/covers`, **outside the document root**, and
  are served through a controller that checks authorisation

---

## Architecture

### F-14 — No layers
Business logic, data access and HTML live in the same file. There is no place to
put a unit test even if you wanted one.

**Fix:** controllers stay thin; domain logic moves into actions/services; Eloquent
models own persistence; Blade owns rendering; Form Requests own validation.

### F-15 — No dependency management, no autoloading
Files are wired together with `require_once` and relative paths.

**Fix:** Composer, PSR-4.

### F-16 — Credentials in source control
`includes/db.php` holds the production password. The file is edited in place on
the server.

**Fix:** `.env`, `config/*.php`, `.env.example` committed and `.env` ignored.

---

## Performance

### F-05 — View counter writes on every page view
A synchronous `UPDATE` on the hot read path. On MyISAM this takes a table-level
lock.

**Fix:** the increment is dispatched to a queued job and batched. The read path
performs no writes.

### F-12 — No pagination
`index.php` loads the entire `articles` table on every request. Fine at 40 rows.
Not fine at 40,000.

**Fix:** `->paginate(15)`.

### F-13 — `SELECT *` for a listing that renders titles
The article body — the largest column in the table — is fetched for every row of
a list that never displays it.

**Fix:** explicit column selection.

### F-17 — Missing indexes
`articles` is filtered on `published` and sorted on `created_at` on every
request, with no index on either. `comments.article_id` has no index.

**Fix:** indexes added in the migrations; composite index on `(published, created_at)`.

---

## Operations

### F-18 — MyISAM, latin1
No transactions, no foreign keys, table-level locking, and a character set that
cannot store the Cyrillic content the site actually serves.

**Fix:** InnoDB, `utf8mb4_unicode_ci`, real foreign keys with cascade rules.

### F-19 — Errors printed to the browser
`die('Query failed: ' . $sql . ...)` prints the failing SQL and the driver error
to whoever triggered it — a free schema disclosure.

**Fix:** `APP_DEBUG=false` in production, structured logging, generic error pages.

### F-20 — No tests, no CI
There is nothing to run before a deploy, so nothing is run.

**Fix:** PHPUnit feature and unit tests, GitHub Actions running tests + Pint +
PHPStan on every push.

---

## Summary

| Class | Count |
|---|---|
| Security | 9 |
| Architecture | 3 |
| Performance | 4 |
| Operations | 3 |

The security issues are the headline, but F-14 (no layers) is the one that
*caused* most of the others. When there is nowhere to put a validation rule, no
validation gets written.
