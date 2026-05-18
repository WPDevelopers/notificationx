# Exit Intent Popup

The Exit Intent Popup feature detects when a visitor is about to leave the page (mouse moves toward the browser chrome) and displays a targeted modal popup to recover abandoning users.

There are **seven themes** (`theme-one` … `theme-seven`). Each theme owns its own content section, its own slice of the flat Advanced Design panel, and its own React render branch. Some shared keys (`exit_intent_image_url`, `exit_intent_button_text`, `exit_intent_button_url`, `exit_intent_button_new_tab`, `exit_intent_dismiss_text`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size`) flow across multiple themes.

> The order of the cards in the admin theme picker is **not** the same as the slug order. The current order (registered in `init_extension()`) is: theme-one → theme-two → theme-five → theme-four → theme-seven → theme-six → theme-three.

---

## Key Files

### Backend (PHP)

| File | Purpose |
|------|---------|
| [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) | Extension class — registers all 7 themes, defines all content/design/customize fields, and sets per-theme defaults |
| [includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php) | Notification type registration (`exit_intent`) |
| [includes/FrontEnd/FrontEnd.php](../includes/FrontEnd/FrontEnd.php) | Serializes enabled exit intent notifications into the REST response under the `exit_intent` key (see `get_notifications_data`) |

### Frontend (React / TypeScript)

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) | React component — renders all 7 themes; owns `videoPlaying` state (theme-four), `submitted`/`submitting` form state (theme-one, theme-seven), and drives the `useCountdown` hook (theme-five, theme-six) |
| [nxdev/notificationx/frontend/core/useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) | Attaches `document` `mouseleave` listener; triggers `dispatchNotification` when cursor crosses the top threshold; stores each `nx_id` in a `triggered` Set so it fires only once per page load |
| [nxdev/notificationx/frontend/core/NotificationContainer.tsx](../nxdev/notificationx/frontend/core/NotificationContainer.tsx) | Detects `config.type === 'exit_intent'` and renders `<ExitIntentPopup>` for each notice |
| [nxdev/notificationx/frontend/core/utils.ts](../nxdev/notificationx/frontend/core/utils.ts) | `normalizePressBar()` converts the PHP keyed object into a JS array for `exitIntentNotices`; `isNotClosed()` skips already-dismissed entries |

### Styles

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) | All exit intent SCSS — overlay, base popup, and per-theme blocks for theme-one … theme-seven |

### Theme preview assets

| Path | Purpose |
|------|---------|
| [assets/admin/images/extensions/themes/exit-intent/](../assets/admin/images/extensions/themes/exit-intent/) | PNG previews shown in the admin theme picker (`exit-intent-theme-one.png` … `exit-intent-theme-seven.png`) |

> Two of the registered preview URLs are intentionally cross-wired in `init_extension()`: `theme-six`'s registry entry points at `exit-intent-theme-seven.png` and `theme-seven`'s points at `exit-intent-theme-six.png`. This swap matches the visual order chosen for the admin picker. Don't "fix" it without updating the picker layout to match.

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

- **Session + cookie persistence** — closing a popup sets `sessionStorage` key `notificationx_exit_intent_{nx_id}_{theme}` to `'closed'`. When `exit_intent_cookie_days` > 0 the same key is also written as a cookie with `expires` set N days ahead; subsequent `mouseleave` events skip it.
- **One-shot per page load** — once fired, the `nx_id` is added to `triggered` so it cannot re-open without a page refresh.

---

## Shared CTA helper (`renderCta`)

All themes that show a primary call-to-action route it through `renderCta` inside `ExitIntentPopup.tsx`:

```ts
const renderCta = (className, style, label) => {
    const url    = (s.exit_intent_button_url || '').trim();
    const newTab = s.exit_intent_button_new_tab !== false;
    if (url) {
        return <a href={url} target={newTab ? '_blank' : '_self'} rel={newTab ? 'noopener noreferrer' : undefined} onClick={handleClose}>{label}</a>;
    }
    return <button type="button" onClick={handleClose}>{label}</button>;
};
```

That gives every theme two cross-theme content fields automatically:

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_button_url` | text | _(empty)_ | When set, the CTA renders as `<a href>` instead of a plain `<button>` |
| `exit_intent_button_new_tab` | toggle | `true` | Opens the URL in a new tab (`target="_blank"` + `rel="noopener noreferrer"`) |

Theme-one and theme-seven do **not** use `renderCta` — they submit a form and have their own `<button type="submit">`.

---

## Themes

### Theme One — Feedback Form

Default theme. Shows a title, subtitle, optional name/email/message inputs, and a submit button.

**Content fields** (section: `exit_intent_content_section`, applies to every theme that is **not** two/three/four/five/six/seven — effectively theme-one only):

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_title` | text | `Wait! Before You Go...` |  |
| `exit_intent_subtitle` | text | `We'd love to understand…` |  |
| `exit_intent_show_name` | toggle | `true` | **Pro-only** — `is_pro: true`; ignored on free regardless of the saved value |
| `exit_intent_name_label` | text | `Name *` | Gated by `exit_intent_show_name` |
| `exit_intent_show_email` | toggle | `true` | **Pro-only** — `is_pro: true` |
| `exit_intent_email_label` | text | `Enter Your Email *` | Gated by `exit_intent_show_email` |
| `exit_intent_show_message` | toggle | `true` | Free |
| `exit_intent_message_placeholder` | text | `Your message...` | Gated by `exit_intent_show_message` |
| `exit_intent_button_text` | text | `SUBMIT` |  |

Submitting the form POSTs `{ nx_id, theme, title, name?, email?, message? }` to `popup-submit` via `nxHelper.post`. Success toggles a `submitted` UI state, then auto-closes after 2.5 s. On the free build, the React component forces `_showName` / `_showEmail` to `false` via the `is_pro` context flag before reading those toggles, so the pro-gated fields never reach the payload.

---

### Theme Two — Flash Sale

Two-column layout: left column has sale headline + CTA + dismiss link; right column is a background image panel.

**Content fields** (section: `exit_intent_theme_two_section`):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_image_url` | media | bundled flash-sale image |
| `exit_intent_sale_badge` | text | `Flash Sale` |
| `exit_intent_sale_headline` | text | `50% OFF` |
| `exit_intent_sale_desc` | text | `ON ENTIRE ORDER` |
| `exit_intent_button_text` | text | `Shop The Flash Sale Now` |
| `exit_intent_button_url` | text | _(empty)_ |
| `exit_intent_button_new_tab` | toggle | `true` |
| `exit_intent_dismiss_text` | text | `NO, THANKS!` |

> Theme-two has **no** countdown timer. Use [theme-five](#theme-five--live-flash-sale-with-timer) or [theme-six](#theme-six--product-with-countdown) when a live countdown is required.

---

### Theme Three — Coupon Offer

Single-column layout with an optional character illustration image, offer text, coupon code block, and CTA + dismiss buttons.

**Content fields** (section: `exit_intent_theme_three_section`):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_image_url` | media | bundled theme-three illustration |
| `exit_intent_t3_title` | text | `Wait, don't go!` |
| `exit_intent_t3_subtitle` | text | `Before you leave, we have a special offer just for you!` |
| `exit_intent_t3_offer` | text | `Get 15% off your next purchase!` |
| `exit_intent_t3_coupon_text` | text | `Use code STAY15 at checkout…` |
| `exit_intent_button_text` | text | `Claim Offer` |
| `exit_intent_button_url` / `exit_intent_button_new_tab` | text + toggle | shared CTA fields |
| `exit_intent_dismiss_text` | text | `No, thanks!` |

---

### Theme Four — Video Popup

Center-aligned card with a badge pill, heading, subtitle, and an embedded video player. Supports YouTube and Vimeo URLs. Before clicking play, shows an optional thumbnail image with a play button overlay. After clicking, swaps to an `<iframe>` with `?autoplay=1`.

**Content fields** (section: `exit_intent_theme_four_section`):

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_image_url` | media | bundled theme-four image | Optional thumbnail shown before play |
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
| `exit_intent_countdown_end` | **date** | _(empty — falls back to demo timer)_ | DateTime picker (`type: 'date'`) |
| `exit_intent_t5_days_label` / `_hours_label` / `_minutes_label` / `_seconds_label` | text | `DAYS` / `HRS` / `MIN` / `SEC` | Unit labels |
| `exit_intent_t5_timer_bg` | colorpicker | `#fff0f5` | Number tile background (content-side) |
| `exit_intent_t5_timer_color` | colorpicker | `#e91e63` | Number tile text color (content-side) |
| `exit_intent_image_url` | media | bundled flash-sale image | Right-panel background image |
| `exit_intent_button_text` | text | `Shop The Flash Sale Now` | CTA button |
| `exit_intent_button_url` / `exit_intent_button_new_tab` | text + toggle | shared CTA fields |
| `exit_intent_dismiss_text` | text | `NO, THANKS!` | Dismiss link |

**Countdown behaviour** (`useCountdown` in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx)):

- Theme-five and theme-six both pass a non-empty `fallbackDurationMs` (~2d 14h 30m 21s) into `useCountdown`. Empty `exit_intent_countdown_end` → the fallback locks in on first render and the timer ticks down from there. Non-empty → parsed via `Date.parse(endDateStr)` (with a `' '`→`'T'` fallback). Expired → all units render as `00`.

> **Customizing the countdown number tiles:** when Advanced Edit is **on**, the design-side `exit_intent_t5_cd_num_bg` / `_color` override the content-side `exit_intent_t5_timer_bg` / `_color`. With Advanced Edit off, the content-side values are used.

---

### Theme Six — Product with Countdown

Single-column, radial-gradient card centered on a product image, with a large title, **optional** countdown timer, and a CTA. Both the timer block and the timer-related design fields are gated by a dedicated toggle (`exit_intent_t6_show_timer`).

The content fields live in **two** sections:

#### `exit_intent_theme_six_section` — main content

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_image_url` | media | bundled theme-six product image | Rendered above the title |
| `exit_intent_t6_title` | text | `Limited Edition Bass Boost Headphones` | Main heading |
| `exit_intent_button_text` | text | `Grab Now` |  |
| `exit_intent_button_url` | text | _(empty)_ | CTA URL (renders an `<a>` when set; otherwise a `<button>`) |
| `exit_intent_button_new_tab` | toggle | `true` |  |

#### `exit_intent_theme_six_timer_section` — countdown

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_t6_show_timer` | toggle | `true` | Master switch for the timer block **and** for every `t6_cd_*` design field |
| `exit_intent_t6_countdown_label` | text | `Offer Ends In` | Label above the timer |
| `exit_intent_countdown_end` | **date** | _(empty — falls back to demo timer)_ | Shared field name with theme-five (only one theme is active at a time, so there's no collision) |
| `exit_intent_t6_days_label` / `_hours_label` / `_minutes_label` / `_seconds_label` | text | `DAYS` / `HOURS` / `MIN` / `SEC` | Unit labels |

The numbers render with a `:` separator between units (`<span class="nx-exit-intent-t6-countdown-sep">`) — the separator inherits the countdown-number font size via inline style.

---

### Theme Seven — Email Lead Capture (Two-Column Image)

Two-column layout: left column is a full-bleed image panel, right column shows a headline, a discount banner, descriptive copy, an email input, and a submit button. After submission, the form is replaced by an inline success message with a checkmark SVG and auto-closes after 2.5 s.

**Content fields** (section: `exit_intent_theme_seven_section`):

| Field name | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_image_url` | media | bundled theme-seven image | Left panel background |
| `exit_intent_t7_headline` | text | `Home Is Where Your Story Begins` | Main headline |
| `exit_intent_t7_discount_text` | text | `Get 15% Off Your First Order!` | Discount banner (hidden when empty) |
| `exit_intent_t7_description` | text | `Discover timeless pieces…` | Description paragraph (hidden when empty) |
| `exit_intent_t7_email_placeholder` | text | `Enter your email` | Placeholder for the email input |
| `exit_intent_button_text` | text | `SEND COUPON` | Submit button label |

Submitting the form POSTs `{ nx_id, theme, title: headline, email }` to `popup-submit` via `nxHelper.post` and reuses the same `submitting` / `submitted` state as theme-one. There is no name/message field; theme-seven is intentionally an email-only capture.

The right-panel content uses an inline `fontFamily` override driven by `exit_intent_t7_headline_font_family` (Playfair Display, Georgia, Times New Roman, Inter, Helvetica). The image panel falls back to the `t7_image_bg` solid color when no image is set or when the image leaves transparent areas.

---

## Shared Settings (Customize Tab)

Defined in `customize_fields()` of [ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php), registered under section `exit_intent_settings`:

| Field name | Type | Default | Notes |
|---|---|---|---|
| `show_close_button` | toggle | `true` | Renders `×` button top-right |
| `exit_intent_sensitivity` | select | `20` | Top-of-viewport px threshold (`10` high / `20` medium / `50` low) |
| `exit_intent_cookie_days` | number | `7` | Days to suppress after dismissal (drives the dismissal cookie as well as the sessionStorage flag) |
| `exit_intent_mobile_disable` | toggle | `true` | Skip trigger on touch/mobile devices |

Standard timing fields `delay_between` and `display_for` are hidden for this source. `behavior`, `sound_section`, and `queue_management` sections are also hidden.

---

## Advanced Design (Design Tab)

Available when **Advanced Edit** is toggled on (the toggle lives inside the global `advance_design_section`).

### How the panel is structured

`design_fields()` does **not** create per-theme sub-sections. Instead, every theme contributes a list via a private helper (`theme_one_design_fields()` … `theme_seven_design_fields()`) and a local `$merge( $theme_slug, $fields )` closure merges them **flat** into the parent `advance_design_section.fields`. Each merged field is wrapped with:

```php
Rules::logicalRule( [
    Rules::is( 'source', $this->id ),
    Rules::is( 'advance_edit', true ),
    Rules::is( 'themes', $this->id . '_' . $theme_slug ),
] )
```

So the user sees a single "Advanced Design" heading and only the controls relevant to whichever theme is currently selected.

### Field priorities

A counter starting at `20` is assigned to each merged field in insertion order. The global **Custom CSS** section sits at priority `150`, so all theme controls render *above* Custom CSS, and Custom CSS stays at the bottom. If you add new design fields, do not give them a `priority` ≥ 150 unless you intentionally want them under Custom CSS.

### Per-theme field set

Reused field names that flow across multiple themes (`exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size`) are merged once per theme — collisions overwrite by `name`, so each theme can ship its own defaults for the shared keys.

#### Theme One — Feedback Form

| Category | Fields |
|---|---|
| Container | `exit_intent_max_width`, `exit_intent_border_radius`, `exit_intent_bg_color`, `exit_intent_overlay_color`, `exit_intent_show_pattern`, `exit_intent_pattern_color` |
| Title typography | `exit_intent_title_color`, `exit_intent_title_font_size`, `exit_intent_title_font_weight` |
| Subtitle typography | `exit_intent_subtitle_color`, `exit_intent_subtitle_font_size` |
| Input fields | `exit_intent_input_bg`, `exit_intent_input_border_color`, `exit_intent_input_border_radius`, `exit_intent_input_text_color` |
| Button | `exit_intent_btn_bg`, `exit_intent_btn_color`, `exit_intent_btn_border_radius`, `exit_intent_btn_font_size`, `exit_intent_btn_font_weight` |
| Close button | `exit_intent_close_color`, `exit_intent_close_size` |

#### Theme Two — Flash Sale

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t2_max_width`, `exit_intent_t2_border_radius`, `exit_intent_t2_bg_color`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Sale badge | `exit_intent_t2_badge_bg`, `exit_intent_t2_badge_color`, `exit_intent_t2_badge_font_size` |
| Headline | `exit_intent_t2_headline_color`, `exit_intent_t2_headline_font_size`, `exit_intent_t2_headline_font_weight` |
| Description | `exit_intent_t2_desc_color`, `exit_intent_t2_desc_font_size` |
| CTA button | `exit_intent_t2_btn_bg`, `exit_intent_t2_btn_color`, `exit_intent_t2_btn_border_radius`, `exit_intent_t2_btn_font_size`, `exit_intent_t2_btn_font_weight` |
| Dismiss link | `exit_intent_t2_dismiss_color`, `exit_intent_t2_dismiss_font_size` |

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

> All "Countdown …" design fields are gated by `Rules::is('exit_intent_t5_show_timer', true)` so they only show when the timer is enabled.

#### Theme Six — Product with Countdown

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t6_max_width`, `exit_intent_t6_border_radius`, `exit_intent_t6_bg_start`, `exit_intent_t6_bg_mid`, `exit_intent_t6_bg_end`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Title | `exit_intent_t6_title_color`, `exit_intent_t6_title_font_size`, `exit_intent_t6_title_font_weight` |
| Countdown label | `exit_intent_t6_cd_label_color`, `exit_intent_t6_cd_label_font_size` |
| Countdown number tile | `exit_intent_t6_cd_num_bg`, `exit_intent_t6_cd_num_color`, `exit_intent_t6_cd_num_font_size`, `exit_intent_t6_cd_num_radius` |
| Countdown unit label | `exit_intent_t6_cd_unit_color`, `exit_intent_t6_cd_unit_font_size` |
| CTA button | `exit_intent_t6_btn_bg`, `exit_intent_t6_btn_color`, `exit_intent_t6_btn_border_radius`, `exit_intent_t6_btn_font_size`, `exit_intent_t6_btn_font_weight` |

> The card background is composed at render time as `radial-gradient(circle at center, t6_bg_start 0%, t6_bg_mid 50%, t6_bg_end 100%)`. All three stops are exposed as colorpicker fields. With Advanced Edit off, the gradient falls back to hard-coded defaults (`#ffffff` → `#fdf2f8` → `#f5f3ff`).
>
> All `exit_intent_t6_cd_*` design fields are gated by `Rules::is('exit_intent_t6_show_timer', true)`.

#### Theme Seven — Email Lead Capture

| Category | Fields |
|---|---|
| Container / overlay / close | `exit_intent_t7_max_width`, `exit_intent_t7_border_radius`, `exit_intent_t7_bg_color`, `exit_intent_t7_image_bg`, `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size` |
| Headline | `exit_intent_t7_headline_color`, `exit_intent_t7_headline_font_size`, `exit_intent_t7_headline_font_weight`, `exit_intent_t7_headline_font_family` |
| Discount banner | `exit_intent_t7_discount_bg`, `exit_intent_t7_discount_border`, `exit_intent_t7_discount_color`, `exit_intent_t7_discount_font_size`, `exit_intent_t7_discount_radius` |
| Description | `exit_intent_t7_desc_color`, `exit_intent_t7_desc_font_size` |
| Email input | `exit_intent_t7_input_bg`, `exit_intent_t7_input_border_color`, `exit_intent_t7_input_border_radius`, `exit_intent_t7_input_text_color` |
| CTA button | `exit_intent_t7_btn_bg`, `exit_intent_t7_btn_color`, `exit_intent_t7_btn_border_radius`, `exit_intent_t7_btn_font_size`, `exit_intent_t7_btn_font_weight` |

> `exit_intent_t7_image_bg` is **separate** from `exit_intent_t7_bg_color` — the image panel sits on its own background which is visible whenever the image is transparent or fails to load. `exit_intent_t7_bg_color` is the right (content) panel only.
>
> `exit_intent_t7_headline_font_family` ships a curated list (Playfair Display, Georgia, Times New Roman, Inter, Helvetica + inherit). Inline-style only — adding fonts means also updating `theme_seven_design_fields()`.

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
ExitIntentPopup.tsx: reads settings = nxExitIntent.config → renders theme-N branch
```
