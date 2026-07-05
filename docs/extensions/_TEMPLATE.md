# <Integration Name> Extension (`<primary module key>`)

> One or two sentences: which third-party plugin / service this integration connects
> NotificationX to, and what data it surfaces (e.g. "pulls completed Easy Digital
> Downloads sales to drive Sales / Download-Stats notifications").

## At a glance

| | |
|---|---|
| **Integration** | `<Integration Name>` |
| **Directory** | [`includes/Extensions/<Dir>/`](../../includes/Extensions/<Dir>/) |
| **Module key(s) (`$module`)** | `modules_*` key(s) that gate this integration |
| **Feeds Types** | which notification Type IDs this integration provides data for (from each class's `$types`) |
| **Extension classes** | list every `<Name><Type>.php` in the dir and the `(type, id)` pair it registers |
| **Depends on** | third-party plugin/service required, and how presence is detected |

## What it does

From the user's perspective: what has to be installed/connected, what notifications
become available, and what real events drive them. Grounded in the code.

## Extension classes & pairings

For each class in the directory, one row: the Type it pairs with, its `$id`, its
`$module`, and what `get_data()` returns. This is the heart of the doc.

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| `<Name>Sales.php` | `conversions` | … | … |

## Data flow

Trace one notification: source event → `get_data()` → entries/storage → FrontEnd → REST → React.
Note polling vs realtime, caching, and any API/webhook (Zapier, IFTTT, MailChimp).

## Fields & settings

Distinctive builder fields / settings keys this integration adds (source lists,
account connection, API keys). Reference `GlobalFields` where it reuses shared fields.

## Dependency & detection

- Required plugin/service and minimum version if enforced.
- How the code detects it (e.g. `class_exists`, `function_exists`, option check) and
  what happens when it is absent (module hidden / silently skipped).

## Key files

| Purpose | File |
|---|---|
| Extension classes | `includes/Extensions/<Dir>/*.php` |
| Registration | `includes/Extensions/ExtensionFactory.php` |
| Shared fields | `includes/Extensions/GlobalFields.php` |

## Testing notes & gotchas

- What to verify after changing this integration (data shape, module gating, desync
  with the React frontend).
- API/rate-limit or auth concerns for service integrations.
- Relevant existing tests under `tests/` (if any).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- Related Type docs under [../types/](../types/)

---
<!--
AUTHORING RULES (delete this comment in the final file):
1. Document only what you can VERIFY in the source. Unknown -> `_TODO: verify_`.
   Never invent class names, $id/$module values, field names, or API behaviour.
2. Use real clickable relative links from docs/extensions/.
3. One doc per INTEGRATION (directory), covering all its extension classes.
-->
