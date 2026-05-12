# Documentation Tasks

Tracks the AI-readable documentation effort for the NotificationX plugin. Update this file as tasks land. New work goes in **Backlog**; in-flight work in **In progress**; finished work in **Done** with the commit/PR if any.

## Conventions

- **Status keys:** `pending` · `in_progress` · `review` · `done` · `skipped`
- Keep each row to one line. Detail goes in the doc itself.
- Linked docs are clickable from this folder.
- When a doc is updated (not just created) for a meaningful reason, log it in **Changelog** at the bottom with a date and one-line reason.

---

## In progress

_None._

## Backlog

| Doc | Status | Notes |
|---|---|---|
| `AGENTS.md` symlink → `CLAUDE.md` | pending | `ln -s CLAUDE.md AGENTS.md` from plugin root |
| Verify route table in [rest-api.md](rest-api.md) against current code | pending | Run the grep noted at bottom of that doc |
| Add a real PHPUnit test (any) to break the placeholder-only state | pending | See [testing.md § priority order](testing.md) |
| Real preview screenshots for design picker in [recipes/add-frontend-design.md](recipes/add-frontend-design.md) | pending | Optional / quality bump |

## Done

| Doc | Date | Notes |
|---|---|---|
| [CLAUDE.md](../CLAUDE.md) — Glossary + Anti-patterns + post-type fix | 2026-05-12 | Surgical edits; fixed `nx_bar` → `notificationx` |
| [docs/README.md](README.md) — index | 2026-05-12 | "I want to…" routing table |
| [docs/architecture.md](architecture.md) | 2026-05-12 | Bootstrap, Type↔Extension, storage, dual runtimes |
| [docs/rest-api.md](rest-api.md) | 2026-05-12 | Namespace + full route table |
| [docs/testing.md](testing.md) | 2026-05-12 | PHPUnit setup, single-test, singleton-reset gotcha |
| [docs/release.md](release.md) | 2026-05-12 | Four-place version bump, build/zip/SVN |
| [docs/debugging.md](debugging.md) | 2026-05-12 | "Notification not showing" decision tree |
| [docs/recipes/add-extension.md](recipes/add-extension.md) | 2026-05-12 | Highest-value recipe |
| [docs/recipes/add-type.md](recipes/add-type.md) | 2026-05-12 | Moved from `new-notification-type.md`, added cross-link |
| [docs/recipes/add-rest-endpoint.md](recipes/add-rest-endpoint.md) | 2026-05-12 | |
| [docs/recipes/add-settings-field.md](recipes/add-settings-field.md) | 2026-05-12 | |
| [docs/recipes/add-frontend-design.md](recipes/add-frontend-design.md) | 2026-05-12 | Generalized from exit-intent-add-new-design.md |
| [docs/integrations/pro-hooks.md](integrations/pro-hooks.md) | 2026-05-12 | Filter/action contract surface |
| [docs/integrations/third-party.md](integrations/third-party.md) | 2026-05-12 | WPML, VisualPortfolio, Freemius clarification |
| [docs/decisions/README.md](decisions/README.md) | 2026-05-12 | ADR rules + index |
| [docs/decisions/0001-quickbuilder-from-github.md](decisions/0001-quickbuilder-from-github.md) | 2026-05-12 | |
| [docs/decisions/0002-singleton-getinstance.md](decisions/0002-singleton-getinstance.md) | 2026-05-12 | |
| [docs/decisions/0003-dual-frontend-builds.md](decisions/0003-dual-frontend-builds.md) | 2026-05-12 | |
| [docs/decisions/0004-module-gating-via-settings.md](decisions/0004-module-gating-via-settings.md) | 2026-05-12 | |

## Skipped / deferred

| Doc | Reason |
|---|---|
| `docs/glossary.md` (standalone) | Folded into CLAUDE.md instead — single source of truth |

---

## Maintenance rules

1. **Keep docs short.** Each file has a target length in the original plan; don't pad past it.
2. **Quote file:line for any factual claim.** Hand-wavy references rot.
3. **One PR per doc when possible.** Easier review, easier rollback.
4. **Re-read every doc quarterly.** Delete stale lines; update version-pinned facts. Log here.
5. **If you find yourself explaining the same decision twice, add an ADR.**

## Changelog

_(date · doc · reason)_

- **2026-05-12** · initial scaffold of all docs · first AI-readable docs pass for the plugin
