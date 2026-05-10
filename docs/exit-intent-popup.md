# Exit Intent Popup

The Exit Intent Popup feature detects when a visitor is about to leave the page (mouse moves toward the browser chrome) and displays a targeted modal popup to recover abandoning users.

There are **five themes** (`theme-one` … `theme-five`). Each theme owns its own content fields, its own slice of the Advanced Design panel, and its own React render branch. Some shared keys (`exit_intent_image_url`, `exit_intent_button_text`, `exit_intent_dismiss_text`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size`) flow across multiple themes.

---

## Key Files

### Backend (PHP)

| File | Purpose |
|------|---------|
| [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) | Extension class — registers all 5 themes, defines all content/design/customize fields, and sets per-theme defaults |
| [includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php) | Notification type registration (`exit_intent`) |
| [includes/FrontEnd/FrontEnd.php](../includes/FrontEnd/FrontEnd.php) | Serializes enabled exit intent notifications into the REST response under the `exit_intent` key (see `get_notifications_data`) |

### Frontend (React / TypeScript)

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) | React component — renders all 5 themes, owns `videoPlaying` state for theme-four, drives the `useCountdown` hook used by theme-five |
| [nxdev/notificationx/frontend/core/useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) | Attaches `document` `mouseleave` listener; triggers `dispatchNotification` when cursor crosses the top threshold; stores each `nx_id` in a `triggered` Set so it fires only once per page load |
| [nxdev/notificationx/frontend/core/NotificationContainer.tsx](../nxdev/notificationx/frontend/core/NotificationContainer.tsx) | Detects `config.type === 'exit_intent'` and renders `<ExitIntentPopup>` for each notice |
| [nxdev/notificationx/frontend/core/utils.ts](../nxdev/notificationx/frontend/core/utils.ts) | `normalizePressBar()` converts the PHP keyed object into a JS array for `exitIntentNotices`; `isNotClosed()` skips already-dismissed entries |

### Styles

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) | All exit intent SCSS — overlay, base popup, and per-theme blocks (theme-one ~line 26, theme-two ~line 210, theme-three ~line 470, theme-four ~line 657, theme-five further down) |

### Compiled Assets

| File | Purpose |
|------|---------|
| [nxbuild/public/js/frontend.js](../nxbuild/public/js/frontend.js) | Compiled JS (contains compiled `ExitIntentPopup`) |
| [nxbuild/public/css/frontend.css](../nxbuild/public/css/frontend.css) | Compiled CSS including all exit intent styles |

> Run `npm run frontend` (or `npm run build`) after editing `ExitIntentPopup.tsx` or the SCSS — the compiled bundle does not pick up source changes automatically.

---

## Trigger Mechanism

Defined in [useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts):

```ts
document.addEventListener('mouseleave', handleMouseLeave);

const handleMouseLeave = (e: MouseEvent) => {
    if (e.clientY > 10) return; // only fire when cursor approaches top
    // fires once per nx_id per page load (tracked in `triggered` Set)
    dispatchNotification({ data: exitItem.content, config: exitItem.post });
};
```

- **Session persistence** — closing a popup sets `sessionStorage` key `notificationx_exit_intent_{nx_id}_{theme}` to `'closed'`; subsequent `mouseleave` events skip it.
- **One-shot per page load** — once fired, the `nx_id` is added to `triggered` so it cannot re-open without a page refresh.

---

## Themes

### Theme One — Feedback Form

Default theme. Shows a title, subtitle, optional name/email/message inputs, and a submit button. (The earlier "question + reason dropdown" UI has been removed; the form is just name/email/message.)

**Content fields** (section: `exit_intent_content_section`, applies to theme-one only):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_title` | text | `Wait! Before You Go...` |
| `exit_intent_subtitle` | text | `We'd love to understand…` |
| `exit_intent_show_name` | toggle | `true` |
| `exit_intent_name_label` | text | `Name *` |
| `exit_intent_show_email` | toggle | `true` |
| `exit_intent_email_label` | text | `Enter Your Email *` |
| `exit_intent_show_message` | toggle | `false` |
| `exit_intent_message_placeholder` | text | `Your message...` |
| `exit_intent_button_text` | text | `SUBMIT` |

Submitting the form POSTs `{ nx_id, theme, title, name?, email?, message? }` to `popup-submit` via `nxHelper.post`. Success toggles a `submitted` UI state, then auto-closes after 2.5 s.

---

### Theme Two — Flash Sale

Two-column layout: left column has sale headline + CTA; right column is a background image panel.

> **Countdown timer was removed from theme-two.** Use [theme-five](#theme-five--live-flash-sale-with-timer) if you need a live countdown layout. Theme-two no longer renders a timer and no longer exposes `exit_intent_countdown_label` / `exit_intent_countdown_end` content fields, nor any `t2_cd_*` design fields.

**Content fields** (section: `exit_intent_theme_two_section`):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_image_url` | image | bundled `theme-two.jpg` |
| `exit_intent_sale_badge` | text | `Flash Sale` |
| `exit_intent_sale_headline` | text | `50% OFF` |
| `exit_intent_sale_desc` | text | `ON ENTIRE ORDER` |
| `exit_intent_button_text` | text | `Shop The Flash Sale Now` |
| `exit_intent_dismiss_text` | text | `NO, THANKS!` |

---

### Theme Three — Coupon Offer

Single-column layout with an optional character illustration image, offer text, coupon code block, and CTA/dismiss buttons.

**Content fields** (section: `exit_intent_theme_three_section`):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_image_url` | image | _(empty)_ |
| `exit_intent_t3_title` | text | `Wait, don't go!` |
| `exit_intent_t3_subtitle` | text | `Before you leave, we have a special offer just for you!` |
| `exit_intent_t3_offer` | text | `Get 15% off your next purchase!` |
| `exit_intent_t3_coupon_text` | text | `Use code STAY15 at checkout…` |
| `exit_intent_button_text` | text | `Claim Offer` |
| `exit_intent_dismiss_text` | text | `No, thanks!` |

---

### Theme Four — Video Popup

Center-aligned card with a badge pill, heading, subtitle, and an embedded video player. Supports YouTube and Vimeo URLs. Before clicking play, shows an optional thumbnail image with a play button overlay. After clicking, swaps to an `<iframe>` with `?autoplay=1`.

**Content fields** (section: `exit_intent_theme_four_section`):

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_image_url` | image | _(empty)_ | Optional thumbnail shown before play |
| `exit_intent_t4_badge` | text | `Before you go...` | Small pill above the title |
| `exit_intent_t4_title` | text | `Watch this short demo video` | Main heading |
| `exit_intent_t4_subtitle` | text | `See how our product simplifies your workflow.` | Sub-heading |
| `exit_intent_t4_video_url` | text | _(empty)_ | YouTube, Vimeo, or direct video URL |

**Video embed logic** (in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx)):

```ts
// YouTube  → https://www.youtube.com/embed/{id}?autoplay=1
// Vimeo    → https://player.vimeo.com/video/{id}?autoplay=1
// Other    → URL passed through as-is
const getEmbedUrl = (url: string) => { ... };
```

State: `videoPlaying` (boolean, default `false`). When `true` **and** `videoUrl` is non-empty, the thumbnail+play-button block is replaced by the `<iframe>`. The play SVG `fill` is driven by `exit_intent_t4_play_color` when Advanced Edit is on.

**CSS classes for theme-four** (in [_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss)):

| Class | Role |
|---|---|
| `.nx-exit-intent-popup.nx-exit-intent-theme-four` | Outer card (520 px, gradient background, `overflow: visible`) |
| `.nx-exit-intent-t4-badge` | Badge pill |
| `.nx-exit-intent-t4-title` | Main heading |
| `.nx-exit-intent-t4-subtitle` | Sub-heading |
| `.nx-exit-intent-t4-video-wrap` | 16:9 thumbnail container |
| `.nx-exit-intent-t4-play` | Full-overlay `<button>` |
| `.nx-exit-intent-t4-play-icon` | White circle with SVG triangle |
| `.nx-exit-intent-t4-iframe` | Embedded video frame (shown after play) |
| `.nx-exit-intent-t4-no-image` | Modifier on video-wrap when no thumbnail is set |

---

### Theme Five — Live Flash Sale (with Timer)

Two-column flash-sale layout with a **live countdown timer** and customisable unit labels. This is the timer-equipped sibling of theme-two.

**Content fields** (section: `exit_intent_theme_five_section`):

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_t5_title` | text | `Flash Sale` | Top-of-card title |
| `exit_intent_t5_headline` | text | `50% OFF` | Big headline |
| `exit_intent_t5_desc` | text | `ON ENTIRE ORDER` | Sub-line |
| `exit_intent_t5_show_timer` | toggle | `true` | Master toggle for the countdown block |
| `exit_intent_t5_countdown_label` | text | `LIMITED-TIME OFFER! SALE ENDS IN` | Label above the timer |
| `exit_intent_countdown_end` | **date** | _(empty — falls back to a 1d 14h 30m demo timer)_ | DateTime picker (`type: 'date'`, same control as the Custom Notification "Time" field) |
| `exit_intent_t5_days_label` / `_hours_label` / `_minutes_label` / `_seconds_label` | text | `DAYS` / `HRS` / `MIN` / `SEC` | Unit labels under each number |
| `exit_intent_t5_timer_bg` | colorpicker | `#fff0f5` | Number tile background (content-side) |
| `exit_intent_t5_timer_color` | colorpicker | `#e91e63` | Number tile text color (content-side) |
| `exit_intent_image_url` | image | bundled `theme-two.jpg` | Right-panel background image |
| `exit_intent_button_text` | text | `Shop The Flash Sale Now` | CTA button |
| `exit_intent_dismiss_text` | text | `NO, THANKS!` | Dismiss link |

**Countdown behaviour** (`useCountdown` in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx)):

- Empty `exit_intent_countdown_end` → falls back to a static demo duration (`1d 14h 30m 26s`) so the timer always ticks.
- Non-empty → parsed via `Date.parse(endDateStr)` (with a `' '`→`'T'` fallback). Expired → all units render as `00`.

**CSS classes for theme-five**:

| Class | Role |
|---|---|
| `.nx-exit-intent-popup.nx-exit-intent-theme-five` | Outer card |
| `.nx-exit-intent-t5-left` / `.nx-exit-intent-t5-right` | Two-column halves |
| `.nx-exit-intent-t5-title` / `.nx-exit-intent-t5-headline` / `.nx-exit-intent-t5-desc` | Copy elements |
| `.nx-exit-intent-t5-countdown-label` | Label above the timer |
| `.nx-exit-intent-t5-countdown-num` | Each number tile |
| `.nx-exit-intent-t5-countdown-lbl` | DAYS / HRS / MIN / SEC under each tile |
| `.nx-exit-intent-t5-btn` / `.nx-exit-intent-t5-dismiss` | CTA + dismiss |

---

## Shared Settings (Customize Tab)

Defined in `customize_fields()` of [ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php), registered under section `exit_intent_settings`:

| Field name | Type | Default | Notes |
|---|---|---|---|
| `show_close_button` | toggle | `true` | Renders `×` button top-right |
| `exit_intent_sensitivity` | select | `20` | Top-of-viewport px threshold (`10` high / `20` medium / `50` low) |
| `exit_intent_cookie_days` | number | `7` | Days to suppress after dismissal |
| `exit_intent_mobile_disable` | toggle | `true` | Skip trigger on touch/mobile devices |

Standard timing fields `delay_between` and `display_for` are hidden for this source. `behavior`, `sound_section`, and `queue_management` sections are also hidden.

---

## Advanced Design (Design Tab)

Available when **Advanced Edit** is toggled on (the toggle lives inside the global `advance_design_section`).

### How the panel is structured

`design_fields()` does **not** create per-theme sub-sections. Instead, every theme's design fields are merged **flat** into the parent `advance_design_section.fields` so the user only ever sees a single "Advanced Design" heading. Each merged field carries a per-theme rule:

```php
Rules::logicalRule( [
    Rules::is( 'source', $this->id ),
    Rules::is( 'advance_edit', true ),
    Rules::is( 'themes', $this->id . '_' . $theme_slug ),
] )
```

So the user only sees the controls relevant to whichever theme is currently selected — no per-theme heading, no leakage from other themes.

### Field priorities

A counter starting at `20` is assigned to each merged field in insertion order. The global **Custom CSS** section sits at priority `150`, so all theme controls render *above* Custom CSS, and Custom CSS stays at the bottom. If you add new design fields, do not give them a `priority` ≥ 150 unless you intentionally want them under Custom CSS.

### Per-theme field set

Reused field names that flow across multiple themes are listed once at the bottom of each table.

#### Theme One — Feedback Form

| Category | Fields |
|---|---|
| Container | `exit_intent_max_width`, `exit_intent_border_radius`, `exit_intent_bg_color`, `exit_intent_overlay_color`, `exit_intent_show_pattern`, `exit_intent_pattern_color` |
| Title typography | `exit_intent_title_color`, `exit_intent_title_font_size`, `exit_intent_title_font_weight` |
| Subtitle typography | `exit_intent_subtitle_color`, `exit_intent_subtitle_font_size` |
| Input fields | `exit_intent_input_bg`, `exit_intent_input_border_color`, `exit_intent_input_border_radius`, `exit_intent_input_text_color` |
| Button | `exit_intent_btn_bg`, `exit_intent_btn_color`, `exit_intent_btn_border_radius`, `exit_intent_btn_font_size`, `exit_intent_btn_font_weight` |
| Close button | `exit_intent_close_color`, `exit_intent_close_size` |

Removed (used to exist, no longer registered): `exit_intent_question_color`, `exit_intent_question_font_size` (no question element in JSX), `exit_intent_input_focus_color`, `exit_intent_placeholder_color`, `exit_intent_btn_hover_bg` (require `:focus` / `::placeholder` / `:hover` selectors that inline styles can't deliver).

#### Theme Two — Flash Sale

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t2_max_width`, `exit_intent_t2_border_radius`, `exit_intent_t2_bg_color`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Sale badge | `exit_intent_t2_badge_bg`, `exit_intent_t2_badge_color`, `exit_intent_t2_badge_font_size` |
| Headline | `exit_intent_t2_headline_color`, `exit_intent_t2_headline_font_size`, `exit_intent_t2_headline_font_weight` |
| Description | `exit_intent_t2_desc_color`, `exit_intent_t2_desc_font_size` |
| CTA button | `exit_intent_t2_btn_bg`, `exit_intent_t2_btn_color`, `exit_intent_t2_btn_border_radius`, `exit_intent_t2_btn_font_size`, `exit_intent_t2_btn_font_weight` |
| Dismiss link | `exit_intent_t2_dismiss_color`, `exit_intent_t2_dismiss_font_size` |

> Theme-two's countdown design fields (`exit_intent_t2_cd_*`) were removed alongside the content fields when the timer was dropped from this theme.

#### Theme Three — Coupon Offer

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t3_max_width`, `exit_intent_t3_border_radius`, `exit_intent_t3_bg_color`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Title | `exit_intent_t3_title_color`, `exit_intent_t3_title_font_size`, `exit_intent_t3_title_font_weight` |
| Subtitle | `exit_intent_t3_subtitle_color`, `exit_intent_t3_subtitle_font_size` |
| Offer line | `exit_intent_t3_offer_color`, `exit_intent_t3_offer_font_size`, `exit_intent_t3_offer_font_weight` |
| Coupon block | `exit_intent_t3_coupon_bg`, `exit_intent_t3_coupon_color`, `exit_intent_t3_coupon_font_size`, `exit_intent_t3_coupon_border_radius` |
| CTA button | `exit_intent_t3_btn_bg`, `exit_intent_t3_btn_color`, `exit_intent_t3_btn_border_radius`, `exit_intent_t3_btn_font_size`, `exit_intent_t3_btn_font_weight` |
| Dismiss link | `exit_intent_t3_dismiss_color`, `exit_intent_t3_dismiss_font_size` |

#### Theme Four — Video Popup

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t4_max_width`, `exit_intent_t4_border_radius`, `exit_intent_t4_bg_color`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Badge | `exit_intent_t4_badge_bg`, `exit_intent_t4_badge_color`, `exit_intent_t4_badge_font_size` |
| Title | `exit_intent_t4_title_color`, `exit_intent_t4_title_font_size`, `exit_intent_t4_title_font_weight` |
| Subtitle | `exit_intent_t4_subtitle_color`, `exit_intent_t4_subtitle_font_size` |
| Video wrap + play icon | `exit_intent_t4_video_bg`, `exit_intent_t4_video_radius`, `exit_intent_t4_play_bg`, `exit_intent_t4_play_color` |

#### Theme Five — Live Flash Sale

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t5_max_width`, `exit_intent_t5_border_radius`, `exit_intent_t5_bg_color`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Title | `exit_intent_t5_title_color`, `exit_intent_t5_title_font_size`, `exit_intent_t5_title_font_weight` |
| Headline | `exit_intent_t5_headline_color`, `exit_intent_t5_headline_font_size`, `exit_intent_t5_headline_font_weight` |
| Description | `exit_intent_t5_desc_color`, `exit_intent_t5_desc_font_size` |
| Countdown label | `exit_intent_t5_cd_label_color`, `exit_intent_t5_cd_label_font_size` |
| Countdown number tile | `exit_intent_t5_cd_num_bg`, `exit_intent_t5_cd_num_color`, `exit_intent_t5_cd_num_font_size`, `exit_intent_t5_cd_num_radius` |
| Countdown unit label | `exit_intent_t5_cd_unit_color`, `exit_intent_t5_cd_unit_font_size` |
| CTA button | `exit_intent_t5_btn_bg`, `exit_intent_t5_btn_color`, `exit_intent_t5_btn_border_radius`, `exit_intent_t5_btn_font_size`, `exit_intent_t5_btn_font_weight` |
| Dismiss link | `exit_intent_t5_dismiss_color`, `exit_intent_t5_dismiss_font_size` |

> Countdown number bg/color: when Advanced Edit is **on**, the design-side `exit_intent_t5_cd_num_bg` / `_color` override the content-side `exit_intent_t5_timer_bg` / `_color`. With Advanced Edit off, the content-side values are used.

> All "Countdown …" design fields are gated by `Rules::is('exit_intent_t5_show_timer', true)` so they only show when the timer is enabled.

### How styles reach the DOM

`ExitIntentPopup.tsx` builds per-theme `React.CSSProperties` objects gated by an `adv = !!settings.advance_edit` flag, then attaches them inline to the matching elements (popup container, close button, badge, title, headline, etc.). Pseudo-state styling (`:hover`, `:focus`, `::placeholder`) is intentionally not exposed — inline styles can't deliver it without a scoped `<style>` block, and the registered fields would be dead.

---

## Data Flow

```
PHP: ExitIntentNotification registers themes & fields
       ↓
FrontEnd.php: get_notifications_data() → $result['exit_intent'][nx_id]['post'] = $settings
       ↓
REST JSON response → response.exit_intent
       ↓
utils.ts: normalizePressBar() → array of { post, content }
       ↓
useNotificationX.ts: setExitIntentNotices(response.exit_intent)
       ↓
useNotificationX.ts: document mouseleave → dispatchNotification({ config: exitItem.post })
       ↓
Redux store: ADD_NOTIFICATION → { id, data, config }
       ↓
NotificationContainer.tsx: config.type === 'exit_intent' → <ExitIntentPopup nxExitIntent={notice} />
       ↓
ExitIntentPopup.tsx: reads settings = nxExitIntent.config → renders theme
```
