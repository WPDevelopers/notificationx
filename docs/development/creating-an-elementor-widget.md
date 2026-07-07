# Creating an Elementor Widget

NotificationX ships two standalone **Elementor widgets** — a Countdown Timer and a Form — that live in the "NotificationX" widget category inside the Elementor editor. They are registered independently of the Type/Extension system by [`ElementorManager`](../../includes/Extensions/Elementor/ElementorManager.php), and only require the **free** Elementor plugin. This is a separate concern from the `elementor_form` *data-source Extension* ([`From.php`](../../includes/Extensions/Elementor/From.php)), which turns Elementor **Pro** form submissions into `form`-Type notifications — see the [Elementor Extension doc](../extensions/elementor.md) for that.

## Registering a widget

`ElementorManager` is the single registrar. It is bootstrapped from [`includes/NotificationX.php`](../../includes/NotificationX.php) only once base Elementor has loaded:

```php
if ( did_action( 'elementor/loaded' ) ) {
    ElementorManager::get_instance();
} else {
    add_action( 'elementor/loaded', [ ElementorManager::class, 'get_instance' ] );
}
```

Its constructor hooks Elementor's registration actions:

```php
add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
add_action( 'elementor/widgets/register',               [ $this, 'register_widgets' ] );
add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
add_action( 'elementor/frontend/after_register_styles',  [ $this, 'register_styles' ] );
```

- `register_category()` adds the `notificationx` category via `$elements_manager->add_category('notificationx', [...])`.
- `register_widgets()` instantiates and registers each widget: `$widgets_manager->register( new CountdownWidget() );`
- `register_scripts()` / `register_styles()` `wp_register_script/style()` the shared handles (`nx-countdown`, `nx-elementor-form`) from `NOTIFICATIONX_PUBLIC_URL`; widgets pull them in through `get_script_depends()` / `get_style_depends()` so Elementor enqueues them only when the widget is on the page (both in the editor preview iframe and on the public frontend).

**The widget class** extends `\Elementor\Widget_Base`. The methods NotificationX's widgets implement (see [`CountdownWidget.php`](../../includes/Extensions/Elementor/CountdownWidget.php)):

| Method | Returns |
| --- | --- |
| `get_name()` | Unique machine name — `'nx-countdown-timer'` (CountdownWidget), `'nx-form'` (FormWidget). |
| `get_title()` | Display name in the widget panel. |
| `get_icon()` | Elementor `eicon-*` icon class. |
| `get_categories()` | `[ 'notificationx' ]` — the category registered above. |
| `get_keywords()` | Search terms in the widget panel. |
| `get_script_depends()` / `get_style_depends()` | Handles registered by `ElementorManager` (e.g. `[ 'nx-countdown' ]`). |
| `register_controls()` | Builds the settings UI with `start_controls_section()` + `add_control()` (`Controls_Manager::SELECT`, `DATE_TIME`, `NUMBER`, `SWITCHER`, group controls for typography/background/border, …). |
| `render()` | Reads `$this->get_settings_for_display()` and echoes the frontend markup. |

To add a **new** widget: create `includes/Extensions/Elementor/<Name>Widget.php` extending `Widget_Base` with the methods above, register any JS/CSS handles it needs inside `ElementorManager::register_scripts()` / `register_styles()`, and add one line to `ElementorManager::register_widgets()`:

```php
$widgets_manager->register( new YourWidget() );
```

Because the plugin autoloads via a Composer classmap, run `composer dump-autoload` (or add the class to `vendor/composer/autoload_classmap.php` + `autoload_static.php`) so the new widget class resolves.

## Existing widgets

Both are registered in [`ElementorManager::register_widgets()`](../../includes/Extensions/Elementor/ElementorManager.php); neither is module-gated (they load whenever Elementor is active):

- **`CountdownWidget`** ([CountdownWidget.php](../../includes/Extensions/Elementor/CountdownWidget.php)) — `get_name() = 'nx-countdown-timer'`, title "NotificationX Countdown Timer". A self-contained due-date or evergreen/recurring countdown. It does not read or write any NotificationX campaign/entry — it just reuses NX's `nx-countdown` JS/CSS. Its markup is mirrored by the Gutenberg countdown block's `countdown_render_callback()` in [`blocks/Blocks.php`](../../blocks/Blocks.php), so the two builders render identically.
- **`FormWidget`** ([FormWidget.php](../../includes/Extensions/Elementor/FormWidget.php)) — `get_name() = 'nx-form'`, title "NotificationX Form". A Name/Email/Message form bound (via its `nx_campaign_id` control) to an *existing* NotificationX campaign whose `source` is `popup_notification` or `exit_intent_custom`. On submit it POSTs to the `notificationx/v1/popup-submit` REST route ([`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php)) — the same endpoint the built-in Popup / Exit-Intent forms use. Its internal `is_pro()` check tests `class_exists('\NotificationXPro\NotificationX')` (i.e. **NotificationX Pro**, not Elementor Pro): the free plugin forces single-column layout and drops the Email field.

## Elementor-designed popups

Separately from the widgets, NotificationX supports designing an **Exit Intent Popup** *with* Elementor — using a full Elementor template as the popup's design in place of the built-in React themes. That feature is documented as its own multi-part track under [../features/exit-intent/elementor/](../features/exit-intent/elementor/) (register the `nx_exit_intent` post type, an importer for seed templates, REST import/remove endpoints, the admin "Custom" tab, and frontend injection of the Elementor HTML into the React popup shell). It is a distinct mechanism from the `Widget_Base` widgets above — it does not add a widget to the Elementor panel; it embeds an Elementor-built template as a campaign's popup body. See [00-overview.md](../features/exit-intent/00-overview.md) and the [elementor/README.md](../features/exit-intent/elementor/README.md) task index.

## Source
- [../../includes/Extensions/Elementor/ElementorManager.php](../../includes/Extensions/Elementor/ElementorManager.php) — category + widget + asset registration.
- [../../includes/Extensions/Elementor/CountdownWidget.php](../../includes/Extensions/Elementor/CountdownWidget.php) · [../../includes/Extensions/Elementor/FormWidget.php](../../includes/Extensions/Elementor/FormWidget.php) — the two `Widget_Base` widgets.
- [../../includes/Extensions/Elementor/From.php](../../includes/Extensions/Elementor/From.php) — the `elementor_form` data-source Extension (Elementor Pro; distinct from the widgets).
- [../extensions/elementor.md](../extensions/elementor.md) — full breakdown of the directory.
- [../features/exit-intent/elementor/](../features/exit-intent/elementor/) — Elementor-designed Exit Intent popups.
