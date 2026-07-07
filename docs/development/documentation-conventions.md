# Documentation Conventions

House style for the `docs/` tree. Read this before adding or restructuring a doc.

## Structure
- Every folder has a `README.md` index that links its contents in a table.
- Reference collections (`types/`, `extensions/`) keep a `_TEMPLATE.md` authoring template — copy it for new entries and add a row to the folder README.
- Multi-part feature docs use numbered prefixes (`00-overview.md`, `01-…`) plus a folder `README.md`.

## Links
- Use **relative** links between docs (`../architecture/overview.md`), and relative links into code (`../../includes/…`) so they resolve on GitHub and in editors.
- When you move a doc, update inbound links (grep the `docs/` tree and `CLAUDE.md`).

## Writing style
- Lead with what the reader needs; link to source files by path so claims are verifiable.
- Mark incomplete docs with a `> **Status:** stub` blockquote at the top.
- State Pro vs Free clearly — Pro logic lives in the sibling `notificationx-pro` plugin.

## Where things go
| Content | Folder |
| --- | --- |
| How the plugin is built | [../architecture/](../architecture/) |
| REST / hooks surface | [../api/](../api/) |
| "How do I extend X" guides | [./](.) (development) |
| Product-feature deep dives | [../features/](../features/) |
| One notification category | [../types/](../types/) |
| One data-source integration | [../extensions/](../extensions/) |
