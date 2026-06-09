# Exit Intent Popup — Elementor Builder Support

Goal: let users design an Exit Intent Popup with Elementor (in addition to the existing seven built-in React themes). Mirrors the PressBar approach documented in [../../notification-bar-reference.md](../../notification-bar-reference.md).

Reference UI we are matching (Notification Bar's *Custom* tab):

```
THEMES
  [Presets]  [Custom (E)(G)]  [Build With AI]
                  └── Create with Your Preferred Builder/Editor
                       [ Build With Elementor ]   [ Build With Gutenberg ]
```

For Exit Intent, only the **Elementor** half of the Custom tab is in scope here. Gutenberg gets its own task list later.

---

## Task index

Each task is incremental — finish + verify before moving to the next.

| # | Task | Status |
|---|------|--------|
| 01 | [Register `nx_exit_intent` post type](./01-register-post-type.md) | done |
| 02 | [Add an Elementor `Importer` for Exit Intent seed JSONs](./02-importer.md) | done |
| 03 | [REST endpoints — `/exit-intent/elementor/import` + `/remove`](./03-rest-endpoints.md) | done |
| 04 | [Admin "Custom" tab — Build / Edit / Remove with Elementor buttons + modal](./04-admin-ui.md) | done |
| 05 | [Hidden state fields — `elementor_id`, `is_elementor`, `is_confirmed`, edit link](./05-state-fields.md) | done |
| 06 | [Suppress built-in theme fields when `elementor_id` is set](./06-suppress-builtin-fields.md) | done |
| 07 | [Seed Elementor JSON templates in `jsons/`](./07-seed-templates.md) | partial (theme-one only) |
| 08 | [Frontend render — inject Elementor HTML into the React popup shell](./08-frontend-render.md) | done |
| 09 | [Lifecycle — `saved_post`, `nx_get_post`, `nx_delete_post`, WPML](./09-lifecycle.md) | todo |
| 10 | [Integrations — WP Rocket RUCSS safelist, `get_edit_post_link` filter](./10-integrations.md) | todo |

> Gutenberg support is a parallel track — same shape, separate set of tasks. We'll spin those up once 01–10 land.

---

## Conventions for this track

- The extension ID stays `exit_intent_custom` (see [`ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php) line 23). All new `Rules::is('source', $this->id, …)` clauses must use `$this->id`, not a hardcoded string.
- The new Elementor-backed post type is **`nx_exit_intent`** — distinct from PressBar's `nx_bar`. Re-using `nx_bar` would collide with PressBar's `get_edit_post_link` filter, theme_preview branching, and Templately push label.
- New seed JSON folder: [`includes/Extensions/ExitIntent/jsons/`](../../../includes/Extensions/ExitIntent/jsons/) (already exists, currently empty).
- Image previews for Elementor themes go under [`assets/admin/images/extensions/themes/exit-intent-elementor/`](../../../assets/admin/images/extensions/themes/exit-intent-elementor/) (folder already exists).
- The user can pick **either** a built-in React theme **or** an Elementor design — never both. Once `elementor_id` is a number, the built-in `themes` radio + all per-theme content/design fields hide.

---

## Definition of done (whole track)

- [ ] User opens Exit Intent campaign → Custom tab → clicks *Build With Elementor* → picks a seed theme → import succeeds → Elementor editor opens with that design.
- [ ] After editing in Elementor and clicking *Update*, returning to NotificationX shows *Edit With Elementor* + *Remove* buttons.
- [ ] Triggering exit intent on the frontend displays the Elementor-built popup inside NotificationX's overlay + close button + sensitivity / cookie chrome.
- [ ] Deleting the campaign also deletes the linked `nx_exit_intent` Elementor post (and its WPML translations).
- [ ] WP Rocket RUCSS doesn't strip the Elementor-built popup's CSS.
