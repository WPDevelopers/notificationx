# Task 06 — Suppress built-in theme fields when Elementor is in use

## Why

Once `elementor_id` is a number, every per-theme content/design field in the built-in path (titles, subtitles, CTA URL, countdown labels, advanced design colors, …) becomes meaningless — Elementor owns all of that. Showing them would let users edit values that have no visible effect.

PressBar achieves this with a `Rules::isOfType('elementor_id', 'number', true, $field)` wrap, e.g. [`PressBar.php:1364-1370`](../../../includes/Extensions/PressBar/PressBar.php#L1364-L1370) and [`PressBar.php:2058-2063`](../../../includes/Extensions/PressBar/PressBar.php#L2058-L2063).

## What

Wrap (don't rewrite) every existing Exit Intent content section, per-theme content section, advanced-design section, and the customize-tab fields that target visual state. Field categories to gate:

- The themes radio-card itself (built-in theme picker).
- All `exit_intent_*_section` content sections.
- The flat advanced-design fields contributed by `theme_one_design_fields()` … `theme_seven_design_fields()`.
- Customize-tab fields where the underlying behaviour is "popup chrome adjustments" *that the React shell still owns* — those stay visible (sensitivity, cookie days, mobile disable, show close button).

> Rule of thumb: if the field is something Elementor would render (text, colour, image, button), suppress it when `elementor_id` is a number. If it's something the **React popup shell** wraps Elementor with (overlay color, close button, trigger sensitivity), keep it visible.

## Acceptance

- With `elementor_id = 0` (no Elementor): admin UI shows all the existing per-theme content + design controls.
- With `elementor_id = 123` (imported): the built-in theme picker, content sections, and advanced-design controls disappear; only the chrome controls remain.

## Files touched

- [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../../includes/Extensions/ExitIntent/ExitIntentNotification.php).

## Depends on

Tasks 04, 05.
