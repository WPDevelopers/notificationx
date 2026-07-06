# NotificationX Documentation

Developer & AI-agent reference for the **NotificationX** WordPress plugin (FOMO / social proof / sales popup / notification bar) by WPDeveloper.

New here? Start with [architecture/overview.md](architecture/overview.md) for the big picture, then jump to the area you need. For plugin-wide conventions and build commands see the repo root [CLAUDE.md](../CLAUDE.md).

## Map

| Folder | What's inside |
| --- | --- |
| [architecture/](architecture/) | How the plugin is put together — the Type↔Extension model, bootstrap/lifecycle, folder layout, storage, admin SPA, frontend runtime. |
| [api/](api/) | REST endpoints, and the public hooks/filters surface. |
| [development/](development/) | How-to guides for extending the plugin — adding a Type, adding an Extension, blocks, Elementor widgets, settings tabs, asset builds, doc conventions. |
| [features/](features/) | Per-feature deep dives — Setup Wizard, Exit Intent, Notification Bar, Sales Notification designs. |
| [types/](types/) | Per-**Type** reference (one doc per notification category — 19 types). |
| [extensions/](extensions/) | Per-**Extension** reference (one doc per data-source integration — 35 integrations). |

## The core abstraction

A NotificationX notification is a **(Type, Extension)** pair plus a theme:

- A **Type** ([types/](types/)) is the display category — Sales/Conversions, Reviews, Comments, Notification Bar, Popup, Exit Intent, etc.
- An **Extension** ([extensions/](extensions/)) is the data source that feeds a Type — WooCommerce, EDD, MailChimp, Contact Form 7, Zapier, and so on.

See [architecture/overview.md](architecture/overview.md) for the full explanation.

## Contributing to these docs

Follow [development/documentation-conventions.md](development/documentation-conventions.md). Each subfolder has a `README.md` index and, where relevant, a `_TEMPLATE.md` authoring template.
