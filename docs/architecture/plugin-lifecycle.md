# Plugin Lifecycle

How NotificationX boots, activates, and upgrades.

## Bootstrap chain

[../../notificationx.php](../../notificationx.php) is the entry point. It:

1. Bails if `WPINC` is not defined (direct-access guard).
2. Defines path/URL constants — `NOTIFICATIONX_FILE`, `NOTIFICATIONX_VERSION` (currently `3.2.10`), `NOTIFICATIONX_PATH`, `NOTIFICATIONX_URL`, `NOTIFICATIONX_BASENAME`, `NOTIFICATIONX_ASSETS` (`assets/`), `NOTIFICATIONX_DEV_ASSETS` (`nxbuild/`), `NOTIFICATIONX_INCLUDES`, and the admin/public/common URL constants.
3. Loads `vendor/autoload.php` (Composer classmap — see [folder-reference.md](folder-reference.md)).
4. If `notificationx-pro` is active, prints a compatibility notice and, when present, requires the Pro entry file.
5. Registers the activation hook (`register_activation_hook( NOTIFICATIONX_FILE, 'notificationx_activate' )`).
6. Instantiates the root singleton: `\NotificationX\NotificationX::get_instance()`.

The root singleton constructor ([../../includes/NotificationX.php](../../includes/NotificationX.php)) wires up the rest, in order:

- `Settings::get_instance([...])` — the `wpdeveloper/lib-settings` store (`key=notificationx`, `store=options`, `auto_commit=true`).
- `WPDRoleManagement` — maps the custom capabilities (`read_notificationx`, `edit_notificationx`, etc.) onto roles.
- `Upgrader::get_instance()` — runs the DB/version checks on every load (see below).
- `Admin::get_instance()` — only when in admin (or `?frontend` is not set).
- `FrontEnd::get_instance()`.
- Hooks: `admin_init → maybe_redirect`, `init → init`, `plugins_loaded → init_extension`, filter `nx_pro_alert_popup`, `init → register_custom_image_size`.
- `REST`, `Cron`, `QuickBuild`, `ShortcodeInline`, `Blocks`, `CoreInstaller`.
- Third-party shims: `WPML`, `VisualPortfolio`, and `ElementorManager` (deferred to `elementor/loaded`), plus `EntriesMailReceiver`.

`init_extension()` (on `plugins_loaded`) calls `ExtensionFactory::get_instance()`, which registers every enabled Extension.

## Singletons: the GetInstance trait

Almost every subsystem uses the `GetInstance` trait ([../../includes/GetInstance.php](../../includes/GetInstance.php)). Call `Foo::get_instance()`, never `new Foo()`.

The trait also implements the free↔Pro swap: when resolving `NotificationX\Foo`, if a subclass `NotificationXPro\Foo` exists and extends it, the Pro subclass is instantiated instead. That is how Pro overrides free behaviour without the free plugin knowing about it. The trait caches a single instance per class in a static `$instance`.

## Activation / deactivation / uninstall

- **Activation** — `notificationx_activate()` → `NotificationX::get_instance()->activator()`:
  - `Database::get_instance()->Create_DB()` creates the three custom tables via `dbDelta` (see [data-storage.md](data-storage.md)).
  - Sets the `nx_activated` transient (30 s) for users who can `delete_users` (skipped under WP-CLI).
  - `Upgrader::get_instance()->clear_transient()` clears the cached builder fields.
  - On the next `admin_init`, `maybe_redirect()` consumes the `nx_activated` transient and redirects to the Setup Wizard (`nx-setup-wizard`) on first activation, or the dashboard (`nx-dashboard`) if the wizard is already completed. Skipped on multisite.
- **Deactivation** — there is no `register_deactivation_hook`; nothing special runs on deactivate.
- **Uninstall** — [../../uninstall.php](../../uninstall.php) is the WordPress uninstall entry point, but it is currently a boilerplate stub: it only guards against direct access (`WP_UNINSTALL_PLUGIN`) and performs **no** data cleanup. Deleting the plugin therefore leaves the custom tables and options in place.

## Version & upgrade path

`NOTIFICATIONX_VERSION` in [../../notificationx.php](../../notificationx.php) must move together with `version` in [../../package.json](../../package.json) when shipping.

`Upgrader` ([../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php)) runs in its constructor on every load and drives upgrades off two options stored in the WP options table:

- `nx_db_version` — compared against `Database::$version` (currently `2.1`). On mismatch it re-runs `Create_DB()` (idempotent `dbDelta`) and updates the option.
- `nx_free_version` — the last-seen plugin version. If it is unset or `< 2.0.0` and the tables were just created, `Migration::get_instance()` runs the legacy-CPT migration once (see [data-storage.md](data-storage.md)). A `<= 2.8.0` check runs `migrate_for_donation()`. Any version change bumps `nx_free_version` and clears the `nx_builder_fields` transient.

Existing users updating the plugin are intentionally **not** sent back through the Setup Wizard — only the stored version is bumped. The wizard fires only on fresh activation via the activation transient.

## Scheduled tasks (Cron)

Pull-based sources (WP.org reviews/stats, MailChimp, ConvertKit, Freemius, Envato, Google Analytics) refresh their data via WP-Cron. `Cron` ([../../includes/Admin/Cron.php](../../includes/Admin/Cron.php)) registers custom recurring schedules (`nx_wp_stats_interval`, `nx_wp_review_interval`, plus anything added through the `nx_cron_schedules` filter) whose intervals come from settings. `Cron::set_cron( $post_id, $interval_key )` schedules the shared `nx_cron_update_data` hook per notification; when it fires, `update_data()` dispatches `nx_cron_update_data_{source}` so the owning Extension can fetch fresh entries. Deleting a notification (`nx_delete_post`) clears its scheduled event.

## Source
- [../../notificationx.php](../../notificationx.php)
- [../../includes/NotificationX.php](../../includes/NotificationX.php)
- [../../includes/GetInstance.php](../../includes/GetInstance.php)
- [../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php)
- [../../includes/Admin/Cron.php](../../includes/Admin/Cron.php)
