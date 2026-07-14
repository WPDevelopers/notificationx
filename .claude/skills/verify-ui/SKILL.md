---
name: verify-ui
description: Verify a NotificationX admin or frontend UI change live on the local site (notificationx.test) using the Chrome DevTools MCP — navigate, screenshot, and check the console for errors. Use after UI/CSS/React changes when the user asks to verify, check, or screenshot how something looks.
tools: Read, Bash, ToolSearch, mcp__chrome-devtools__*
---

# Verify UI on notificationx.test

Checks a UI change in the real browser instead of guessing from code. Local site: `http://notificationx.test/` (the user is normally already logged into wp-admin in Chrome).

## Workflow

1. Load the needed Chrome DevTools MCP tools via ToolSearch (`navigate_page`, `take_screenshot`, `list_console_messages`, `take_snapshot`).
2. **Navigate** to the page under test. Common URLs:
   - Admin SPA: `http://notificationx.test/wp-admin/admin.php?page=nx-admin` (also `nx-dashboard`, `nx-settings`, `nx-analytics`, `nx-setup-wizard`, `nx-edit`)
   - Frontend (popups/bars render on any page): `http://notificationx.test/`
3. **Wait for the change**: if a watcher build is involved, the bundle in `nxbuild/` must be newer than the source edit — check `ls -la nxbuild/` timestamps before blaming the code.
4. **Inspect**:
   - `take_screenshot` — visual state (attach/describe what's actually visible).
   - `list_console_messages` — any new JS errors/warnings; ignore pre-existing noise unrelated to the change.
   - For layout issues, `take_snapshot` / `evaluate_script` to check computed styles or the presence of expected classes (e.g. body classes, theme wrappers).
5. **Report honestly**: what the screenshot shows, console errors found (or "none"), and whether the change is actually visible. "Code looks right" is not verification.

## Notes

- Admin SPA routes are client-side (Route.tsx intercepts NX menu clicks) — to test client-side navigation behavior, click through the UI rather than hard-reloading.
- If Chrome DevTools MCP is not connected, fall back to asking the user to check manually — do not fake a verification.
