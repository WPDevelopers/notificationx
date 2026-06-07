# Task 05 — Hidden state fields

## Why

QuickBuilder is a fully form-state-driven UI. The visibility of every Build/Edit/Remove control flows from a small set of hidden fields that must exist with the right defaults.

PressBar's set lives at [`PressBar.php:934-1117`](../../../includes/Extensions/PressBar/PressBar.php#L934-L1117).

## What

Add these hidden fields to the Exit Intent design schema (all rules-gated to `source = exit_intent_custom`):

| Name | Type | Default | Notes |
|---|---|---|---|
| `is_elementor` | hidden | `class_exists('\\Elementor\\Plugin')` | Is Elementor available on this site? |
| `elementor_id` | hidden | `false` | The linked `nx_exit_intent` post ID; `false` until imported. |
| `elementor_edit_link` | hidden / button-href | `''` | Populated from the import response's `context`. |
| `elementor_exit_theme` | hidden | `'theme-one'` | Seed theme the user picked. Used by `nx_get_post` to back-fill the radio. |
| `is_confirmed` | hidden | `false` | Modal step gate — flips to `true` post-import via `ajax.trigger: '@is_confirmed:true'`. |

## Acceptance

- Save & reopen a fresh campaign → all five fields present in the saved post meta with correct defaults.
- Import flow flips `elementor_id` + `elementor_edit_link` + `is_confirmed` without a page reload.
- Remove flow resets all three back to `false` / `''` / `false`.

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php).

## Depends on

Task 04 (the modal's `trigger` payloads write to these fields).
