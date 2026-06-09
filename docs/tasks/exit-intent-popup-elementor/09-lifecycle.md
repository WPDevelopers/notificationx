# Task 09 — Lifecycle hooks (save / load / delete / WPML)

## Why

Three loose ends after the happy path works:

1. **Save**: when the campaign is saved with a new title, the linked `nx_exit_intent` post's title should follow ("NxExit: <title>") so it's identifiable in admin lists. PressBar does this in `saved_post()` ([line 355](../../../includes/Extensions/PressBar/PressBar.php#L355)).
2. **Load**: when the admin re-opens an existing campaign, we need to re-derive `elementor_edit_link` (Elementor's edit URLs aren't stable across reinstalls) and `elementor_exit_theme` (so the modal preselects the right radio if the user clicks Remove → Build again). PressBar does this in `nx_get_post()` ([line 2102](../../../includes/Extensions/PressBar/PressBar.php#L2102)). If the linked post has been manually deleted, `unset($post['elementor_id'])` so the UI falls back to the built-in renderer.
3. **Delete**: when the campaign is deleted, the orphan `nx_exit_intent` post must be removed too — including all WPML language copies (`apply_filters('wpml_object_id', $id, 'nx_exit_intent', false, $lang)`). PressBar: `nx_delete_post()` + `delete_elementor_post()` ([lines 1520, 1528](../../../includes/Extensions/PressBar/PressBar.php#L1520)).

## What

Implement, mirroring PressBar:

- `save_post()` — unset transient flags (`is_elementor`, `is_confirmed`) before persist.
- `saved_post()` — `wp_update_post(['ID' => $data['elementor_id'], 'post_title' => "NxExit: $title"])`.
- `nx_get_post()` — hooked at `nx_get_post` priority 9; refresh `elementor_edit_link`, back-fill `elementor_exit_theme` by matching against `$this->elementor_themes[*]['title']`; if document missing, drop `elementor_id` from the array.
- `nx_delete_post()` + `delete_elementor_post()` — WPML-aware delete.

## Acceptance

- Renaming a campaign updates the linked Elementor doc's title.
- Deleting a campaign also deletes the `nx_exit_intent` post (verified via `wp post list --post_type=nx_exit_intent`).
- Manually trashing the Elementor doc but keeping the campaign → reopening the campaign cleanly falls back to the built-in theme picker (no console errors, no stale edit link).

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php).

## Depends on

Tasks 01–05.
