# Frontend Performance: Overview

## The report

On 2026-09-22 the xSpeed team (a caching/optimizer plugin) reported that NotificationX was hurting the GTmetrix score of embedpress.com.

Optimizers usually hold non-essential scripts back until the visitor's first interaction ("Delay JS"). Consent banners are the exception: a banner has to ask before anything else happens, so optimizers never delay consent scripts. NotificationX has a GDPR cookie notice, so xSpeed treated it as a consent script. But the banner lives in the same file as every other notification type, so xSpeed had to load the whole 1.25 MB bundle eagerly.

Their measurements on embedpress.com (GTmetrix, Seattle, Chrome):

| Metric | NotificationX delayed | NotificationX eager |
| --- | --- | --- |
| GTmetrix score | 95–96 (A) | 84–88 (B) |
| Page weight | 1.27 MB | 2.30 MB |
| Speed Index | 1.2 s | 3.0 s |
| Total Blocking Time | 15 ms | 110 ms |
| CLS | 0.005 | 0.068 |
| Fully loaded | 2.0 s | 7–13 s |

They asked for three things: a small consent-only loader, sized non-animated notification images, and deferred `/notice/` requests.

## What we found

### 1. embedpress.com has no GDPR notice active

The page's own NotificationX data (the `notificationXArr.push({...})` block in the footer) had `"gdpr": []`. xSpeed made NotificationX eager to protect a banner that doesn't exist on that site. Their rule triggered on "NotificationX is installed", not on "a GDPR notice is running". This is the direct cause of the 95 → 86 drop, and xSpeed can fix it on their side. See [01-optimizer-compatibility.md](01-optimizer-compatibility.md).

### 2. Most of the bundle was admin code

We rebuilt 3.3.1's `frontend.js` byte-for-byte (1,285,035 bytes) and broke it down with webpack stats. About 64% was not frontend code at all:

| Module | Size before minification | How it got in |
| --- | --- | --- |
| `moment-timezone` data | ~708 KB | admin `core/functions.ts` → `../hooks` → admin `useNotificationX.ts` → `@wordpress/date` |
| `sweetalert2` | ~130 KB | admin `core/functions.ts` |
| `react-toastify` | ~79 KB | admin `core/ToasterMsg.tsx`, through `core/functions.ts` |

Four frontend files imported small helpers from the admin tree:

| Frontend file | Imported | From |
| --- | --- | --- |
| `frontend/core/Pressbar.tsx` | `themes_has_bg` | `core/functions.ts` |
| `frontend/core/Analytics.tsx` | `getIconUrl` | `core/functions.ts` |
| `frontend/core/Popup.tsx` | `getIconUrl` | `core/functions.ts` |
| `frontend/gdpr/utils/GdprActions.jsx` | `modalStyle`, `nxHelper` | `core/constants.ts`, `core/functions.ts` |

Webpack can't drop the rest of `core/functions.ts`, because it imports other modules for their side effects, so the whole admin chain came along.

### 3. Other findings

- **`frontend.css` is 926 KB** (55 KB gzip) and render-blocking. The frontend `theme.scss` imports the admin `scss/_modal.scss`, whose nested comma selectors explode (single selectors up to 26 KB). It also `@import`s FontAwesome 4.7 from cdnjs and Open Sans from Google Fonts. These are the "2 extra web fonts" in the report.
- **Google `platform.js`** was added on every notification mount ([Analytics.tsx](../../../nxdev/notificationx/frontend/core/Analytics.tsx)). Only the YouTube subscribe widget needs it.
- **The animated GIF.** The wordpress.org API returns only `icon-128x128.gif` (665 KB) for EmbedPress, with no static alternative. [WPOrgStats.php](../../../includes/Extensions/WordPress/WPOrgStats.php) picks `2x`, then `1x`.
- **CLS is probably the pressbar, not the GIF.** The notification image sits in a fixed 100 px box inside a fixed-position toast. The pressbar sets `document.body.style.paddingTop` after it renders ([Pressbar.tsx](../../../nxdev/notificationx/frontend/core/Pressbar.tsx)), which shifts the whole page. Not yet confirmed with GTmetrix's CLS breakdown.
- **The GDPR banner is never instant.** It needs the bundle, then the POST to `/notice/`, then `cookie_visibility_delay_before` seconds. That was `+value || 5`, so 0 was impossible and the minimum was 5 s. Fixed in B1.
- **Deferring `/notice/` would delay the banner,** because the banner's content comes from that same request. The config has to be inlined first (B3).

## The plan

| Phase | Scope | Risk | Status |
| --- | --- | --- | --- |
| A (xSpeed) | Exempt NotificationX only when the page's `gdpr` list is non-empty | None for us | Proposed to xSpeed |
| B0 | Safety net: baselines, E2E comparison harness | — | Done with B1 ([03-guardrails.md](03-guardrails.md)) |
| B1 | Remove admin imports from the frontend, `platform.js` only for YouTube, allow a GDPR delay of 0, `nx_status_updated` hook | Low | **Done in the working tree, not released** ([02-b1-changes.md](02-b1-changes.md)) |
| B2 | CSS: extract GDPR modal styles, stop importing admin `_modal.scss`, load FontAwesome conditionally, self-host Open Sans, then look at pressbar CLS | Low–medium, needs visual QA | Planned ([04-roadmap.md](04-roadmap.md)) |
| B3 | Consent loader split with inlined GDPR config, then defer `/notice/` | High, full QA | Planned ([04-roadmap.md](04-roadmap.md)) |

## Results so far (B1)

| | 3.3.1 | After B1 |
| --- | --- | --- |
| `frontend.js` | 1,285,035 bytes (197 KB gzip) | 455,761 bytes (131 KB gzip) |
| `crossSite.js` | 1,285,390 bytes | 456,119 bytes |
| `platform.js` on non-YouTube pages | loaded | not loaded |
| GDPR banner with delay set to 0 | ~5.1 s | ~0.2–0.4 s after the runtime starts |

On sites where an optimizer eagerly loads NotificationX, B1 cuts the eager script by about 830 KB raw. It doesn't restore embedpress.com to 95 on its own: that needs the xSpeed rule (phase A), or B3 on sites that really run a GDPR notice.
