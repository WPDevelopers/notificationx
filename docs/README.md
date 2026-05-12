# NotificationX Documentation

AI-readable docs for the NotificationX WordPress plugin. **Read [../CLAUDE.md](../CLAUDE.md) first** for the high-level mental model (Type ↔ Extension, bootstrap, conventions). This folder holds the deep-dive references for specific tasks.

## I want to…

| Task | Read |
|---|---|
| Understand how the plugin boots and is organized | [architecture.md](architecture.md) |
| Add a new **data source** for an existing notification type (most common) | [recipes/add-extension.md](recipes/add-extension.md) |
| Add a brand-new **notification Type** (Popup, Bar, etc.) | [recipes/add-type.md](recipes/add-type.md) |
| Add a REST endpoint | [recipes/add-rest-endpoint.md](recipes/add-rest-endpoint.md) |
| Add a settings field for an Extension or globally | [recipes/add-settings-field.md](recipes/add-settings-field.md) |
| Add a new visual design/theme to a Type | [recipes/add-frontend-design.md](recipes/add-frontend-design.md) |
| Call the REST API, or know what endpoints exist | [rest-api.md](rest-api.md) |
| Hook into NotificationX from the Pro plugin | [integrations/pro-hooks.md](integrations/pro-hooks.md) |
| Understand the WPML / VisualPortfolio / Freemius shims | [integrations/third-party.md](integrations/third-party.md) |
| Run, write, or debug tests | [testing.md](testing.md) |
| Cut a release (version bump, POT, zip, SVN) | [release.md](release.md) |
| Debug "why isn't my notification showing?" | [debugging.md](debugging.md) |
| Know *why* something is the way it is | [decisions/](decisions/) |
| Track which docs exist and what's still missing | [TASKS.md](TASKS.md) |

## Conventions used in these docs

- **File paths are clickable** — written as `[label](../includes/...)` relative to this `docs/` folder.
- **Quote file:line for any concrete claim.** Hand-wavy references rot.
- **Code snippets are minimal skeletons,** not full implementations. For full examples, the docs link to the canonical file in `includes/`.
- **Each doc has a target length** — usually 50–150 lines. Don't pad. If a doc grows past 200 lines, it probably needs to be split.

## What's NOT in these docs

- **Marketing / changelog / history** — see [../README.txt](../README.txt) and [../readme.html](../readme.html).
- **WP.org user-facing documentation** — that lives on https://notificationx.com/docs/.
- **Human onboarding / contributing guide** — these docs assume an experienced developer (or AI agent) who already knows WordPress plugin conventions. They focus on what's *specific* to NotificationX.
- **Pro plugin internals** — `notificationx-pro` has its own docs. This folder only describes the *contract surface* (`integrations/pro-hooks.md`) the free plugin exposes to Pro.

## Updating these docs

- Update the doc in the **same PR** as the code change it describes. Drift is the enemy.
- Log non-trivial doc updates in [TASKS.md](TASKS.md) under **Changelog**.
- Re-read the whole folder quarterly. Delete stale lines aggressively — outdated docs are worse than no docs.
