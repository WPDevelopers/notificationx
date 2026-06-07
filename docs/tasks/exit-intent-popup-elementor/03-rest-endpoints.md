# Task 03 — REST endpoints for Elementor import / remove

## Why

The admin "Build With Elementor" / "Remove" buttons fire ajax against REST endpoints. PressBar uses `/notificationx/v1/elementor/import` + `/elementor/remove` ([`Core/REST.php:161-170`](../../../includes/Core/REST.php#L161-L170)).

## What

Add two new routes (greenfield, don't generalise the PressBar ones yet — keeping diffs surgical):

- `POST /notificationx/v1/exit-intent/elementor/import` → calls `ExitIntentNotification::create_exit_intent_with_elementor($params)`.
- `POST /notificationx/v1/exit-intent/elementor/remove` → calls `ExitIntentNotification::delete_elementor_post($params['elementor_id'])`.

Both use `permission_callback => array($this, 'edit_permission')` to match PressBar's auth posture.

Mirror PressBar's `create_bar_of_type_bar_with_elementor` body inside `create_exit_intent_with_elementor`:

1. Sanitise `theme_id`.
2. `(new Importer())->create_nx(['theme' => $theme])` → `$ID`.
3. `update_post_meta($ID, '_wp_page_template', 'elementor_canvas')` so the popup HTML has no theme chrome.
4. `wp_send_json_success(['context' => ['themes' => null, 'elementor_id' => $ID, 'elementor_edit_link' => …get_edit_url()]])`.

## Acceptance

- `wp rest list` shows both routes registered.
- `curl -X POST /wp-json/notificationx/v1/exit-intent/elementor/import -d theme_id=theme-one` (with auth) returns `{success: true, data: { context: {...} }}`.
- Remove route deletes the post; subsequent `get_post($id)` returns `null`.

## Files touched

- [`includes/Core/REST.php`](../../../includes/Core/REST.php) — add two `register_rest_route` calls + two thin handlers.
- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php) — add `create_exit_intent_with_elementor()` + `delete_elementor_post()`.

## Depends on

Tasks 01, 02.
