---
name: nx-dual-runtime-check
description: Use whenever editing PHP under includes/Types/ or includes/Extensions/ that affects notification *display* (template tags, field schema, popup structure, theme registration), OR when editing files under nxdev/notificationx/frontend/. NotificationX has a silent dual-runtime desync trap — display logic lives in BOTH PHP and a separate React bundle, and the build system won't catch divergence. This skill ensures both sides are updated together.
---

# Dual Frontend Runtime Check

NotificationX has **three webpack builds** and **three runtime contexts** (admin SPA, frontend popup runtime, Gutenberg blocks). Display logic for a notification Type lives in **two** of them simultaneously:

- **PHP** in `includes/Types/<Name>.php` (templates, field schema, defaults)
- **React** in `nxdev/notificationx/frontend/` (the actual popup rendering)

The build system does **not** detect divergence between them. A change to one without the other produces a popup that renders wrong with no error.

This is documented as **ADR 0003** (`docs/decisions/0003-dual-frontend-builds.md`) and called out in `CLAUDE.md`'s anti-patterns. It is the single most common source of "I changed the code but the popup didn't update" bugs.

## Before editing display logic

Ask yourself **all three** of these questions:

1. **Does my change rename, add, or remove a template tag** (e.g. `{{tag_product_title}}`, `{{tag_time}}`)?
2. **Does my change alter the data shape returned by an Extension's `get_data()`** or the field schema in a Type?
3. **Does my change affect the visual structure of a popup theme** (HTML/JSX layout, CSS classes the renderer depends on)?

If **any** answer is yes → both sides must be touched. Stop and audit:

```sh
# Find the React renderer for the Type you're editing
ls nxdev/notificationx/frontend/

# Grep both sides for the symbol you're changing
grep -rn "<tag_or_field_name>" includes/Types/ includes/Extensions/ nxdev/notificationx/frontend/
```

## The checklist when changing display

- [ ] PHP Type class (`includes/Types/<Name>.php`) updated.
- [ ] PHP Extension class (if data shape changed in `get_data()`).
- [ ] React renderer in `nxdev/notificationx/frontend/` updated.
- [ ] QuickBuilder field schema updated if field `name` changed (`docs/recipes/add-settings-field.md`).
- [ ] Theme registry entry in PHP (if adding/removing a theme).
- [ ] `npm run frontend` (or `npm run frontend-watch`) rebuilt the bundle.
- [ ] If touching blocks: `npm run bb` too.
- [ ] If touching translatable strings: `npm run pot`.

## Symptoms of having missed a side

| Symptom | Likely cause |
|---|---|
| Popup renders blank where it used to show content | Frontend reads a tag the PHP no longer provides, or vice versa |
| New theme shows in admin design picker but renders blank | PHP theme registered, React component not registered |
| New theme renders but doesn't appear in design picker | React component registered, PHP theme registration missing |
| Builder shows a field but the popup ignores it | Field exists in schema, frontend renderer doesn't read it |
| Popup shows raw `{{tag_xxx}}` text | Frontend's tag replacement doesn't know about this tag |

## When in doubt

Open the matching frontend file before editing PHP. Open the matching PHP file before editing frontend. Treat them as a pair.

## Reference

- `docs/architecture.md § dual frontend runtimes`
- `docs/decisions/0003-dual-frontend-builds.md`
- `docs/recipes/add-frontend-design.md` — the four layers a new theme touches.
