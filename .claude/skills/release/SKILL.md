---
name: release
description: Full release preparation for NotificationX free and/or pro — version bump + changelog (via the version-changelog skill), production build, POT regeneration, and distribution zip. User-invoked only; confirms before running builds.
disable-model-invocation: true
tools: Read, Edit, Bash, Skill, AskUserQuestion
---

# Release Prep (free / pro)

End-to-end release flow. Heavier than `version-changelog` — this one also builds and zips.

## Steps

1. **Version + changelog**: invoke the `version-changelog` skill (Skill tool) for the requested plugin(s). Everything about versions/changelog lives there — don't duplicate it.
2. **⚠️ Watcher check (MANDATORY)**: the user normally runs a dev watcher (`npm run admin-watch` / `start`). Before any build, ask the user to confirm the watcher is stopped — a watcher writing to `nxbuild/` mid-build produces corrupt bundles.
3. **Build + zip** (only after confirmation):
   - Free (`wp-content/plugins/notificationx/`): `npm run zip` — runs admin + frontend + blocks + countdown builds, regenerates the POT, then `wp dist-archive .`
   - Pro (`wp-content/plugins/notificationx-pro/`): `npm run zip` — build + POT + `wp dist-archive .`
4. **Verify the artifact**:
   - Confirm the `.zip` landed (dist-archive outputs next to the plugin dir) and report its path + size.
   - Spot-check the zip respects [.distignore](../../../.distignore): `unzip -l <zip> | grep -E "nxdev|node_modules|tests"` should return nothing.
5. **Report**: summary table — plugin, new version, zip path. Remind the user that git commit/tag/push is theirs to do (never commit or push from this skill).

## Rules

- Never run this flow uninvited — it is user-triggered only (`/release`).
- If any build step fails, stop and show the real error output; do not zip a broken build.
- Requires `wp` CLI (`wp dist-archive`, `wp i18n`) — if missing, report it and stop.
