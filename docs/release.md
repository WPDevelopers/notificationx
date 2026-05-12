# Release Checklist

How to cut a NotificationX release.

## Version bump (do all four, in one commit)

A NotificationX version lives in **four** places. They must match exactly.

| File | What to change |
|---|---|
| [notificationx.php](../notificationx.php) | The `* Version:` plugin header line **and** the `NOTIFICATIONX_VERSION` constant |
| [package.json](../package.json) | `"version": "..."` |
| [README.txt](../README.txt) | `Stable tag: X.Y.Z` + a `== Changelog ==` entry |
| [readme.html](../readme.html) (if shipping a user-facing notice) | version string in the changelog block |

> **Why all four:** the `Version:` header is what WP shows to users; `NOTIFICATIONX_VERSION` drives asset cache busting (`nxbuild/` URLs include it) and the upgrader migration path; `package.json` is what wp-scripts reads; `README.txt` `Stable tag` is what WP.org serves on the update API. Mismatch causes silent half-broken updates.

## Build

```sh
npm ci                # clean install (use ci, not install, for reproducibility)
composer install --no-dev --optimize-autoloader
npm run release       # = build (admin + frontend) + bb (blocks) + pot
```

What `release` produces:
- `nxbuild/` — admin + frontend bundles.
- `blocks/notificationx/index.js` — block bundle.
- `languages/notificationx.pot` — translations template.

If any of these are missing, the release is incomplete.

## Distribution zip

```sh
npm run zip
```

Uses `wp dist-archive` (requires [WP-CLI](https://wp-cli.org) installed locally). Reads [.distignore](../.distignore) to exclude dev files. Output: `notificationx.X.Y.Z.zip` in the parent directory.

**Before zipping, verify [.distignore](../.distignore) excludes:**
- `node_modules/`, `vendor/` dev deps (if shipping a separate runtime vendor), `nxdev/` (source), `tests/`, `docs/`, `.git*`, `webpack.*.config.js`, `package-lock.json`, `composer.lock` if you don't ship it, `*.md` files not meant for users.

`.distignore` and `.gitattributes` overlap but aren't the same — update both when adding top-level dev-only files.

## POT regeneration

`npm run pot` runs:
```sh
wp i18n make-pot . languages/notificationx.pot --exclude='nxbuild'
```

Must be done **after** code changes that touch translatable strings. Verify the file's `POT-Creation-Date` matches your release.

## Pre-release smoke test

On a clean WP install:

1. Install the zip via **Plugins → Add New → Upload**.
2. Activate. No PHP errors in `wp-content/debug.log`.
3. **NotificationX → Add New** → create a notification of each major Type (Sales, NotificationBar, Popup, ExitIntent). Each builder loads without console errors.
4. Publish a Sales notification with WooCommerce as the source (if WC installed). Front-end popup appears.
5. **NotificationX → Settings → Modules** — toggle one module off. Confirm its Extension disappears from the source dropdown.
6. **NotificationX → Analytics** — confirm the page loads.
7. Activate `notificationx-pro` on top (if you have it). Confirm Pro Extensions appear without errors.

If any step fails, **do not release.**

## Upgrade path testing

For non-trivial schema changes:

1. Install the *previous* stable version on a clean WP.
2. Create at least one notification of each Type.
3. Upgrade to the new version (replace plugin folder).
4. Verify the [Upgrader](../includes/Core/Upgrader.php) ran (check the stored version option) and notifications still work.
5. Verify the `nx_posts`, `nx_entries`, `nx_stats` tables have the expected schema (`SHOW CREATE TABLE`).

## WP.org SVN flow

If publishing to the WP plugin directory:

1. Check out the SVN repo: `svn co https://plugins.svn.wordpress.org/notificationx/`.
2. Copy the contents of the built plugin (NOT the zip — the unzipped tree) into `trunk/`.
3. `svn add` new files, `svn rm` removed files.
4. Commit `trunk/` with the changelog message.
5. Tag the release: `svn cp trunk/ tags/X.Y.Z/`, commit.
6. Update `assets/` (banners, icons, screenshots) only if changed — these live at SVN root, **not** inside `trunk/`.

## Pro coordination

If this release exposes new hooks for `notificationx-pro`, or changes existing ones:
1. Update [integrations/pro-hooks.md](integrations/pro-hooks.md) in the **same** PR.
2. Coordinate with the Pro maintainer — Pro should ship a compatible version *before or simultaneously with* free.
3. Bump the minimum Pro version requirement in the free plugin's compatibility check, if any.

## Post-release

- Tag the release in git: `git tag X.Y.Z && git push --tags`.
- Update [README.txt](../README.txt) `== Changelog ==` if you didn't already.
- Watch the WP.org support forum and `wp-content/debug.log` reports for ~48h.
- If a regression slips through, **don't amend** — release X.Y.Z+1 instead. The WP.org update API hates retroactive changes.

## Hotfix flow

For urgent fixes between scheduled releases:

1. Branch from the release tag, not main.
2. Make the minimal fix.
3. Bump only the patch version (X.Y.Z → X.Y.(Z+1)).
4. Run the full release flow above.
5. Cherry-pick the fix back to main.
