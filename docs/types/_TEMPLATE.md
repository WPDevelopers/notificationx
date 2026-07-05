# <Human Name> Notification Type (`<type_id>`)

> One or two sentences: what this notification renders for the end visitor, and the
> real-world use case it serves (e.g. "shows a live feed of recent WooCommerce
> purchases to build social proof").

## At a glance

| | |
|---|---|
| **Type ID** | `<type_id>` |
| **Class** | [`includes/Types/<Class>.php`](../../includes/Types/<Class>.php) |
| **Trait** | [`includes/Types/Traits/<Class>.php`](../../includes/Types/Traits/<Class>.php) — _or "none"_ |
| **Priority** | `<$priority>` |
| **Default source** | `<$default_source>` |
| **Default theme** | `<$default_theme>` |
| **Module gate (`$module`)** | `modules_*` keys that must be ON for this type to load |
| **Compatible extensions** | Extensions whose `$types` include `<type_id>` (data sources) |

## What it does

Explain the behaviour from the user's perspective — what appears on the site, how it
is triggered, what data drives it. Keep it grounded in the actual code, not marketing.

## Data flow

Trace one notification end-to-end for THIS type:
`Extension::get_data()` → stored/entries → `FrontEnd.php` routing → REST → React runtime.
Note anything type-specific (custom template ids, `display_types`, link handling).

## Fields & settings schema

The distinctive builder fields this type declares (via its trait / `init_fields`).
List the notable field groups and any type-specific settings keys. Don't dump every
global field — focus on what makes this type different.

## Themes / templates

Available themes (`$themes` / `$res_themes`) and the template ids they map to.

## Key files

| Layer | File(s) |
|---|---|
| Type class | `includes/Types/<Class>.php` |
| Trait | `includes/Types/Traits/<Class>.php` |
| Extensions | `includes/Extensions/<...>` |
| Frontend runtime | `nxdev/notificationx/frontend/...` |
| PHP frontend | `includes/FrontEnd/FrontEnd.php` (routing touch-points) |

## Dependencies

Third-party plugins required for this type's data sources (e.g. WooCommerce, EDD,
LearnDash). State "none — core WordPress only" if it has no external dependency.

## Testing notes & gotchas

- What to verify after changing this type (PHP + React desync risks).
- Known edge cases, module-gating behaviour, migration concerns.
- Relevant existing tests (if any) under `tests/`.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- _links to sibling type/extension docs_

---
<!--
AUTHORING RULES (delete this comment in the final file):
1. Only document what you can VERIFY in the source. If a value is unknown, write
   `_TODO: verify_` — never invent field names, theme ids, or behaviour.
2. Use real file paths as clickable relative links from docs/types/.
3. Match the depth and tone of docs/new-notification-type.md.
-->
