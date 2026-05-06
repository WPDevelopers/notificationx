# Exit Intent Popup

The Exit Intent Popup feature detects when a visitor is about to leave the page (mouse moves toward the browser chrome) and displays a targeted modal popup to recover abandoning users.

---

## Key Files

### Backend (PHP)

| File | Purpose |
|------|---------|
| [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) | Extension class — registers all 4 themes, defines all content/design/customize fields, and sets per-theme defaults |
| [includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php) | Notification type registration (`exit_intent`) |
| [includes/FrontEnd/FrontEnd.php](../includes/FrontEnd/FrontEnd.php) | Serializes enabled exit intent notifications into the REST response under the `exit_intent` key (see `get_notifications_data`) |

### Frontend (React / TypeScript)

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) | React component — renders all 4 themes, owns `videoPlaying` state for theme-four, handles close/play interactions |
| [nxdev/notificationx/frontend/core/useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) | Attaches `document` `mouseleave` listener; triggers `dispatchNotification` when cursor crosses the top threshold; stores each `nx_id` in a `triggered` Set so it fires only once per page load |
| [nxdev/notificationx/frontend/core/NotificationContainer.tsx](../nxdev/notificationx/frontend/core/NotificationContainer.tsx) | Detects `config.type === 'exit_intent'` and renders `<ExitIntentPopup>` for each notice |
| [nxdev/notificationx/frontend/core/utils.ts](../nxdev/notificationx/frontend/core/utils.ts) | `normalizePressBar()` converts the PHP keyed object into a JS array for `exitIntentNotices`; `isNotClosed()` skips already-dismissed entries |

### Styles

| File | Purpose |
|------|---------|
| [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) | All exit intent SCSS — overlay, base popup, and per-theme blocks (theme-one ~line 26, theme-two ~line 210, theme-three ~line 470, theme-four ~line 657) |

### Compiled Assets

| File | Purpose |
|------|---------|
| [nxbuild/public/js/frontend.js](../nxbuild/public/js/frontend.js) | Compiled JS (contains compiled `ExitIntentPopup` — theme-four video logic at ~line 53645) |
| [nxbuild/public/css/frontend.css](../nxbuild/public/css/frontend.css) | Compiled CSS including all exit intent styles |

---

## Trigger Mechanism

Defined in [useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) (around line 573):

```ts
document.addEventListener('mouseleave', handleMouseLeave);

const handleMouseLeave = (e: MouseEvent) => {
    if (e.clientY > 10) return; // only fire when cursor approaches top
    // fires once per nx_id per page load (tracked in `triggered` Set)
    dispatchNotification({ data: exitItem.content, config: exitItem.post });
};
```

- **Session persistence** — closing a popup sets `sessionStorage` key `notificationx_exit_intent_{nx_id}` to `'closed'`; subsequent `mouseleave` events skip it.
- **One-shot per page load** — once fired, the `nx_id` is added to `triggered` so it cannot re-open without a page refresh.

---

## Themes

### Theme One — Feedback Form

Default theme. Shows a title, subtitle, question with reason dropdown, optional name/email inputs, and a submit button.

**Content fields** (shown when theme-one is active and `exit_intent_content_section` rules pass):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_title` | text | `Wait! Before You Go...` |
| `exit_intent_subtitle` | text | `We'd love to understand…` |
| `exit_intent_question` | text | `What's stopping you from getting {product} today?` |
| `exit_intent_product_name` | text | `our product` |
| `exit_intent_reasons` | repeater | 4 preset reasons |
| `exit_intent_reason_placeholder` | text | `Select Reason` |
| `exit_intent_show_reason` | toggle | `true` |
| `exit_intent_show_name` | toggle | `true` |
| `exit_intent_show_email` | toggle | `true` |
| `exit_intent_name_label` | text | `Name *` |
| `exit_intent_email_label` | text | `Enter Your Email *` |
| `exit_intent_button_text` | text | `SUBMIT` |

---

### Theme Two — Flash Sale with Countdown

Two-column layout: left column has sale headline + optional countdown timer + CTA button; right column is a background image panel.

**Content fields** (section: `exit_intent_theme_two_section`):

| Field name | Type | Default |
|---|---|---|
| `exit_intent_image_url` | image | bundled `theme-two.jpg` |
| `exit_intent_sale_badge` | text | `Flash Sale` |
| `exit_intent_sale_headline` | text | `50% OFF` |
| `exit_intent_sale_desc` | text | `ON ENTIRE ORDER` |
| `exit_intent_countdown_label` | text | `LIMITED-TIME OFFER! SALE ENDS IN` |
| `exit_intent_countdown_end` | datetime | _(empty — disables countdown)_ |
| `exit_intent_button_text` | text | `Shop The Flash Sale Now` |
| `exit_intent_dismiss_text` | text | `NO, THANKS!` |

The countdown is driven by `useCountdown` in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) (line 3). When `exit_intent_countdown_end` is empty or expired, the countdown block is hidden.

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

**Video embed logic** (in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) lines 63–75):

```ts
// YouTube  → https://www.youtube.com/embed/{id}?autoplay=1
// Vimeo    → https://player.vimeo.com/video/{id}?autoplay=1
// Other    → URL passed through as-is
const getEmbedUrl = (url: string) => { ... };

const handlePlay = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (videoUrl && !videoPlaying) setVideoPlaying(true);
};
```

State: `videoPlaying` (boolean, default `false`). When `true` **and** `videoUrl` is non-empty, the thumbnail+play-button block is replaced by the `<iframe>`.

**CSS classes for theme-four** (in [_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) line 657+):

| Class | Role |
|---|---|
| `.nx-exit-intent-popup.nx-exit-intent-theme-four` | Outer card (520 px, gradient background, `overflow: visible`) |
| `.nx-exit-intent-t4-badge` | Badge pill |
| `.nx-exit-intent-t4-title` | Main heading |
| `.nx-exit-intent-t4-subtitle` | Sub-heading |
| `.nx-exit-intent-t4-video-wrap` | 16:9 thumbnail container (`position: relative`, `overflow: hidden`) |
| `.nx-exit-intent-t4-play` | Full-overlay `<button>` (`position: absolute; inset: 0`) |
| `.nx-exit-intent-t4-play-icon` | White 72 px circle with SVG triangle |
| `.nx-exit-intent-t4-iframe` | Embedded video frame (16:9 aspect ratio, shown after play) |
| `.nx-exit-intent-t4-no-image` | Modifier on video-wrap when no thumbnail is set |

---

## Shared Settings (Customize Tab)

Defined in `customize_fields()` of [ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) (line 665), registered under section `exit_intent_settings`:

| Field name | Type | Default | Notes |
|---|---|---|---|
| `show_close_button` | toggle | `true` | Renders `×` button top-right |
| `exit_intent_sensitivity` | select | `20` | Top-of-viewport px threshold (`10` high / `20` medium / `50` low) |
| `exit_intent_cookie_days` | number | `7` | Days to suppress after dismissal |
| `exit_intent_mobile_disable` | toggle | `true` | Skip trigger on touch/mobile devices |

Standard timing fields `delay_between` and `display_for` are hidden for this source. `behavior`, `sound_section`, and `queue_management` sections are also hidden.

---

## Advanced Design (Design Tab)

Available when **Advanced Edit** is enabled. Defined in `design_fields()` ([ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) line 442) and applied at render time in [ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) (theme-one only, lines 255–291).

| Category | Fields |
|---|---|
| Container | `exit_intent_max_width`, `exit_intent_border_radius`, `exit_intent_bg_color`, `exit_intent_overlay_color`, `exit_intent_show_pattern`, `exit_intent_pattern_color` |
| Title typography | `exit_intent_title_color`, `exit_intent_title_font_size`, `exit_intent_title_font_weight` |
| Subtitle typography | `exit_intent_subtitle_color`, `exit_intent_subtitle_font_size` |
| Question typography | `exit_intent_question_color`, `exit_intent_question_font_size` |
| Input fields | `exit_intent_input_bg`, `exit_intent_input_border_color`, `exit_intent_input_focus_color`, `exit_intent_input_border_radius`, `exit_intent_input_text_color`, `exit_intent_placeholder_color` |
| Button | `exit_intent_btn_bg`, `exit_intent_btn_hover_bg`, `exit_intent_btn_color`, `exit_intent_btn_border_radius`, `exit_intent_btn_font_size`, `exit_intent_btn_font_weight` |
| Close button | `exit_intent_close_color`, `exit_intent_close_size` |

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
