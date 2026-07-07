# Adding an Extension (Data Source)

An **Extension** is a data source that feeds an existing **Type**. Adding one lets NotificationX turn events from a plugin or service (a new WooCommerce order, a Mailchimp signup, an incoming Zapier webhook) into notifications for a Type that already exists. To add a whole new *display category* instead — a new kind of notification with its own themes and frontend renderer — see [adding-a-notification-type.md](adding-a-notification-type.md).

Every Extension extends the abstract base class [`Extension`](../../includes/Extensions/Extension.php) and is registered in [`ExtensionFactory`](../../includes/Extensions/ExtensionFactory.php). The base class already wires the source into the QuickBuilder UI (source dropdown, themes, templates, module toggle) through `__init_fields()` — you only override the handful of methods that describe *your* source and fetch its data.

The canonical example is [`includes/Extensions/WooCommerce/`](../../includes/Extensions/WooCommerce/); a simpler pull-based example is [`includes/Extensions/EDD/`](../../includes/Extensions/EDD/).

## 1. Pick the Type it feeds

An Extension declares which Type it belongs to via its `$types` property — this string must equal the target Type's `$id`. Existing Type IDs include `conversions` (Sales), `form` (Contact Form), `reviews`, `email_subscription`, and the config-only types `press_bar`, `popup`, `exit_intent`, `flashing_tab`. Check [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) for the authoritative list.

`$types` is a single Type ID. One *directory* can hold several extension classes that each feed a different Type — WooCommerce ships `WooCommerce.php` (→ `conversions`), `WooReviews.php` (→ `reviews`), plus inline variants — but each class registers exactly one `(type, id)` pair. That is the `<Name>/<Name><Type>.php` convention: one file per Type the integration supports.

## 2. Create the class

Create `includes/Extensions/<Name>/<Name><Type>.php` under the `NotificationX\Extensions\<Name>` namespace, extending `Extension` and using the `GetInstance` trait. Set the identifying properties, then implement `init_extension()`:

```php
namespace NotificationX\Extensions\Acme;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Types\Conversions;

class AcmeSales extends Extension {
    use GetInstance;

    public $priority        = 25;                 // Order in the source dropdown
    public $id              = 'acme_sales';        // Unique — the `source` stored in post meta
    public $types           = 'conversions';       // Must equal the target Type's $id
    public $module          = 'modules_acme';       // Settings key that gates the whole integration
    public $module_title    = '';                   // Set in init_extension()
    public $class           = '\Acme\Plugin';       // Presence check — see step 3
    public $doc_link        = 'https://notificationx.com/docs/...';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'Acme', 'notificationx' );
        $this->module_title = __( 'Acme', 'notificationx' );
        // Optionally reuse a Type's templates, e.g.:
        // $this->templates = Conversions::get_instance()->templates;
    }
}
```

Key properties (all declared on [`Extension`](../../includes/Extensions/Extension.php)):

| Property | Purpose |
|---|---|
| `$id` | Globally-unique source slug. Stored as `source` in the campaign's post meta and used in every `nx_*_{$this->id}` dynamic filter. Must match its registration key in `ExtensionFactory`. |
| `$types` | The Type ID this source feeds (equals a Type's `$id`). |
| `$module` | The `modules_*` settings key that gates the integration. If the module is disabled the extension never initializes (see step 3). |
| `$module_title` | Human label shown on the Modules settings card; set in `init_extension()`. |
| `$class` / `$function` / `$constant` | Dependency presence checks (any one, see step 3). |
| `$is_pro` | `true` locks the source behind Pro; the actual data logic then lives in `notificationx-pro`. |
| `$priority` / `$module_priority` | Ordering in the source dropdown / Modules grid. |
| `$cron_schedule` | If set, saving a campaign schedules a recurring refresh (see step 4). |

The base constructor registers the module, and — only if the module is enabled — registers the Type and calls `initialize()`, which hooks `init_extension()`, `init_fields()`, and (when the source is actually in use) `init()`, `admin_actions()`, `public_actions()`.

## 3. Dependency detection

Two independent gates decide whether an Extension does anything:

1. **Module toggle.** The base constructor calls `Modules::get_instance()->is_enabled($this->module)` — a module turned off in **NotificationX → Settings → General → Modules** short-circuits everything. `ExtensionFactory::register_extensions()` also skips adding the object at all when the module is disabled. Your `$module` key is auto-registered as a Modules card by `Extension::register_module()`.

2. **Third-party dependency.** Set exactly one of `$class`, `$function`, or `$constant` to something that only exists when the source plugin/service is active. `Extension::is_active()` and `class_exists()` (the method) check these:

```php
public $class    = '\WooCommerce';                 // WooCommerce.php
public $function = 'wc_get_orders';                 // any function
public $constant = 'ACME_VERSION';                  // any defined constant
```

When the dependency is missing, `is_active(false)` returns `false`, so `init()` / `admin_actions()` / `public_actions()` / `init_fields()` never run. To tell the user *why* the source is inert, implement `source_error_message()` — the base class auto-hooks it, and it renders an admin notice scoped with `Rules::is('source', $this->id)`:

```php
public function source_error_message( $messages ) {
    if ( ! $this->class_exists() ) {
        $messages[ $this->id ] = [
            'message' => __( 'You have to install Acme plugin first.', 'notificationx' ),
            'html'    => true,
            'type'    => 'error',
            'rules'   => \NotificationX\Core\Rules::is( 'source', $this->id ),
        ];
    }
    return $messages;
}
```

See [`WooCommerce::source_error_message()`](../../includes/Extensions/WooCommerce/WooCommerce.php) and [`Elementor/From::source_error_message()`](../../includes/Extensions/Elementor/From.php) for real examples.

## 4. Implement the behavior

There are two data-acquisition patterns; pick the one that matches your source.

**Realtime / event-driven** (WooCommerce, form integrations). Override `init()` to hook the source's own actions, build an entry, and persist it through the inherited helpers `save()`, `update_notification()`, or `update_notifications()`. Implement `get_notification_ready($post)` so the campaign can be (re)generated on demand — [`PostType`](../../includes/Core/PostType.php) exposes a "regenerate" action for any extension that defines it:

```php
public function init() {
    parent::init();
    add_action( 'acme_order_completed', [ $this, 'save_new_order' ], 10, 2 );
}

public function get_notification_ready( $post = [] ) {
    // fetch recent items, shape each into
    // [ 'nx_id' => ..., 'source' => $this->id, 'entry_key' => ..., 'data' => [...] ]
    // then $this->update_notifications( $entries );
}
```

**Pull / polling** (Mailchimp, Envato, Zapier, Google Analytics). Implement `get_data( $args = [] )` returning normalized entries; it is invoked through [`ExtensionFactory::getExtension()`](../../includes/Extensions/ExtensionFactory.php). To refresh on a schedule, set `$cron_schedule` — [`PostType`](../../includes/Core/PostType.php) then registers a WP-cron event via `Cron::set_cron()` when the campaign is saved. Compare [`MailChimp::get_data()`](../../includes/Extensions/MailChimp/MailChimp.php) and [`ZapierConversions::get_data()`](../../includes/Extensions/Zapier/ZapierConversions.php).

**Builder fields.** Override `init_fields()` (auto-hooked when the source is active) to add source-specific fields to the Content / Design / Customize tabs, always gating them so they only appear for your source:

```php
public function init_fields() {
    parent::init_fields();
    add_filter( 'nx_content_fields', [ $this, 'content_fields' ], 99 );
}

public function content_fields( $fields ) {
    $fields['acme_section'] = [
        'type'   => 'section',
        'rules'  => \NotificationX\Core\Rules::is( 'source', $this->id ),
        'fields' => [ /* ... */ ],
    ];
    return $fields;
}
```

Reuse the shared field registry in [`GlobalFields`](../../includes/Extensions/GlobalFields.php) (e.g. `normalize_fields()`) rather than re-declaring common option lists — see how `WooCommerce::order_status()` and `WooCommerce::link_types()` feed options through it.

> **Pro note:** if `$is_pro = true`, this free-plugin class typically only supplies `init_extension()`, `source_error_message()`, and `doc()`; the real capture/`get_data()` logic lives in the sibling `notificationx-pro` plugin and must not be added here. [`Elementor/From.php`](../../includes/Extensions/Elementor/From.php) is the model for this shape.

## 5. Register & document

Add your class to the `$extension_classes` map in [`ExtensionFactory`](../../includes/Extensions/ExtensionFactory.php) — the array key **must** equal your `$id`:

```php
public $extension_classes = [
    // ...
    'acme_sales' => 'NotificationX\Extensions\Acme\AcmeSales',
];
```

At registration time the whole map is passed through the `nx_extension_classes` filter, so `notificationx-pro` (or any add-on) can inject additional sources without editing this file:

```php
add_filter( 'nx_extension_classes', function ( $classes ) {
    $classes['acme_sales'] = 'NotificationX\Extensions\Acme\AcmeSales';
    return $classes;
} );
```

Because NotificationX autoloads via a Composer classmap, also register the class in the autoloader — run `composer dump-autoload`, or add the entry manually to `vendor/composer/autoload_classmap.php` and `autoload_static.php` (same procedure as Step 4 of [adding-a-notification-type.md](adding-a-notification-type.md)).

Finally, document the integration: copy [`../extensions/_TEMPLATE.md`](../extensions/_TEMPLATE.md) to `../extensions/<name>.md`, fill in the `(type, id, module)` pairings and data flow, and add a row to [`../extensions/README.md`](../extensions/README.md). Cross-link any hooks you introduced in [`../api/hooks-filters.md`](../api/hooks-filters.md).

## Source
- [../../includes/Extensions/Extension.php](../../includes/Extensions/Extension.php) — abstract base class (`$id`, `$types`, `$module`, `init_extension()`, `is_active()`, `save()`/`update_notification()`).
- [../../includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php) — `$extension_classes` map, `nx_extension_classes` filter, `getExtension()`.
- [../../includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php) — shared builder-field registry.
- [../../includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/) — canonical realtime example.
- [../../includes/Extensions/EDD/](../../includes/Extensions/EDD/), [../../includes/Extensions/MailChimp/MailChimp.php](../../includes/Extensions/MailChimp/MailChimp.php) — pull/`get_data()` examples.
- [../api/hooks-filters.md](../api/hooks-filters.md) · [../extensions/_TEMPLATE.md](../extensions/_TEMPLATE.md)
