# 0001 — QuickBuilder is pinned to a GitHub branch, not npm

**Date:** 2024-01-01 *(approximate — original decision predates this ADR)*
**Status:** Accepted

## Context

NotificationX uses [QuickBuilder](https://github.com/WPDevelopers/quickbuilder) — an in-house React form-builder library — as the engine that renders the notification builder UI and the settings tabs. QuickBuilder evolves continuously alongside NotificationX, and changes often need to ship in lockstep (e.g. a new field type added to QuickBuilder is consumed by a new Extension in the same release).

The npm-published version of QuickBuilder lags behind master, and releasing every change to npm before shipping NotificationX would be friction without benefit — QuickBuilder has effectively one consumer.

## Decision

Pin the QuickBuilder dependency in [package.json](../../package.json) to a specific branch on GitHub: `github:WPDevelopers/quickbuilder#notificationx`. Provide an `npm run up` script that explicitly reinstalls from that branch, so developers have a one-command way to pick up upstream changes.

## Consequences

- ✅ NotificationX and QuickBuilder can ship breaking changes together without coordinating an npm release.
- ✅ A single command (`npm run up`) refreshes the dependency — no manual lockfile surgery.
- ❌ `npm install` / `npm ci` will **not** pull the latest QuickBuilder changes — the lockfile pins a specific commit. Developers must remember to `npm run up`.
- ❌ Standard SCA / dependency-scanning tools may flag the GitHub source as unverified.
- ❌ A fresh clone in an air-gapped CI environment must allow outbound GitHub pulls.
- ❌ Cannot use `npm audit` against the QuickBuilder code path.

**Don't:** bump QuickBuilder via `npm install quickbuilder@x.y.z` — that resolves the *npm* version, which is wrong. Always use `npm run up`. See the anti-pattern note in [../../CLAUDE.md](../../CLAUDE.md).
