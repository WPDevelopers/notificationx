# Task 01 — Register `nx_exit_intent` post type

## Why

Elementor edits *posts*. To let users design the popup in Elementor, we need a dedicated WP post type whose instances back each campaign. PressBar's analogue is `nx_bar` (see [`PressBar.php:1549`](../../../includes/Extensions/PressBar/PressBar.php#L1549)).

## What

Add a `register_post_type()` method on `ExitIntentNotification` and hook it on `init`. Also add a `get_edit_post_link` filter so any direct "Edit" link on an `nx_exit_intent` post jumps into Elementor instead of the standard editor.

## Acceptance

- After plugin reload, `get_post_type_object('nx_exit_intent')` returns a valid object.
- `wp_insert_post(['post_type' => 'nx_exit_intent', ...])` succeeds.
- `get_edit_post_link($id)` for an `nx_exit_intent` post returns Elementor's editor URL when Elementor is active.
- Existing `nx_bar` handling is untouched (PressBar's filter still wins for its own post type).

## Constraints

- `supports` must include `'elementor'` — that string is what flips Elementor into treating it as a document.
- `public: false` + `publicly_queryable: true` — matches `nx_bar`. Lets Elementor's preview routes resolve while keeping the post type out of search/menus.
- `show_in_menu: true` but no `menu_icon` UI surface for the user (they only ever interact with it from inside a NotificationX campaign).
- Don't auto-flush rewrites — `rewrite: false` already.

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php)

## Verification steps

1. `wp eval "var_dump( post_type_exists('nx_exit_intent') );"` → `true`.
2. Activate Elementor, create a draft `nx_exit_intent` via `wp post create --post_type=nx_exit_intent --post_title=test --porcelain`, then `wp eval "echo get_edit_post_link($ID);"` → should contain `action=elementor`.

## Follow-ups (out of scope for this task)

- Importer (Task 02) actually creates these posts via `Elementor\Plugin::$instance->documents->create`.
- Lifecycle cleanup (Task 09) deletes them on campaign delete.
