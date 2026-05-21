# Task 02 — Elementor `Importer` for Exit Intent seed JSONs

## Why

Each "Build With Elementor" import needs to (a) read a packaged Elementor template JSON, (b) freshen its element IDs, (c) run Elementor's `on_import` content processors (so dynamic tags / media URLs are remapped), and (d) create a brand-new `nx_exit_intent` Elementor document populated with the result. PressBar does this with [`PressBar/importer.php`](../../../includes/Extensions/PressBar/importer.php).

## What

Add `includes/Extensions/ExitIntent/importer.php` that mirrors PressBar's `Importer extends Elementor\TemplateLibrary\Source_Local` but:

- Reads from `__DIR__ . "/jsons/{$theme}.json"` (Exit Intent's own seed folder).
- Creates the document with `post_type: 'nx_exit_intent'`.
- Default `post_title` prefix `'NxExit: '` (PressBar uses `'NxBar: '`).

## Acceptance

- `new \NotificationX\Extensions\ExitIntent\Importer()` instantiable when Elementor is active.
- `$importer->create_nx(['theme' => 'theme-one'])` returns a new published `nx_exit_intent` post ID.
- The returned post is openable in Elementor (no broken element references).

## Files touched

- New: [`includes/Extensions/ExitIntent/importer.php`](../../../includes/Extensions/ExitIntent/importer.php)

## Depends on

- Task 01 (the post type must exist).
- Task 07 (a seed JSON must exist for the test theme — until then, `create_nx` will fail at `file_get_contents`).
