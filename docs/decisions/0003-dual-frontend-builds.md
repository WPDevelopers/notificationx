# 0003 — Admin and frontend ship as separate webpack builds

**Date:** 2024-01-01 *(approximate — original decision predates this ADR)*
**Status:** Accepted

## Context

NotificationX has three runtime contexts with very different constraints:

1. **Admin SPA** — runs only in wp-admin, can rely on `@wordpress/*` packages provided by core (no need to bundle React, no need to bundle `wp-components`), bundle size less critical.
2. **Frontend popup runtime** — runs on every public page where a notification is enabled. Must be tiny, must work without WP admin globals, must bundle its own React (the frontend has no guarantee `@wordpress/*` is loaded).
3. **Gutenberg blocks** — runs in the block editor (admin), uses `@wordpress/blocks`, has different externals than the main admin SPA.

Bundling all three from a single webpack config means either shipping admin-only deps to the frontend (bloat, performance hit on every public pageview) or complex externals juggling.

## Decision

Maintain three separate webpack configs and entry points:

- [webpack.config.js](../../webpack.config.js) — admin SPA, entry `nxdev/index.tsx`, externals from `@wordpress/scripts` defaults.
- [webpack.frontend.config.js](../../webpack.frontend.config.js) — frontend runtime, entry `nxdev/notificationx/frontend/{index,crossSite,flashing-tab}.tsx`, built with `--webpack-no-externals` so React is bundled.
- [webpack.blocks.config.js](../../webpack.blocks.config.js) — blocks, entry `blocks/notificationx/index.jsx`.

`npm run release` builds all three. `npm run start` watches admin + frontend together.

## Consequences

- ✅ Frontend bundle stays small — only what the popup runtime actually needs.
- ✅ Admin SPA can use `@wordpress/components`, `@wordpress/data`, etc. without bundling them — WP provides them at runtime.
- ✅ Each context can have its own dependency tree and version pinning.
- ❌ **The silent-desync trap:** display logic for a notification Type lives in *both* the PHP Type class and the frontend React renderer. Changing one without the other produces a popup that renders wrong without any build error. Documented in [../architecture.md § dual frontend runtimes](../architecture.md#dual-frontend-runtimes-the-silent-desync-trap).
- ❌ Three configs to maintain. When updating webpack/babel versions, all three must move together.
- ❌ `npm run build` does **not** build blocks — must run `npm run bb` separately, or use `npm run release` which does all three.
- ❌ Developers new to the codebase often modify the admin renderer and wonder why the public popup didn't change.

**Don't:**
- Try to consolidate into one config "for simplicity" — the externals/bundle-size tradeoff doesn't allow it.
- Forget the `bb` build step when shipping changes that touch blocks.
- Change popup rendering in PHP without checking what `nxdev/notificationx/frontend/` does with that data.

**Considered but rejected:**
- Server-side rendering the popup HTML (would lose interactivity, reactivity to user behavior).
- Shipping React for admin too via the frontend bundle (would duplicate React in wp-admin where it's already loaded).
