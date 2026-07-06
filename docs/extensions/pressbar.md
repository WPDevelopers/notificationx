# PressBar Extension (`modules_bar`)

> The built-in Notification Bar integration. Unlike most other Extensions, PressBar is
> not a third-party data-source connector — it is a config-only Extension whose
> "data" is the bar's own saved content, optionally designed with the **Elementor**
> page builder or the **Gutenberg** block editor.

## At a glance

| | |
|---|---|
| **Integration** | `PressBar` (Notification Bar) |
| **Directory** | [`includes/Extensions/PressBar/`](../../includes/Extensions/PressBar/) |
| **Module key(s) (`$module`)** | `modules_bar` |
| **Feeds Types** | `notification_bar` |
| **Extension classes** | `PressBar.php` → `(notification_bar, press_bar)` |
| **Depends on** | Nothing required. Optionally detects **Elementor** (`\Elementor\Plugin` class) for page-builder design, and **Essential Blocks** (`essential-blocks/essential-blocks.php`) for the Gutenberg-block bar theme, upselling installation when either is missing. |

## What it does

PressBar powers the "Notification Bar" type — a sticky bar (top/bottom of the page)
typically used for announcements, countdowns, and promotional CTAs. There is no
external plugin whose events drive it: the admin builds the bar's text, button,
colors, and (optionally) a countdown directly in the NotificationX builder, and the
frontend renders that saved configuration verbatim.

Two optional page-builder integrations exist on top of the base bar:

- **Elementor** — the bar can be designed visually with Elementor. `register_post_type()`
  registers a `nx_bar` CPT with `'elementor'` in its `supports` array, and
  [`importer.php`](../../includes/Extensions/PressBar/importer.php) defines
  `Importer extends \Elementor\TemplateLibrary\Source_Local` to import one of the
  bundled Elementor theme templates from
  [`jsons/`](../../includes/Extensions/PressBar/jsons/) (`theme-one.json` … `theme-five.json`)
  into a new Elementor document. Presence is detected via
  `class_exists('\Elementor\Plugin')`; without it, Elementor-specific fields/buttons
  (edit link, build-with-Elementor, etc.) are gated off via `Rules::is('is_elementor', false)`.
- **Gutenberg / Essential Blocks** — a second CPT, `nx_bar_eb`, is registered with
  `show_in_rest => true` for block-editor–based bar design, backed by templates in
  [`jsons-gb/`](../../includes/Extensions/PressBar/jsons-gb/) (`theme-one.json` …
  `theme-seven.json`). `load_plugin_dependencies()` checks
  `Helper::is_plugin_active('essential-blocks/essential-blocks.php')` and, if the
  plugin is not active, sets `$this->popup` to a forced upsell dialog prompting the
  user to install Essential Blocks.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`PressBar.php`](../../includes/Extensions/PressBar/PressBar.php) | `notification_bar` | `press_bar` | No `get_data()` method exists on this class — PressBar does not pull data from any external source/table. The rendered content comes from the notification's own saved post settings via `FrontEnd::get_bar_content()`, which reads fields like `press_content` / `sliding_content`, and, if `elementor_id` is set and Elementor is active, from `\Elementor\Plugin::$instance->frontend->get_builder_content_for_display()`. |

`$module_priority = 1`, `$priority = 5`, `$default_theme = 'press_bar_theme-two'`.

## Data flow

There is no source-event → `get_data()` step for PressBar. Instead:

1. Admin saves a `notification_bar` / `press_bar` notification (post type `nx_bar`,
   or `nx_bar_eb` when built with Gutenberg blocks) with its own content fields
   (`press_content`, `sliding_content`, button/countdown/design settings) — see
   `save_post()` in `PressBar.php` (strips transient flags like `is_elementor`,
   `is_confirmed`, `is_gutenberg`, `is_gb_confirmed`, and normalizes
   `countdown_start_date`).
2. [`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) buckets notifications whose
   `source == 'press_bar'` into the `pressbar` group inside
   `get_notifications_ids()` (routing branch at line ~194) and `get_notifications_data()`
   (result key `pressbar`, param `pressbar`).
3. In `get_notifications_data()`, for each bucketed notification: `bottom_left`
   position is remapped to `top`; if `elementor_id` is set but the Elementor post
   isn't published or Elementor isn't active, `elementor_id` is cleared; the button
   URL is filtered through `nx_notification_link`; and `get_bar_content()` produces
   the HTML body. The result shape is `{ post: {...settings}, content: "<html>" }`
   per `nx_id` — consumed on the frontend via `normalizePressBar()` (config + single
   content blob, not a multi-entry list).
4. `insert_views()` (hooked to `nx_filtered_data_{$this->id}`) records a view via
   `Analytics::get_instance()->insert_views()` on the public/frontend pass.

## Fields & settings

- Content tab: `bar_content_type` (`static` vs pro-gated `sliding`), `press_content`
  (rich text via `nx-editor`), `sliding_content` (repeater, pro), plus countdown and
  button fields — added via `content_fields()` on `nx_content_fields`.
- Design tab: `design_tab_fields()`, `design_tab_presets_fields()`, and
  `design_tab_fields_for_button()` add bar colors/borders/counter styling, hooked on
  `nx_design_tab_fields`.
- Customize tab: `customize_fields()` on `nx_customize_fields`.
- Display tab: `hide_image_field()` and `display_fields()` on `nx_display_fields`.
- Elementor-specific fields (`is_elementor`, `elementor_id`, `elementor_edit_link`,
  build/import-with-Elementor buttons) are added inside `design_tab_fields()` and
  gated with `Rules::is('is_elementor', …)` / `Rules::is('elementor_id', …)`.
- Several option lists (country list, WP roles, etc.) are normalized via
  `GlobalFields::get_instance()->normalize_fields(...)` rather than being redefined
  locally — see call sites in `PressBar.php` (e.g. `Helper::nx_get_all_country()`
  for the country options, `$wp_roles_with_default` for role options).

## Dependency & detection

- **Elementor** (optional): detected with `class_exists('\Elementor\Plugin')`
  (used repeatedly, e.g. `init_extension`'s `get_edit_post_link` filter, `nx_get_post()`,
  `get_bar_content()`-adjacent code, and the `is_elementor` field default). When
  absent, Elementor-only UI (edit-with-Elementor link, Elementor theme picker,
  build/import-with-Elementor actions) is hidden via `Rules`, and any stored
  `elementor_id` is treated as unset when rendering.
- **Essential Blocks** (optional, for the Gutenberg/block bar experience): detected
  with `Helper::is_plugin_active('essential-blocks/essential-blocks.php')` in
  `load_plugin_dependencies()` (hooked on `init`, priority `-1`). When inactive, a
  forced popup (`$this->popup`) upsells installing Essential Blocks; the underlying
  `nx_bar_eb` CPT and `jsons-gb/` templates still exist but the feature is presented
  as unavailable until the dependency is installed.
- Neither dependency gates the base Notification Bar itself — a plain bar (static or
  pro sliding text, button, countdown) works with no third-party plugin at all;
  Elementor/Essential Blocks only unlock the respective visual-builder design paths.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/PressBar/PressBar.php`](../../includes/Extensions/PressBar/PressBar.php) |
| Elementor template importer | [`includes/Extensions/PressBar/importer.php`](../../includes/Extensions/PressBar/importer.php) |
| Elementor bar theme templates | [`includes/Extensions/PressBar/jsons/`](../../includes/Extensions/PressBar/jsons/) |
| Gutenberg/Essential-Blocks bar theme templates | [`includes/Extensions/PressBar/jsons-gb/`](../../includes/Extensions/PressBar/jsons-gb/) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'press_bar' => 'NotificationX\Extensions\PressBar\PressBar'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Frontend data routing/rendering | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (`pressbar` bucket, `get_bar_content()`) |

## Testing notes & gotchas

- Because there is no `get_data()`, changes here are almost entirely about the
  saved post settings and rendering path — test by saving a `notification_bar`
  notification with each `bar_content_type` and verifying `get_bar_content()`
  output, not by mocking an external API.
- When testing Elementor mode: verify behavior both with `\Elementor\Plugin`
  present and absent (fields must hide/show correctly, `elementor_id` must be
  cleared when the Elementor post isn't `publish`).
- When testing Gutenberg/Essential Blocks mode: verify the forced popup appears
  only when `essential-blocks/essential-blocks.php` is inactive, and that the
  `nx_bar_eb` CPT / `jsons-gb/` templates are otherwise unaffected.
- `bottom_left` position is silently remapped to `top` in
  `get_notifications_data()` — don't be surprised if a bar saved as `bottom_left`
  renders at the top.
- _TODO: verify_ whether there are dedicated automated tests under `tests/` for
  PressBar; none were found during this pass.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/) (`notification_bar` Type — see the `NotificationBar` Type class under `includes/Types/`, not yet doc'd; _TODO: verify_ link once that doc exists)
