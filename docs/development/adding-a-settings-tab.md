# Adding a Settings Tab

The **NotificationX → Settings** screen is a single QuickBuilder form whose tab/section/field schema is assembled in PHP by [`Settings::settings_form()`](../../includes/Admin/Settings.php) and rendered by the admin SPA. Every tab and every tab's body is wrapped in a WordPress filter, so you can add a whole new tab, or drop a section into an existing one, without editing `Settings.php`.

## Settings storage recap

Settings persistence is delegated to the `wpdeveloper/lib-settings` package. NotificationX configures it once in [`includes/NotificationX.php`](../../includes/NotificationX.php):

```php
$this->settings = Settings::get_instance([
    'key'         => 'notificationx',  // wp_options row
    'auto_commit' => true,
    'store'       => 'options',
]);
```

All values live under the single `notificationx` option, nested beneath a `settings` key. Read one with the base-class getter (dot-notation, with a default):

```php
Settings::get_instance()->get( 'settings.enable_analytics', false );
```

Saving flows through [`Settings::save_settings()`](../../includes/Admin/Settings.php): it checks the `edit_notificationx_settings` capability, runs the submitted array through the `nx_settings` filter (your chance to sanitize or strip transient fields), then calls `set('settings', $settings)` and fires the `nx_settings_saved` action. See [../architecture/data-storage.md](../architecture/data-storage.md) for the wider storage picture.

## Adding a tab / section

The schema is a nested array — **tabs → sections → fields** — built inside `settings_form()`:

```php
'tabs' => apply_filters( 'nx_settings_tab', [
    'tab-general' => apply_filters( 'nx_settings_tab_general', [
        'id'       => 'tab-general',
        'label'    => __( 'General', 'notificationx' ),
        'classes'  => 'tab-general',
        'priority' => 10,
        'fields'   => [
            'section-modules' => [
                'name'   => 'section-modules',
                'type'   => 'section',
                'label'  => __( 'Modules', 'notificationx' ),
                'fields' => [ /* individual controls */ ],
            ],
        ],
    ] ),
    'advanced-settings-tab'      => apply_filters( 'nx_settings_tab_advanced', [ /* ... */ ] ),
    'email-analytics-reporting'  => apply_filters( 'nx_settings_tab_email_analytics', [ /* ... */ ] ),
    'entries'                    => apply_filters( 'nx_settings_tab_entries', [ /* ... */ ] ),
    'tab-miscellaneous-settings' => apply_filters( 'nx_settings_tab_miscellaneous', [ /* ... */ ] ),
] ),
```

Two filter tiers give you two extension points:

- **`nx_settings_tab`** wraps the *entire* tabs array — hook it to add a brand-new top-level tab.
- **`nx_settings_tab_<slug>`** wraps one tab's definition — hook it to append a section to an existing tab. The built-in slugs are `general`, `advanced`, `email_analytics`, `entries`, and `miscellaneous`.

**Field shapes.** A *tab* is `[ 'id', 'label', 'classes', 'priority', 'fields' => <sections> ]`. A *section* is `[ 'name', 'type' => 'section', 'label', 'priority', 'fields' => <controls> ]`. A *control* is `[ 'name', 'type', 'label', 'default', 'priority', ... ]` where `type` is a QuickBuilder field type (`checkbox`, `toggle`, `select`, `number`, `text`, `section`, …). `priority` orders siblings; `is_pro => true` locks a field behind Pro; `rules => Rules::is(...)` conditionally shows it.

**Add a whole new tab** via `nx_settings_tab`:

```php
add_filter( 'nx_settings_tab', function ( $tabs ) {
    $tabs['acme-tab'] = [
        'id'       => 'tab-acme',
        'label'    => __( 'Acme', 'notificationx' ),
        'classes'  => 'tab-acme',
        'priority' => 40,
        'fields'   => [
            'acme-section' => [
                'name'   => 'acme-section',
                'type'   => 'section',
                'label'  => __( 'Acme Options', 'notificationx' ),
                'fields' => [
                    'acme_enable' => [
                        'name'    => 'acme_enable',
                        'type'    => 'checkbox',
                        'label'   => __( 'Enable Acme', 'notificationx' ),
                        'default' => false,
                    ],
                ],
            ],
        ],
    ];
    return $tabs;
} );
```

**Add a section to an existing tab** via `nx_settings_tab_<slug>` — this is exactly how [`ImportExport`](../../includes/Admin/ImportExport.php) injects the Import/Export UI into the Miscellaneous tab:

```php
add_filter( 'nx_settings_tab_miscellaneous', function ( $tab ) {
    $tab['fields']['import-section'] = [
        'name'     => 'import-section',
        'type'     => 'section',
        'label'    => __( 'Import/Export', 'notificationx' ),
        'priority' => 30,
        'fields'   => [ /* controls */ ],
    ];
    return $tab;
} );
```

Note the filter receives the single tab array, so you append under its `fields` key. Because saving passes everything through `nx_settings`, any transient control (buttons, one-shot triggers) should be stripped there before it is persisted — `ImportExport` uses `add_filter('nx_settings', ...)` to unset its export/import trigger fields.

**Module toggles.** The **General** tab's `section-modules` renders a single multi-value `toggle` field whose options come from `Modules::get_instance()->get_all()`. You don't add modules here — a module card appears automatically when an Extension registers its `$module` via [`Extension::register_module()`](../../includes/Extensions/Extension.php) (see [adding-an-extension.md](adding-an-extension.md)). Those `modules_*` toggles gate whether an integration loads at all; ordinary settings fields you add through the filters above are plain stored values you read back with `Settings::get()`.

## Source
- [../../includes/Admin/Settings.php](../../includes/Admin/Settings.php) — `settings_form()` schema, `nx_settings_tab*` filters, `save_settings()` / `nx_settings` filter, `set()`/`get()`.
- [../../includes/Admin/ImportExport.php](../../includes/Admin/ImportExport.php) — real `nx_settings_tab_miscellaneous` + `nx_settings` example.
- [../../includes/Core/Modules.php](../../includes/Core/Modules.php) — module registry behind the General tab toggles.
- [../architecture/admin-spa.md](../architecture/admin-spa.md) · [../architecture/data-storage.md](../architecture/data-storage.md)
