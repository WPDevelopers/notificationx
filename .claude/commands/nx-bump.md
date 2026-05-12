---
description: Bump the NotificationX version in all four required locations (plugin header, constant, package.json, README.txt stable tag).
argument-hint: <X.Y.Z>
allowed-tools: Read, Edit, Bash
---

# /nx-bump

Bump the NotificationX version. Argument: `$ARGUMENTS` — must be a single `X.Y.Z` semver string.

## Why four places

A NotificationX version lives in:

1. `notificationx.php` — the `* Version:` plugin header line
2. `notificationx.php` — the `NOTIFICATIONX_VERSION` constant
3. `package.json` — the `"version"` field
4. `README.txt` — the `Stable tag:` line

These drive (in order): what WP shows to users, asset cache-busting, what wp-scripts reads, and what WP.org serves on the update API. Mismatch causes silent half-broken updates.

## Steps to perform

1. **Validate** `$ARGUMENTS` matches `^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.-]+)?$`. If not, stop and ask the user for a valid semver.

2. **Read the current version** from `notificationx.php` (the `NOTIFICATIONX_VERSION` define line). Call it `$OLD`. Report both `$OLD` and `$NEW` to the user before editing.

3. **Sanity check**: confirm `$NEW` is strictly greater than `$OLD` by semver comparison. If equal or lower, stop and ask the user to confirm — downgrades are unusual.

4. **Make all four edits** (one Edit per file, all in this turn):

   a. `notificationx.php` — plugin header. Match `* Version:           <OLD>` and replace.

   b. `notificationx.php` — constant. Match `define( 'NOTIFICATIONX_VERSION', '<OLD>' );` and replace.

   c. `package.json` — match `"version": "<OLD>",` and replace.

   d. `README.txt` — match `Stable tag: <OLD>` and replace.

5. **Verify** with a single grep:
   ```sh
   grep -n "$NEW\|$OLD" notificationx.php package.json README.txt
   ```
   The output should show `$NEW` in four locations and `$OLD` in zero. If `$OLD` still appears anywhere, list those lines and ask the user how to handle them — do not auto-edit further.

6. **Do NOT commit.** Leave the working tree dirty. Tell the user the bump is staged and remind them of the release flow: `npm run release && npm run zip`. Point at `docs/release.md` for the full checklist.

## Output format

End with this exact block (filled in):

```
Bumped: <OLD> → <NEW>

Files changed:
  - notificationx.php (header)
  - notificationx.php (NOTIFICATIONX_VERSION)
  - package.json
  - README.txt (Stable tag)

Next:
  - Add a changelog entry to README.txt under == Changelog ==
  - npm run release   # build admin + frontend + blocks + pot
  - npm run zip       # produce notificationx.<NEW>.zip
  - See docs/release.md for the full checklist
```

## Anti-patterns

- ❌ Editing only some of the four locations.
- ❌ Auto-committing — the user should review the changelog before committing.
- ❌ Editing `readme.html` (user-facing notice file) without being asked — it's not in the required four.
- ❌ Pre-releasing the build (`npm run release`) automatically — the user may want to add changelog entries first.

## On uncertainty

If grep finds an unexpected `$OLD` occurrence (e.g. in a vendored file, an old changelog entry, or a hardcoded comparison), report it but do not change it. Old version numbers in changelogs are correct and must remain.
