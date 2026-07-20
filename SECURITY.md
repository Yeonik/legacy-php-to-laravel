# Security Policy

## Reporting a vulnerability

Please report security issues **privately**, not through a public issue:

- Email: **kirelitom@gmail.com**
- Or open a [GitHub Security Advisory](https://github.com/Yeonik/legacy-php-to-laravel/security/advisories/new)

Include what you found, where, and how to reproduce it. You can expect an
acknowledgement within a few days, and I will keep you updated until the report
is resolved or closed.

Please give me a reasonable window to respond before disclosing publicly.

## Scope

This is a portfolio case study, not a product. Nothing here is deployed to
production, there are no users and no data to protect. Reports are still
welcome — if something in [`app-laravel/`](app-laravel/) is wrong, I would
rather know.

### `legacy/` is out of scope, by design

The [`legacy/`](legacy/) directory is a deliberately vulnerable specimen: it is
the *subject* of the study. SQL built by concatenation, unescaped output,
unsalted MD5 passwords and an unrestricted upload are all intentional, tagged in
the code (`F-01`, `F-03`, …) and catalogued in
[`docs/FINDINGS.md`](docs/FINDINGS.md). Findings there are not vulnerabilities —
they are the exhibit.

The legacy application is bound to `127.0.0.1` in `docker-compose.yml` and is
not meant to be exposed.

**Please do not send exploit code or proof-of-concept payloads.** A description
of the issue and the affected file is enough. This repository contains none, and
that is deliberate.
