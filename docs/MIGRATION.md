# Migration strategy

How you actually get from `legacy/` to `app-laravel/` on a site that is live and
has users, without a weekend-long big-bang cutover that either works or doesn't.

This is the document a client cares about. The code is the easy part.

---

## The constraint

The site is up. It has content, users, inbound links, and someone will notice if
it goes down. A rewrite that requires "we switch everything on Saturday night"
is a rewrite that either gets postponed forever or gets rolled back at 3am.

So: **strangle it, don't replace it.**

---

## Phase 0 — Make the old system observable (1–2 days)

Before touching anything:

- put the legacy code in version control (it usually isn't)
- take a full database dump and prove you can restore it
- add access logging, so you know which pages actually get traffic

You will discover that a third of the files are unreachable. Do not migrate them.

---

## Phase 1 — Schema, forward-compatible (2–3 days)

The new schema is not a redesign for its own sake. Each change maps to a finding:

| Change | Finding |
|---|---|
| MyISAM → InnoDB, latin1 → utf8mb4 | F-18 |
| `password` VARCHAR(32) → VARCHAR(255), nullable | F-04 |
| new `legacy_password` VARCHAR(32) nullable | F-04 |
| `role` VARCHAR → enum | F-18 |
| real foreign keys, cascade rules | F-18 |
| index on `(published, created_at)`, on `comments.article_id` | F-17 |
| `cover` filename → `cover_path` on a private disk | F-11 |

Run the schema migration **while the legacy app is still serving traffic**. The
old code keeps working: it doesn't care that `password` grew, that indexes
appeared, or that a nullable column was added.

The one thing it *would* care about is `role` becoming an enum — so seed the
enum with exactly the values already present in the table, warts included, and
clean them up in a later pass.

---

## Phase 2 — Passwords, without asking anyone to do anything (see F-04)

Copy the MD5 digests into `legacy_password`, leave `password` NULL.

From that point the new application can authenticate every existing user, and
each one silently upgrades to bcrypt the first time they log in. No forced
resets, no "we've improved our security, please choose a new password" email
that half your users will ignore and the other half will find alarming.

**Cutover window:** 90 days is a reasonable default. Track the ratio:

```sql
SELECT COUNT(*) FILTER (WHERE legacy_password IS NULL) AS migrated,
       COUNT(*)                                        AS total
FROM users;
```

When the curve flattens, null the remainder and send those accounts through
password reset. Typically that is the dormant tail — 5–15% — and they were going
to need a reset anyway.

---

## Phase 3 — Strangler fig, route by route

Put the new application behind the same domain and move one route at a time,
starting with the ones that are read-only and low-traffic.

```nginx
location /articles/ { proxy_pass http://laravel; }   # migrated
location /admin/    { proxy_pass http://laravel; }   # migrated
location /          { proxy_pass http://legacy;  }   # not yet
```

Order matters. Migrate in this sequence:

1. **Public read paths** (article list, article page) — lowest risk, highest
   traffic, so you learn fast and can roll back a single `location` block.
2. **Admin read paths.**
3. **Admin writes** (create/update/delete). By now the team trusts the new stack.
4. **Auth.** Last, because it touches everyone.

Both applications talk to the same database throughout. That is what makes the
route-by-route move possible, and it is why Phase 1's schema has to be
forward-compatible rather than a clean redesign.

---

## Phase 4 — Delete the old code

Not "archive it". Not "keep it around just in case". Delete it, once the last
route is proxied and the access log has been quiet for two weeks. It is in git.

---

## What I would tell a client to expect

| Phase | Effort |
|---|---|
| 0 — observability | 1–2 days |
| 1 — schema | 2–3 days |
| 2 — password migration | 1 day + a 90-day passive window |
| 3 — strangler, route by route | the bulk of it — depends entirely on route count |
| 4 — decommission | 1 day |

The honest answer to "how long does it take to rewrite our site on Laravel" is
**"I'll tell you after I've seen it and counted the routes"** — and any estimate
given before that is a guess dressed up as a number.

That is also why this repository exists: to make the estimate a conversation
about specifics rather than a conversation about trust.
