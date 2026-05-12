# Testing

How to run, write, and debug PHPUnit tests for NotificationX.

> **Current state (honest):** the test suite is a stub. [phpunit.xml.dist](../phpunit.xml.dist) is configured and [tests/bootstrap.php](../tests/bootstrap.php) loads the plugin into the WordPress test environment, but the only existing test file is the excluded placeholder `tests/test-sample.php`. This doc covers how to run what exists and how to add real coverage.

## Prerequisites

1. **WordPress test suite installed.** The standard `install-wp-tests.sh` flow:
   ```sh
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```
   If the script isn't in this repo, copy it from the WP-CLI scaffold:
   ```sh
   curl -O https://develop.svn.wordpress.org/trunk/tests/phpunit/data/install-wp-tests.sh
   ```
   The script creates a test DB and downloads the WP test library to `$WP_TESTS_DIR` (default `/tmp/wordpress-tests-lib`).
2. **`WP_TESTS_DIR` env var set** to wherever the test library lives.
3. **`composer install`** at the plugin root.

## Running the suite

```sh
composer install
vendor/bin/phpunit
```

Single test:
```sh
vendor/bin/phpunit --filter=YourTestClass
vendor/bin/phpunit --filter=YourTestClass::test_specific_method
```

Single file:
```sh
vendor/bin/phpunit tests/test-extensions.php
```

With verbose output:
```sh
vendor/bin/phpunit --testdox
```

## What the bootstrap does

[tests/bootstrap.php](../tests/bootstrap.php):
1. Loads `$_tests_dir/includes/functions.php` (WP test helpers).
2. Registers a `muplugins_loaded` filter that requires `notificationx.php`.
3. Loads `$_tests_dir/includes/bootstrap.php` (boots WP).

Net effect: when your test class extends `WP_UnitTestCase`, the plugin is fully loaded, the DB has WP's tables, and `setUp/tearDown` rolls back DB writes per test.

## Adding a test

Create `tests/test-<feature>.php`. **Filename must match `test-*.php`** or PHPUnit's pattern in [phpunit.xml.dist](../phpunit.xml.dist) will skip it.

```php
<?php
use NotificationX\Extensions\ExtensionFactory;

class Test_ExtensionFactory extends WP_UnitTestCase {

    public function test_woocommerce_sales_registers_when_module_enabled() {
        $settings = \NotificationX\Admin\Settings::get_instance();
        $settings->set( 'settings.modules.modules_woocommerce', true );

        $factory = ExtensionFactory::get_instance();
        $this->assertArrayHasKey( 'woocommerce_sales', $factory->get_extensions() );
    }

    public function test_disabled_module_does_not_register() {
        $settings = \NotificationX\Admin\Settings::get_instance();
        $settings->set( 'settings.modules.modules_woocommerce', false );

        // ExtensionFactory caches; force-rebuild or assert via Modules::is_enabled.
        $this->assertFalse(
            \NotificationX\Core\Modules::get_instance()->is_enabled( 'modules_woocommerce' )
        );
    }
}
```

### Singleton reset gotcha

`GetInstance` caches in a static property. Tests that mutate singleton state must either:
- Reset the singleton explicitly between tests (`MyClass::$instance = null;` if accessible), or
- Test behavior via inputs/outputs that don't depend on cached state.

`WP_UnitTestCase` does **not** reset PHP statics on `tearDown` — only DB rows. Plan for it.

### DB writes

The test framework wraps each test in a transaction and rolls back on `tearDown`. Writes to `wp_options`, custom NX tables, etc. are safe — they don't persist across tests.

**Exception:** if your code uses `dbDelta` or schema-changing queries, those may not roll back. Use the dedicated migration test pattern (set up in a one-time fixture, verify, tear down manually).

### Mocking third-party plugins

Most Extensions guard on `class_exists` or `function_exists`. To test a WooCommerce extension without installing WC, you can:
- Define a stub class in the test setup: `class_alias('WP_UnitTestCase', 'WooCommerce')` — crude but works for `class_exists` checks.
- Or use a `requires` annotation and skip when WC isn't loaded:
  ```php
  /**
   * @requires class WooCommerce
   */
  public function test_woo_specific_behavior() { ... }
  ```

## Coding standards

```sh
vendor/bin/phpcs --standard=phpcs.xml
```

Two configs exist:
- [phpcs.xml](../phpcs.xml) — looser, used in CI.
- `.phpcs.xml.dist` — stricter dist version.

Run the strict one before tagging a release.

## What's worth testing (priority order)

If you're adding real coverage, focus here first:

1. **Module gating** — every Extension's `$module` toggle actually gates registration.
2. **Type ↔ Extension mapping** — every Extension's `$types` resolves to a registered Type.
3. **REST endpoint contracts** — at least one positive + one negative case per controller in [includes/Core/Rest/](../includes/Core/Rest/).
4. **Entry storage** — `nx_entries` row format, `entry_key` uniqueness, regenerate/reset behavior.
5. **Migrations** — version upgrade paths in [includes/Core/Upgrader.php](../includes/Core/Upgrader.php).

Don't bother unit-testing PHP that just glues to WP hooks — integrate-test via `WP_UnitTestCase` instead.

## Frontend tests

There are no JS tests configured. If adding any, use `wp-scripts test` or Jest with the existing `@wordpress/scripts` setup (no Jest config currently committed).
