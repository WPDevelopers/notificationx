---
name: version-changelog
description: Bump the plugin version and add a changelog entry for NotificationX (free) and/or NotificationX Pro. Updates the plugin header, version constant, package.json, README.txt Stable tag, and inserts a properly formatted changelog entry. Use when the user asks to bump/update the version, prepare a release, or add a changelog entry for either plugin.
tools: Read, Edit, Bash, AskUserQuestion
---

# NotificationX Version & Changelog Updater

Updates the version number and changelog for the **free** and/or **pro** plugin. Both plugins live side by side and are separate git repos:

| | Free | Pro |
|---|---|---|
| Path | `wp-content/plugins/notificationx/` | `wp-content/plugins/notificationx-pro/` |
| Main file | `notificationx.php` | `notificationx-pro.php` |
| Constant | `NOTIFICATIONX_VERSION` | `NOTIFICATIONX_PRO_VERSION` |

Free and pro have **independent version numbers** (e.g. free 3.2.11 vs pro 3.1.4) — never assume they move together.

## Step 1 — Gather inputs

Determine from the user's request (ask via AskUserQuestion only for what's missing):

1. **Which plugin(s)**: free, pro, or both.
2. **New version**: if not given, read the current version from the main plugin file and propose a patch bump (X.Y.Z → X.Y.Z+1). Confirm with the user before writing.
3. **Changelog entries**: if not given, offer to draft them from git history — run `git log --oneline <last-version-tag-or-recent>..HEAD` (or `git log --oneline -20` if no tags) inside that plugin's directory and summarize user-facing changes. Always show the drafted entries to the user for approval before writing; internal refactors/build tweaks do not belong in the changelog.
4. **Release date**: today's date, formatted `DD/MM/YYYY`.

## Step 2 — Update version (per plugin)

Every version write must update **all four** locations in that plugin — a partial bump is a bug:

1. **Main plugin file** (`notificationx.php` / `notificationx-pro.php`):
   - Header comment: `* Version:           X.Y.Z` (preserve the existing column alignment/whitespace)
   - Constant: `define( 'NOTIFICATIONX_VERSION', 'X.Y.Z' );` (or `NOTIFICATIONX_PRO_VERSION`)
2. **package.json**: `"version": "X.Y.Z"`
3. **README.txt**: `Stable tag: X.Y.Z`

Do NOT touch `Requires at least`, `Requires PHP`, or `Tested up to` unless the user explicitly asks (e.g. "also bump tested up to 7.0").

## Step 3 — Insert changelog entry (per plugin)

The changelog lives in **README.txt** under the `== Changelog ==` section. Insert the new entry **immediately after** the `== Changelog ==` line (newest first), keeping one blank line between entries.

Exact format (match the existing entries in the file):

```
= X.Y.Z - DD/MM/YYYY =
Added: <new feature>.
Fixed: <bug fix>.
Improved: <enhancement>.
Few minor bug fixes and improvements
```

Rules:

- Prefixes are `Added:`, `Fixed:`, `Improved:` — in that order when multiple kinds exist.
- Entries are short, user-facing, sentence case. No developer jargon, file names, or PR numbers.
- The closing line `Few minor bug fixes and improvements` is the house style — include it unless the user says otherwise.
- Date format is strictly `DD/MM/YYYY` (e.g. `= 3.2.12 - 15/07/2026 =`).

## Step 4 — Verify

After editing, verify consistency per plugin:

```bash
cd <plugin-dir>
grep -n "Version:" <main-file> | head -2
grep -n "_VERSION'" <main-file>
grep -n '"version"' package.json
grep -n "Stable tag" README.txt
sed -n '/== Changelog ==/,+8p' README.txt
```

All four version strings must be identical, and the new changelog entry must be at the top of the changelog section. Report the result as a short summary table (file → old version → new version).

## Notes

- Do NOT commit, tag, or push — version bump and changelog only. Mention that the changes are uncommitted.
- Do NOT run any build (`npm run build` / `release`) — the user handles builds themselves.
- If updating **both** plugins, complete free first, then pro, then show a combined summary.
