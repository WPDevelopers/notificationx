# Task 07 — Seed Elementor JSON templates

## Why

The "Choose your theme" modal needs at least one importable seed. PressBar ships five at [`PressBar/jsons/theme-{one..five}.json`](../../../includes/Extensions/PressBar/jsons/). Exit Intent's [`jsons/`](../../../includes/Extensions/ExitIntent/jsons/) folder exists but is empty.

## What

For each seed we want exposed in the modal:

1. Build the popup design in Elementor on a scratch site.
2. Export the Elementor template (Elementor → Templates → Saved Templates → Export Template).
3. Drop the JSON into `includes/Extensions/ExitIntent/jsons/theme-{slug}.json`.
4. Add a matching preview image to `assets/admin/images/extensions/themes/exit-intent-elementor/theme-{slug}.png` (folder already exists).
5. Register the entry in `$this->elementor_themes` (Task 04).

### JSON shape (from a PressBar export, for reference)

```json
{
  "version": "0.4",
  "title": "Nx Exit Theme One",
  "type": "section",          // or "container" depending on Elementor version
  "page_settings": [],
  "content": [ { "id": "…", "elType": "section", "settings": {…}, "elements": […] } ]
}
```

The importer (Task 02) trusts these keys exactly — keep the export untouched after download.

## Acceptance

- At least one seed JSON committed.
- That seed imports successfully end-to-end via the modal (Task 04 manual run).

## Files touched

- New: `includes/Extensions/ExitIntent/jsons/theme-*.json`.
- New: `assets/admin/images/extensions/themes/exit-intent-elementor/theme-*.png`.

## Depends on

None (can be done in parallel with 01–06).
