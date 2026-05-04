# Skill: new-notification-type

Create a new notification type in the NotificationX plugin end-to-end.

Trigger phrases: "create a new notification type", "add a notification type", "new nx type", "build a type"

---

## What this skill does

Scaffolds and wires up a complete new notification type by:
1. Collecting required information from the user
2. Creating the Type class under `includes/Types/`
3. Registering it in `TypesFactory.php`
4. Optionally creating a companion Extension class and registering it in `ExtensionFactory.php`
5. Running `composer dump-autoload`

---

## Step 0 — Gather inputs

Before writing any code, ask the user for the following if not already provided:

| Input | Example | Used for |
|---|---|---|
| **Type ID** (slug) | `flash_sale` | Factory key, `$this->id`, theme prefix |
| **Type class name** | `FlashSale` | PHP class name |
| **Display title** | `Flash Sale` | `$this->title` |
| **Module ID** | `modules_flash_sale` | `$this->module` |
| **Default source ID** | `flash_sale_source` | `$this->default_source` |
| **Pro feature?** | yes/no | `$this->is_pro` |
| **Create a companion Extension?** | yes/no | Determines steps 4–5 |
| **Extension ID** (if yes) | `flash_sale_source` | `$this->id` on Extension |
| **Extension class name** (if yes) | `FlashSaleSource` | PHP class name |
| **Extension sub-namespace** (if yes) | `FlashSale` | Directory under `includes/Extensions/` |

If the user provides a description instead of these values, derive sensible defaults and confirm with them before proceeding.

---

## Step 1 — Create the Type class

**File:** `includes/Types/{ClassName}.php`

Use this exact template, substituting values from Step 0:

```php
<?php
namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

class {ClassName} extends Types {
    use GetInstance;

    public $priority       = 50;
    public $is_pro         = {is_pro};        // true or false
    public $module         = ['{module_id}'];
    public $default_source = '{source_id}';
    public $default_theme  = '{type_id}_theme-one';
    public $link_type      = 'none';

    public function __construct() {
        parent::__construct();
        $this->id = '{type_id}';
    }

    public function init() {
        parent::init();
        $this->title = __( '{Display Title}', 'notificationx' );

        $this->themes = [
            'theme-one' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/themes/theme-blank.jpg',
                'image_shape' => 'circle',
                'template'    => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __( 'Someone', 'notificationx' ),
                    'second_param'        => __( 'did something on', 'notificationx' ),
                    'third_param'         => 'tag_title',
                    'custom_third_param'  => __( 'Unknown', 'notificationx' ),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Just now', 'notificationx' ),
                ],
            ],
        ];

        $this->templates = [
            '{type_id}_template_default' => [
                'first_param'  => GlobalFields::get_instance()->common_name_fields(),
                'third_param'  => [
                    'tag_title' => __( 'Item Title', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_time' => __( 'Time', 'notificationx' ),
                ],
                '_themes' => [
                    '{type_id}_theme-one',
                ],
            ],
        ];
    }
}
```

**Rules:**
- Always call `parent::__construct()` before setting `$this->id`.
- Always call `parent::init()` at the top of `init()`.
- Theme keys are bare slugs (`theme-one`). The factory prefixes them with `{type_id}_` automatically when registering.
- `_themes` inside a template entry must list the full prefixed theme IDs (e.g. `{type_id}_theme-one`).
- Set `$this->is_pro = true` and add a `$popup` array when the type is a pro feature:

```php
public $popup = [
    'denyButtonText'    => "<a href='https://notificationx.com/docs/' target='_blank'>More Info</a>",
    'confirmButtonText' => "<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>",
    'html'              => "<h1>This is a Pro Feature</h1><p>Upgrade to unlock this notification type.</p>",
];
```

---

## Step 2 — Register in TypesFactory

**File:** `includes/Types/TypesFactory.php`

Read the file first, then add one entry to the `$types` array:

```php
'{type_id}' => 'NotificationX\Types\{ClassName}',
```

Insert it in alphabetical order among the existing entries. Do not change anything else.

---

## Step 3 — (Optional) Create the Extension class

Only perform this step if the user asked for a companion Extension.

**File:** `includes/Extensions/{ExtSubNamespace}/{ExtClassName}.php`

```php
<?php
namespace NotificationX\Extensions\{ExtSubNamespace};

use NotificationX\Extensions\Extension;
use NotificationX\GetInstance;

class {ExtClassName} extends Extension {
    use GetInstance;

    public $id           = '{ext_source_id}';
    public $types        = '{type_id}';         // Must match Type ID registered in TypesFactory
    public $module       = '{module_id}';
    public $module_title = '{Display Title}';
    public $img          = NOTIFICATIONX_ADMIN_URL . 'images/extensions/nx-logo.png';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title = __( '{Display Title} Source', 'notificationx' );
    }

    public function init() {
        parent::init();
        // TODO: Register WordPress hooks to collect data for this source.
        // Example: add_action( 'some_wp_event', [ $this, 'collect_entry' ], 10, 2 );
    }

    // TODO: Implement data collection method and call
    // Database::get_instance()->insert( 'nx_entries', $data ) to store entries.
}
```

**Rules:**
- `$this->types` must exactly match the Type ID added to TypesFactory.
- `$this->module` must match the module ID used in the Type class.
- The Extension `$id` is the source identifier (used as `source` column in `wp_nx_entries`).

---

## Step 4 — (Optional) Register in ExtensionFactory

**File:** `includes/Extensions/ExtensionFactory.php`

Read the file, then add one entry to the `$extension_classes` array:

```php
'{ext_source_id}' => 'NotificationX\Extensions\{ExtSubNamespace}\{ExtClassName}',
```

---

## Step 5 — Regenerate autoloader

```bash
composer dump-autoload
```

Run this from the plugin root after all files are written.

---

## Step 6 — Verify

After scaffolding, confirm the following:
- [ ] `includes/Types/{ClassName}.php` exists and `$this->id` matches the factory key.
- [ ] `TypesFactory.php` has the new entry.
- [ ] (If extension) `includes/Extensions/{ExtSubNamespace}/{ExtClassName}.php` exists.
- [ ] (If extension) `ExtensionFactory.php` has the new entry.
- [ ] `composer dump-autoload` ran without errors.
- [ ] No syntax errors: `php -l includes/Types/{ClassName}.php`

---

## Reference files

| File | Purpose |
|---|---|
| [includes/Types/Types.php](includes/Types/Types.php) | Base class — all available properties and hook points |
| [includes/Types/TypesFactory.php](includes/Types/TypesFactory.php) | Registry — add your type here |
| [includes/Types/CustomNotification.php](includes/Types/CustomNotification.php) | Minimal type example |
| [includes/Types/Comments.php](includes/Types/Comments.php) | Full type with filters example |
| [includes/Types/EmailSubscription.php](includes/Types/EmailSubscription.php) | Pro type with popup and preview_entry example |
| [includes/Extensions/Extension.php](includes/Extensions/Extension.php) | Extension base class |
| [includes/Extensions/ExtensionFactory.php](includes/Extensions/ExtensionFactory.php) | Extension registry |
| [AGENTS.md](AGENTS.md) | Full project agent guidance |
