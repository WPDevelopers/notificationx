# Task 04 — Admin "Custom" tab UI for Elementor

## Why

This is the user-visible piece that matches the screenshot supplied in the conversation: a *Custom* tab inside the Themes section with **Build With Elementor** (+ later Build With Gutenberg) buttons; once a builder is chosen, the buttons swap to **Edit With Elementor** + **Remove**.

PressBar wires this in `design_tab_presets_fields()` ([`PressBar.php:406`](../../../includes/Extensions/PressBar/PressBar.php#L406)) and `design_tab_fields()` ([`PressBar.php:527`](../../../includes/Extensions/PressBar/PressBar.php#L527)).

## What

1. Register an Elementor-themes registry on the class, e.g. `$this->elementor_themes = [...]` (one entry per seed JSON in `jsons/`). Each entry: `{ label, value, icon, column, title }`.
2. Inside the existing `init_fields()` (or a new `nx_design_tab_fields` filter hook on this extension), add:
   - A *Custom* tab section (matching PressBar's pattern at line 406-468).
   - `elementor_edit_link` button (`href: -1`, target `_blank`) — visible when `elementor_edit_link` is a string.
   - `nx-exit-intent_with_elementor-remove` button — visible when `elementor_id` is set, fires `/exit-intent/elementor/remove`, then `setFieldValue` to reset state.
   - `nx-exit-intent_with_elementor` modal — visible when neither `elementor_id` nor `gutenberg_id` is set; body shows the `elementor_themes` radio-card; confirm button POSTs `/exit-intent/elementor/import` with `theme_id: @elementor_exit_theme`.
   - `nx-exit-intent_with_elementor_install` button — fallback when Elementor isn't active; reuses the existing `/notificationx/v1/core-install` endpoint with `slug: elementor`.
3. All rules gated by `Rules::is('source', $this->id)` to scope to Exit Intent only.

## Acceptance

- Open a fresh Exit Intent campaign → Custom tab shows "Build With Elementor".
- Click → modal opens with the seed-theme picker.
- Pick a theme → Import → modal advances to "Next" → clicking Next jumps to the Display tab and closes the modal.
- Reopen the campaign → Custom tab now shows "Edit With Elementor" + "Remove".
- Click Remove → button row reverts to "Build With Elementor".

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php).

## Depends on

Tasks 01, 02, 03, 05, 07.
