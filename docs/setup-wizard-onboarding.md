# Setup Wizard (Onboarding)

A standalone, full-screen onboarding experience shown to new users. It collects
a little context (business type + goals), recommends campaigns, and hands the
user off to the dashboard or the campaign builder.

> Design source: Figma "Popups Figma Templates | BRIX Templates"
> (`fileKey CchcgRv7X7i5saRgBysnJM`). Relevant frames:
> `4440:188` Welcome · `4440:259` Welcome illustration · `4440:290` Business
> Details. When the official Figma MCP is rate-limited, pull frames via the
> local **FigBridge** bridge (`127.0.0.1:7331`).

---

## At a glance

- **Not** a separate webpack entry. The wizard is a **route inside the existing
  admin React SPA** (`nxdev/notificationx/`), rendered full-screen by hiding the
  WP chrome with a body class.
- It is a 4-step flow: **Welcome → Business Details → Recommended → Finish**.
- Completion **or skipping** sets the `nx_onboarding_completed` option so it is not
  auto-launched again, and stores the collected answers in `nx_onboarding_data`.

### When the wizard auto-launches

It redirects to the wizard once, gated entirely by `nx_onboarding_completed`:

- **Fresh install** — `NotificationX::activator()` sets the `nx_activated` transient;
  `NotificationX::maybe_redirect()` (on `admin_init`) consumes it and redirects to
  `nx-setup-wizard` when not completed (else `nx-dashboard`).
- **Existing user on update** — `Core/Upgrader` detects a version change for a site
  that already had a previous version and, if not yet completed, sets the same
  `nx_activated` transient (1h) so the next admin page redirects into the wizard.
- Once the user **completes or skips**, `nx_onboarding_completed` is set and it never
  auto-launches again (both `maybe_redirect` and `Upgrader` check `is_completed()`).

### Welcome = tracking consent

Clicking **Get Started** on the Welcome step (`startWizard` in `SetupWizard.tsx`) is
the consent point named in the Welcome fine print. It POSTs the `setup_wizard_optin`
miscellaneous action → `SetupWizard::optin_tracking()` →
`PluginInsights::optin(true)`, which opts the site into WP Insights usage tracking
and immediately sends the data (site info + admin email) to the insights API. It's
fire-and-forget — the UI advances to step 2 without waiting. **Skip does not opt in.**

---

## Files

### PHP (backend)

| File | Responsibility |
| --- | --- |
| [`includes/Core/SetupWizard.php`](../includes/Core/SetupWizard.php) | Registers the `nx-setup-wizard` submenu (under `nx-admin`), adds the `nx-setup-wizard-active` body class, loads the frontend popup CSS, handles the `complete_setup_wizard` and `setup_wizard_optin` REST actions, and exposes `onboarding_completed` to the SPA via `nx_builder_configs`. |
| [`includes/Core/Upgrader.php`](../includes/Core/Upgrader.php) | On a version change for an existing (not-completed) site, sets the `nx_activated` transient so the wizard re-launches once after an update. |
| [`includes/Admin/PluginInsights.php`](../includes/Admin/PluginInsights.php) | WP Insights tracker. `optin($send)` (added) programmatically opts in + sends data; called from the wizard's Welcome step. |
| [`includes/NotificationX.php`](../includes/NotificationX.php) | `maybe_redirect()` — first-activation redirect into the wizard. |
| [`includes/Core/PostType.php`](../includes/Core/PostType.php) | Adds `toplevel_page_nx-setup-wizard` to the admin-asset enqueue allowlist so the SPA bundle loads on the wizard page. |

Key backend constants / options (in `SetupWizard.php`):

- `SetupWizard::PAGE` = `nx-setup-wizard` (menu slug / `?page=`).
- `SetupWizard::COMPLETED_OPTION` = `nx_onboarding_completed` (bool flag).
- `nx_onboarding_data` option — `{ business_type, goals[], completed_at }`.
- `SetupWizard::is_completed()` — read the flag from anywhere.

### React (frontend SPA)

| File | Responsibility |
| --- | --- |
| [`nxdev/notificationx/admin/SetupWizard/SetupWizard.tsx`](../nxdev/notificationx/admin/SetupWizard/SetupWizard.tsx) | The whole wizard: shared header + stepper, the four step components, data catalogs, recommendation logic, persistence + hand-off. |
| [`nxdev/notificationx/admin/SetupWizard/icons.tsx`](../nxdev/notificationx/admin/SetupWizard/icons.tsx) | Inline 20×20 stroke-icon set (`WizardIcon`). Add a path here before referencing a new `icon` id. |
| [`nxdev/notificationx/admin/SetupWizard/Illustration.tsx`](../nxdev/notificationx/admin/SetupWizard/Illustration.tsx) | Static Welcome-screen illustration (two mock notification cards framing the dark Growth Dashboard hero `growth-dashboard.webp`). |
| [`nxdev/notificationx/scss/nx_new/_setup_wizard.scss`](../nxdev/notificationx/scss/nx_new/_setup_wizard.scss) | All wizard styles, BEM-namespaced under `.nx-sw`. |
| [`nxdev/notificationx/Route.tsx`](../nxdev/notificationx/Route.tsx) | Maps the `nx-setup-wizard` page to `<SetupWizard/>`. |

`LivePreview.tsx` is **dead code** (the animated preview was replaced by the
static `Illustration`); left in place but unused.

---

## Layout convention — **the same on every screen**

This is the rule to keep consistent across all current and future steps — the
"Step 1" header design is reused on **every** screen. The wordmark + stepper
live **inside** each step's card, and any Back/Continue footer is pinned inside
the card at the bottom.

```
  ┌───────────────────────────────┐
  │ NotificationX                 │  ← wordmark (Hanken Grotesk 700, #5129e6)
  │ (✓)——(2)——(3)——(4)            │  ← in-card stepper
  │ ───────────────────────────── │  ← full-width divider (on __cardhead)
  │  step content (scrolls)       │  ← body; scrolls on short screens
  │ ───────────────────────────── │
  │ [Back]   Step N of 4  [Cont→] │  ← footer pinned at the bottom, in-card
  └───────────────────────────────┘
```

- Every step renders the shared **`WizardCardHeader`** (the wrapper
  `nx-sw__cardhead` → `nx-sw__brandbar` wordmark + the **`WizardStepper`**) as
  the first child **inside** its card (`nx-sw__welcome-card` for Welcome,
  `nx-sw__panel` for the others). No "SETUP WIZARD" caption.
- The header divider lives on **`nx-sw__cardhead`** (`border-bottom`) so it spans
  the **full card width**, not just under the stepper.
- **Compact height + scrolling:** the card is a flex column that sizes to its
  content but never taller than the viewport (`max-height: calc(100vh - 80px)`,
  `overflow: hidden`). The body is the flex-grow region with
  `min-height: 0; overflow-y: auto` (`nx-sw__welcome-body` / `nx-sw__columns` /
  `nx-sw__panel-body` / `nx-sw__finish`) — so on short screens the body scrolls
  internally instead of the whole page.
- The card has no padding; the brandbar/stepper provide the top inset and each
  body provides its own padding.
- **Footers live inside the card and stay fixed.** Steps 2 **and 3** put their
  `NavRow` in a `nx-sw__panel-footer` (top border + padding, `flex-shrink: 0`)
  that sits **after** the scrollable body — so Back/Continue stay pinned at the
  card bottom while the body scrolls. (Steps 1/4 have no nav footer.)
- `WizardStepper` dot states:
  - **done** — filled `#5129e6` circle + white check.
  - **active** — filled `#5129e6` circle + white number, with a `#fff` + brand
    focus ring.
  - **upcoming** — dimmed (`opacity .4`) outlined circle + number.
- The container uses the `nx-sw__container--center` modifier (centered, max
  **1240px**, 40px vertical padding → the `100vh - 80px` card max-height).

> If you add a new step, render `<WizardCardHeader active={index} />` as the
> first child of the step's card, make the step's body the scrollable flex-grow
> region (`flex: 1; min-height: 0; overflow-y: auto`), and put any Back/Continue
> row in a `nx-sw__panel-footer` **after** the body (so it stays pinned). Do not
> build a bespoke logo/stepper or place the footer outside the card.

### Design tokens (from the BRIX Figma)

| Token | Value | Use |
| --- | --- | --- |
| Brand | `#5129e6` / `#6a4bff` | wordmark, active dot, selected states, primary CTA |
| Heading ink | `#0b1c30` | titles |
| Body | `#474556` / `#6f6c8f` | captions, subtitles |
| Line | `#c9c4d9` | borders / dividers |
| Page bg | `#f6f6ff` / `#f8f9ff` | wizard background |
| Selected fill | `rgba(106,75,255,.1)` | selected row background |
| Row icon box | `#e5eeff` (→ `#6a4bff` selected) | option icon tile |
| Fonts | Hanken Grotesk (headings), Inter (body) | — |

---

## The four steps

1. **Welcome** (`StepWelcome`) — title + feature grid + Get Started / Skip Setup,
   with the lavender illustration panel on the right (`Illustration`): two mock
   notification cards framing the Growth Dashboard hero. The top "Alex just
   purchased!" sales card (`nx-sw__mock--top`) gently floats up/down via the
   `nx-sw-float` keyframe (disabled under `prefers-reduced-motion`). Skip →
   persists completion and redirects to the dashboard.
2. **Business Details** (`StepBusiness`) — one card, two divided columns:
   **Business Type** (single-select, radio semantics) and **Primary Goals**
   (multi-select, checkbox semantics). Each is a `SelectableRow`; only the
   selected row shows a trailing check. 6 options per column (matches Figma).
3. **Recommended** (`StepRecommended`) — campaign cards from `recommendFor(goals)`:
   the **two fixed defaults** (`DEFAULT_CAMPAIGN_IDS = ['sales','bar']`) always
   lead, followed by the **goal-matched** campaigns (the 3rd-onward cards change
   with the selection), padded to a minimum of 3. Each card's **Configure** opens
   the builder in a **new tab** at `?page=nx-edit&type=<type>&source=<source>` —
   `AddNewNotification` reads those URL params and calls `builder.setValues({type,
   source})` to preselect, so the wizard tab stays open. Pro cards send non-Pro
   users to pricing.
   It's a scroll-snap viewport (`nx-sw__slider-viewport`, 3 cards/view, hidden
   scrollbar). The **slider only activates when there are more than 3 cards**
   (`hasSlider = recs.length > 3`) — only then are the two circular chevron
   buttons (`nx-sw__slider-nav`, `chevron` icon; `--next` is the icon rotated
   180°) rendered; they disable at each end via `updateEdges` (the viewport's
   `scroll`/`resize`) and `scrollBy` moves one card at a time. With ≤3 cards the
   row is static and fills the full width.
4. **Finish** (`StepFinish`) — summary of choices + **Go to Dashboard** /
   **Create My First Campaign** (opens `nx-edit`).

### Data catalogs (in `SetupWizard.tsx`)

- `BUSINESS_TYPES` / `GOALS` — `{ id, icon, label }`. `icon` must exist in
  `icons.tsx`. Goal `id`s are the keys the recommendation engine matches.
- `CAMPAIGN_CATALOG` — real NotificationX campaigns with `type` + `source`
  (the same hand-off the Dashboard uses), `isPro`, and a `goals[]` mapping.
  When trimming `GOALS`, make sure every campaign still maps to at least one
  remaining goal so it stays reachable.

### Persistence / hand-off

- `persist()` → `nxHelper.post("miscellaneous", { action: "complete_setup_wizard", business_type, goals })`.
- `finish("dashboard" | "builder")` persists then navigates to
  `admin.php?page=nx-dashboard` or `nx-edit`.
- `configureCampaign(c)` persists and opens the builder preset via
  `builder.setRedirect({ page: "nx-edit", state: { type, source } })`.

---

## Build & verify

```bash
npm run admin            # production build → assets/admin/* (committed target)
npm run admin-watch      # watch → nxbuild/* (served when NX_DEBUG is on)
```

- `NOTIFICATIONX_DEV_ASSETS` (the `nxbuild/` constant) is what the plugin serves
  in debug; `npm run admin` writes the committed `assets/` bundle.
- **Visual check without a live WP:** copy the built `assets/admin/css/admin.css`
  next to a hand-written DOM that mirrors the JSX (wrapped in `<div class="nx-sw">`),
  serve it over `python3 -m http.server`, and screenshot via the Playwright MCP
  (`file://` is blocked). This is how the Welcome and Business screens were
  verified. With a live sandbox, prefer `./sb visit` (see `CLAUDE.local.md`).

---

## Adding a new step (checklist)

1. Add the step to the `STEPS` array (id + label) — the stepper updates
   automatically.
2. Write a `StepX` component that returns **only** its content card (+ `NavRow`
   if needed). Do not add a logo or stepper.
3. Render it in `SetupWizard` under the matching `active === N` guard, below the
   shared `<WizardHeader active={active} />`.
4. Add any new `icon` ids to `icons.tsx`.
5. Style under `.nx-sw__…` in `_setup_wizard.scss`, reusing the shared tokens,
   `nx-sw__btn`, and `NavRow`.
6. `npm run admin` and verify the header/stepper render identically to the other
   steps.
