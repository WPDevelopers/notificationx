---
name: new-extension
description: Scaffold a new NotificationX integration (Extension) for an existing notification Type — e.g. a new form plugin, ecommerce, or LMS source. Follows the in-repo development guide and the canonical WooCommerce pattern, and creates the matching docs entry. Use when the user asks to add/integrate a new data source or integration.
tools: Read, Edit, Write, Bash, Glob, Grep, AskUserQuestion
---

# New Extension (Integration) Scaffold

Creates a new data-source Extension. The **authoritative guide is in-repo**: read [docs/development/adding-an-extension.md](../../../docs/development/adding-an-extension.md) FIRST and follow it. This skill is the checklist on top.

## Step 0 — Inputs

1. **Integration name** (e.g. "SureCart") and the **Type(s)** it feeds (`conversions`, `form`, `email_subscription`, etc. — see `includes/Types/TypesFactory.php` for valid ids).
2. Check the target plugin's hooks/API for capturing the data (order created, form submitted, …).

## Checklist

1. **Class**: `includes/Extensions/<Name>/<Name><Type>.php` extending `NotificationX\Extensions\Extension`:
   - Set `$id`, `$types`, `$module` (settings key, `modules_<id>` convention), `$doc_link`, `$img`.
   - Implement `init_extension()` (builder UI/popup config) and `get_data()` (data fetching).
   - Register in `includes/Extensions/ExtensionFactory.php`.
2. **Canonical reference**: mirror the closest existing extension — `includes/Extensions/WooCommerce/` for sales, `CF7` for forms, `MailChimp` for email subscription.
3. **Fields**: shared form fields go through `includes/Extensions/GlobalFields.php` — reuse before inventing new ones.
4. **Module gating**: the extension only loads when its `$module` settings key is enabled — verify it appears under Settings → Modules.
5. **Docs**: create `docs/extensions/<slug>.md` from [docs/extensions/_TEMPLATE.md](../../../docs/extensions/_TEMPLATE.md), following [docs/development/documentation-conventions.md](../../../docs/development/documentation-conventions.md).
6. **Pro?** If the integration is Pro-gated, it belongs in `notificationx-pro`, not this repo — same structure, hooks in via the shared Extension system.

## Rules

- Singletons via `GetInstance` trait — never `new` core classes.
- No builds — the user runs a watcher for any JS/admin UI changes.
- Finish by summarizing created/edited files + what manual testing is needed (trigger the source event, confirm the notification shows).
