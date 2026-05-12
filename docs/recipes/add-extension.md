# Recipe: Add a New Extension (data source)

An **Extension** adds a new *data source* for an existing **Type** of notification — e.g. "WooCommerce sales" is the `WooCommerce` Extension feeding the `Conversions` (Sales) Type.

> Adding a brand-new **Type** (a new display kind, not just a new data source)? See [add-type.md](add-type.md) instead.
> Adding a **theme/design** to an existing Type? See [add-frontend-design.md](add-frontend-design.md).

This recipe takes ~30 minutes for a simple Extension. Read the whole thing once before you start.

## Mental model in one paragraph

The user picks a **Type** in the admin builder (Sales, Reviews, NotificationBar…). For that Type, they then pick a **Source** — that's your Extension. Your Extension's job is to (a) declare itself to `ExtensionFactory`, (b) declare the form fields that appear when the user picks it, and (c) return notification data when the frontend asks for it. The Extension is **module-gated**: a setting toggle (`modules_<name>`) decides whether your class is even registered. See [../../includes/Core/Modules.php](../../includes/Core/Modules.php) line 66.

## Pick the matching Type

| Type ID | Use case |
|---|---|
| `conversions` | Sales / purchase popups (WooCommerce, EDD, FluentCart) |
| `reviews` | Review popups (Google, WC, plugin reviews) |
| `comments` | Recent comments |
| `email_subscription` | Newsletter signups (Mailchimp, FluentCRM) |
| `contact_form` | Form submissions (CF7, FluentForm, WPForms, NJF) |
| `download_stats` | Download counts |
| `donations` | Donation popups (Give, etc.) |
| `notification_bar` | Top/bottom bar |
| `popup` | General-purpose popup Type |
| `exit_intent` | Exit-intent popup |
| `flashing_tab` | Tab title flasher |
| `gdpr` / `ccpa` | Compliance banners |

If your data doesn't fit any of these, you're adding a new Type, not an Extension — see [add-type.md](add-type.md).

## Steps

### 1. Create the directory and class

For an integration named `Acme` feeding the `Conversions` Type:

```
includes/Extensions/Acme/
└── AcmeConversions.php
```

Naming: `<Vendor><Type>.php`. The class name matches the file.

### 2. Minimal class skeleton

```php
<?php
namespace NotificationX\Extensions\Acme;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Types\Conversions;

class AcmeConversions extends Extension {
    use GetInstance;

    public $priority        = 20;                  // ordering within the Type's source list
    public $id              = 'acme_conversions';  // MUST be unique across all extensions
    public $types           = 'conversions';       // Type ID from the table above
    public $module          = 'modules_acme';      // settings.modules['modules_acme']
    public $module_priority = 16;                  // ordering within the Modules UI
    public $doc_link        = 'https://notificationx.com/docs/acme-notification-alert/';
    public $class           = '\Acme\Plugin';      // optional: skip registration if class missing

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'Acme', 'notificationx' );
        $this->module_title = __( 'Acme', 'notificationx' );

        // Inherit display templates from the Conversions (Sales) Type.
        $this->templates = Conversions::get_instance()->templates;
    }

    public function get_data( $args = array() ) {
        // Return an array of entries OR a single entry. Shape depends on Type.
        // For Conversions, each entry typically has:
        //   tag_name, tag_product_title, tag_time, tag_country, tag_city, image, link
        return array();
    }
}
```

The base class [Extension.php](../../includes/Extensions/Extension.php) auto-wires `init_extension()` and the hook callbacks (`save_post`, `saved_post`, `preview_entry`, `fallback_data`, `notification_image`) per-source via `nx_*_{$id}` filters.

### 3. Register the class in `ExtensionFactory`

Open [includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php) and add to `$extension_classes` (around line 31–87):

```php
'acme_conversions' => \NotificationX\Extensions\Acme\AcmeConversions::class,
```

Pro extensions skip this and instead hook into the `nx_extension_classes` filter from `notificationx-pro`.

### 4. Module toggle (settings)

Your `$module = 'modules_acme'` is the settings key. By default, missing keys are treated as **enabled** ([Modules.php:66](../../includes/Core/Modules.php)). If you want it default-off, add it to the settings defaults in [includes/Admin/Settings.php](../../includes/Admin/Settings.php).

The toggle appears in the Modules tab of NotificationX → Settings automatically (driven by `$module_title` and `$module_priority`).

### 5. Form fields (what the user configures)

Two layers:

- **Type-level fields** — defined by the Type itself (e.g. `Conversions` provides product/time/country fields). You inherit these via `$this->templates = Conversions::get_instance()->templates`.
- **Source-specific fields** — fields that only apply when your Extension is selected. Add them by hooking [`GlobalFields`](../../includes/Extensions/GlobalFields.php) or by registering a `source_tab` schema. See [add-settings-field.md](add-settings-field.md).

If your Extension just reuses the standard product/sale shape, you don't need step 5.

### 6. Fetch and store data

There are two patterns:

#### a) Polling / cron-driven
Set `$cron_schedule = 'hourly'` (or any registered interval) and override `init_extension()` to schedule a fetch. The base class wires the cron tick — your job is to fill `$wpdb->prefix . 'nx_entries'` with rows keyed by `source = $this->id` and a unique `entry_key`. See [includes/Core/Database.php](../../includes/Core/Database.php) for the `nx_entries` schema.

#### b) Event-driven
Hook a WordPress action from the source plugin (e.g. `woocommerce_order_status_completed`, `wpforms_process_complete`) and call `\NotificationX\Extensions\Extension::save_entry( $this->id, $data )` (or the equivalent helper used by the canonical extensions). Look at [includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/) for the reference pattern.

### 7. Display templates

If your data uses the standard tags (`{{tag_name}}`, `{{tag_product_title}}`, `{{tag_time}}`, etc.), inheriting `Conversions::templates` is enough. If you need custom tags, add them via the `nx_filtered_data_{$this->id}` filter — replace tag placeholders with your data before display.

### 8. POT regeneration

Run `npm run pot` after adding new translatable strings.

### 9. Test it

1. Enable your module in **NotificationX → Settings → Modules**.
2. Create a new notification: **NotificationX → Add New** → pick the matching Type → pick "Acme" as the source.
3. Step through the builder; verify your fields render.
4. Publish; verify entries land in `{$prefix}nx_entries` with `source = 'acme_conversions'`.
5. Visit a frontend page where the notification is configured to show; verify the popup renders with your data.

## Reference: canonical Extensions to copy from

| Pattern | Where to look |
|---|---|
| Standard third-party plugin integration | [includes/Extensions/WooCommerce/](../../includes/Extensions/WooCommerce/) |
| Form-submission integration | [includes/Extensions/FluentForm/](../../includes/Extensions/FluentForm/), [includes/Extensions/CF7/](../../includes/Extensions/CF7/) |
| Email/subscription integration | [includes/Extensions/MailChimp/](../../includes/Extensions/MailChimp/) |
| Webhook-style (push from external) | [includes/Extensions/Zapier/](../../includes/Extensions/Zapier/), [includes/Extensions/BitIntegrations/](../../includes/Extensions/BitIntegrations/) |

## Common pitfalls

- **Forgot to register in `ExtensionFactory::$extension_classes`** → Extension class never instantiates. Symptom: source doesn't appear in the dropdown.
- **`$id` empty or duplicated** → Silent overwrite in `ExtensionFactory::$extensions[$id]`. Symptom: another Extension's data/UI appears in place of yours.
- **`$types` set to an invalid Type ID** → `get_type()` returns `false`; Extension registers but does nothing. Verify against [TypesFactory::$types](../../includes/Types/TypesFactory.php).
- **`$module` set but missing from settings defaults and defaults to enabled** — this is *usually fine* (auto-enabled), but if you intentionally want default-off, add it to the settings defaults explicitly.
- **`$class` / `$function` / `$constant` prerequisite check fails silently** — set these only when you want the Extension to disappear when the underlying plugin/function is missing. Useful guard; easy to misdiagnose.
- **`get_data()` returns the wrong shape** → frontend renders blank/empty popup. Cross-check against the Type's expected tag keys.
- **Changed display logic in PHP but not the frontend runtime** ([nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/)) → silent desync. See [../architecture.md](../architecture.md#dual-frontend-runtimes).

## Pro version of the same Extension

If a Pro version of your Extension exists (e.g. `WooCommerceSales` in free vs Pro-extended version), the Pro plugin hooks into `nx_extension_classes` and replaces your class entry. Don't put Pro-only logic here — expose any hook points your Pro version needs and let it implement them.

See [../integrations/pro-hooks.md](../integrations/pro-hooks.md) for the contract surface.
