---
name: new-sales-theme
description: Add a new Sales notification (Conversions) theme to NotificationX from a Figma design. Knows every file that must change — PHP theme registration, React template, SCSS, preview image — and follows the in-repo design doc. Use when the user asks to implement/add a new sales notification theme or design.
tools: Read, Edit, Write, Bash, Glob, Grep, mcp__figma__*, AskUserQuestion
---

# New Sales Notification Theme

Implements one new Sales (Conversions) notification theme end-to-end. The **authoritative walkthrough is in-repo**: read [docs/sales-notification-add-new-design.md](../../../docs/sales-notification-add-new-design.md) FIRST and follow it — this skill is the checklist/file-map on top of it.

## Step 0 — Inputs

1. **Figma link** (usually provided). Pull the design via the Figma MCP (`get_design_context` / `get_screenshot`). Load the `figma-design-to-code` skill if implementing from Figma.
2. **Theme slug**: continue the existing sequence — check the last `conv-theme-*` slug in `includes/Types/Conversions.php` and use the next number (e.g. after `conv-theme-sixteen` comes `conv-theme-seventeen`).

## File map — every theme touches ALL of these

| File | What to add |
|---|---|
| `includes/Types/Conversions.php` | Theme entry in the themes array (`'conv-theme-<n>' => [...]`) + template config (`conversions_conv-theme-<n>`) |
| `includes/Types/WooCommerceSales.php` | Same theme registered for the WooCommerce Sales type (`woocommerce_sales_conv-theme-<n>`) |
| `nxdev/notificationx/frontend/themes/GetTemplate.ts` | Map the slug to its React template/markup |
| `nxdev/notificationx/frontend/scss/_themes/_theme-<name>.scss` | New SCSS partial for the theme |
| `nxdev/notificationx/frontend/scss/_themes/_common.scss` | `@import` the new partial |
| `assets/admin/images/extensions/themes/nx-conv-theme-<n>.png` | Theme preview thumbnail shown in the builder (export from Figma; keep it small — existing ones are ~14-19KB) |

Optional, when relevant:
- `nxdev/notificationx/admin/SetupWizard/LivePreview.tsx` — if the wizard's live preview should showcase the new theme.
- `nxdev/notificationx/frontend/themes/helpers/` — shared helpers (e.g. `BrandLogo`) if the design needs one.

## Rules

- Study the most recently added theme (git log on `scss/_themes/`) and mirror its registration pattern exactly — PHP template variables and the React template must stay in sync (see the "Frontend templating quirk" note in CLAUDE.md).
- **Never run builds** (`npm run admin`/`frontend`/`build`) — the user runs a watcher.
- Reuse existing SCSS variables/mixins from `_common.scss` rather than hardcoding repeated values.
- After implementing, list the changed files and remind the user to check the theme in the builder preview once the watcher rebuilds.
