---
name: add-test
description: Scaffold and write a PHPUnit test for a NotificationX core path (factories, migration/upgrader, REST, types, extensions) following the repo's existing test conventions. Use when the user asks to add/write tests for a class or behavior in the plugin.
tools: Read, Edit, Write, Bash, Glob, Grep
---

# Add PHPUnit Test

Adds a test to `tests/` following the repo's established conventions (part of the core-path test coverage initiative).

## Conventions (read one existing test first, e.g. tests/test-types-factory.php)

- File name: `tests/test-<subject-kebab>.php`; class `Test_<Subject_Snake> extends WP_UnitTestCase`.
- Tabs for indentation (WordPress style), file-level docblock explaining WHAT invariants the suite verifies, `@group <area>` on the class.
- `set_up()` / `tear_down()` (snake_case WP style), always call `parent::set_up()` first.
- Singleton caution: core classes use the `GetInstance` trait — construct a **fresh instance** in `set_up()` when testing stateful behavior so shared singleton state does not leak between tests (see the comment pattern in `test-types-factory.php`).
- Test method names describe the invariant: `test_types_map_is_populated`, not `test1`.
- Config: [phpunit.xml.dist](../../../phpunit.xml.dist); bootstrap loads the whole plugin via `muplugins_loaded` — WordPress + all NX classes are available.

## Workflow

1. Read the class under test fully; identify the behaviors worth locking down (registration maps, gating, round-trips, upgrade paths) — not trivial getters.
2. Read the closest existing test file and mirror its structure.
3. Write focused tests; prefer real objects over mocks (the WP test framework is available).
4. **Run them**: `vendor/bin/phpunit --filter Test_<Name>` from the plugin root.
   - If `vendor/bin/phpunit` is missing, run `composer install` first.
   - If the WP test lib is missing (`wordpress-tests-lib` error), report the bootstrap requirement (`bin/install-wp-tests.sh`) instead of guessing — ask the user how their test env is set up.
5. Report pass/fail output honestly, including skipped/incomplete tests.

## Rules

- New tests must pass before you declare done; a red test you can't fix gets reported, not deleted.
- Don't touch `tests/test-sample.php` (excluded from the suite).
