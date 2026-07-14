# AGENTS.md

Entry point for coding agents working on the NotificationX free WordPress plugin.

## Read first

- [CLAUDE.md](CLAUDE.md) — the primary guide: project layout, bootstrap chain, the Type ↔ Extension model, commands, and conventions. Read it before touching code.
- [CLAUDE.local.md](CLAUDE.local.md) — local-environment notes (live site, watcher, vendor/ and nxbuild/ are off-limits for edits).
- [docs/README.md](docs/README.md) — documentation tree: `architecture/`, `api/`, `development/`, `features/`, `types/` (per notification Type), `extensions/` (per integration).

## Commands

- JS/asset builds use `@wordpress/scripts` (`npm run build`, `npm run admin`, `npm run frontend`, `npm run bb`; see CLAUDE.md for the full list). **Do not run builds manually** — the developer runs a watcher that rebuilds into `nxbuild/`.
- `composer install` — PHP deps (vendor/ is committed; only needed after composer.json changes).
- Lint: `vendor/bin/phpcs --standard=phpcs.xml`. A pre-commit hook in `.githooks/` runs `php -l` on staged PHP files.

## Tests

PHPUnit integration tests (WP_UnitTestCase) live in [tests/](tests/), config [phpunit.xml.dist](phpunit.xml.dist). One-time setup installs the WP core test harness (needs MySQL and svn):

```sh
bash bin/install-wp-tests.sh wordpress_test root root localhost latest
phpunit   # or vendor/bin/phpunit, from the repo root
```

The harness reads `WP_TESTS_DIR` (defaults to `<tmp>/wordpress-tests-lib`). CI runs the same flow via `.github/workflows/phpunit.yml` (called from `qa.yml` and gating `deploy.yml`). To add a test, use the `add-test` skill.

## Skills (.claude/skills/)

- `add-test` — scaffold a PHPUnit test following repo conventions.
- `new-extension` — scaffold a new integration (Extension) for an existing Type.
- `new-sales-theme` — add a Sales notification theme from a Figma design.
- `sync-docs` — update docs/types/ or docs/extensions/ after code changes.
- `verify-ui` — verify UI changes live on http://notificationx.test/ via Chrome DevTools.
- `version-changelog` — bump version + changelog (free and/or Pro).
- `release` — release workflow helper.
- `admin-notice-generator` — generate admin notice code from a Figma design.

## Hard rules

- Never edit `vendor/` or `nxbuild/` (enforced by `.claude/hooks/protect-files.sh`).
- Pro-only logic belongs in the sibling `notificationx-pro` plugin, not here.
- New top-level dev-only files must be added to `.distignore` and `.gitattributes`.
