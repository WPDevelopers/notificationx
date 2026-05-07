# Adding a New Design (Theme) to the Exit Intent Popup

This guide is a **companion to** [exit-intent-popup.md](exit-intent-popup.md), which documents the existing feature (data flow, trigger mechanism, and existing themes `theme-one` … `theme-four`). Read that first.

This document is a step-by-step **how-to** for adding a fifth design — what to touch, in what order, and what to leave alone.

---

## 1. File Map

| # | Concern | File |
|---|---------|------|
| 1 | Theme registry, content fields, design fields, customize fields | [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) |
| 2 | Notification type registration (no change usually needed) | [includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php) |
| 3 | REST serializer (no change needed — generic) | [includes/FrontEnd/FrontEnd.php](../includes/FrontEnd/FrontEnd.php) |
| 4 | React renderer — add a branch for the new theme | [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) |
| 5 | Frontend stylesheet — add a per-theme block | [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) |
| 6 | Admin theme picker preview SVG | [assets/admin/images/extensions/themes/exit-intent/](../assets/admin/images/extensions/themes/exit-intent/) |
| 7 | Photo/image assets used by the theme (optional) | `assets/common/exit-intend-popup/` |

The factory wiring at [includes/Types/TypesFactory.php](../includes/Types/TypesFactory.php) and [includes/Extensions/ExtensionFactory.php](../includes/Extensions/ExtensionFactory.php) is already in place. **Do not modify** these for a new theme.

---

## 2. Naming Conventions

Match what existing themes do (see [exit-intent-popup.md](exit-intent-popup.md) for full field tables):

- **Theme key:** `theme-five` (used in the React `theme === '...'` check and in field rules)
- **Theme rules selector:** `Rules::is('themes', 'exit_intent_custom_theme-five')`
- **Field name prefix:** `exit_intent_` for shared fields, `exit_intent_t5_*` for theme-specific ones (mirroring the existing `t3_*` / `t4_*` pattern)
- **CSS root class:** `.nx-exit-intent-popup.nx-exit-intent-theme-five`
- **Per-theme content section ID:** `exit_intent_theme_five_section`
- **SVG filename:** `exit-intent-theme-five.svg`

---

## 3. Step-by-Step Implementation

### Step 1 — Register the new theme in the extension

**File:** [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php)
**Method:** `init_extension()` — locate the `$this->themes` array.

Add a new entry:

```php
'theme-five' => [
    'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-five.svg',
    'defaults' => [
        'exit_intent_t5_title'    => __( 'Don\'t leave yet!', 'notificationx' ),
        'exit_intent_t5_subtitle' => __( 'Grab 20% off before you go.', 'notificationx' ),
        'exit_intent_button_text' => __( 'Claim Offer', 'notificationx' ),
        // any other field keys your theme exposes
    ],
    'column'   => '5',
],
```

> `source` is the preview SVG. Defaults populate the form on first load and must use the **same field names** you'll register in Step 2.

### Step 2 — Add content fields for the new theme

Same file, `content_fields()`. Each existing theme has its own section gated by a theme rule. Mirror that pattern with a new section, e.g. `exit_intent_theme_five_section`:

```php
'exit_intent_theme_five_section' => [
    'label'  => __( 'Theme Five Content', 'notificationx' ),
    'rules'  => Rules::is( 'themes', 'exit_intent_custom_theme-five' ),
    'fields' => [
        'exit_intent_t5_title' => [
            'label'    => __( 'Headline', 'notificationx' ),
            'type'     => 'text',
            'priority' => 10,
        ],
        'exit_intent_t5_subtitle' => [
            'label'    => __( 'Subtitle', 'notificationx' ),
            'type'     => 'text',
            'priority' => 20,
        ],
        'exit_intent_button_text' => [
            'label'    => __( 'Button Text', 'notificationx' ),
            'type'     => 'text',
            'priority' => 30,
        ],
    ],
],
```

Reuse shared keys (`exit_intent_button_text`, `exit_intent_image_url`, `exit_intent_dismiss_text`) wherever the new theme has the same concept — the React renderer already understands them.

### Step 3 — Add design fields (optional)

Same file, `design_fields()` (around line 442). If the new theme has unique stylable parts, add them inside a `Rules::is('themes', 'exit_intent_custom_theme-five')` block. Reuse existing keys where possible:

- Container: `exit_intent_max_width`, `exit_intent_border_radius`, `exit_intent_bg_color`, `exit_intent_overlay_color`
- Button: `exit_intent_btn_bg`, `exit_intent_btn_hover_bg`, `exit_intent_btn_color`, `exit_intent_btn_border_radius`
- Title typography: `exit_intent_title_color`, `exit_intent_title_font_size`, `exit_intent_title_font_weight`
- Close button: `exit_intent_close_color`, `exit_intent_close_size`

Full list in [exit-intent-popup.md § Advanced Design](exit-intent-popup.md#advanced-design-design-tab).

### Step 4 — Customize tab settings

**No change needed.** The customize tab settings (`show_close_button`, `exit_intent_sensitivity`, `exit_intent_cookie_days`, `exit_intent_mobile_disable`) defined in `customize_fields()` (around line 665) apply to all themes globally.

### Step 5 — Render the new theme in React

**File:** [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx)

The component reads `settings = nxExitIntent.config` and dispatches by `theme`. Add a new branch:

```tsx
if (theme === 'theme-five') {
    return (
        <div className="nx-exit-intent-overlay" onClick={handleOverlayClose}>
            <div
                className="nx-exit-intent-popup nx-exit-intent-theme-five"
                onClick={(e) => e.stopPropagation()}
            >
                {settings.show_close_button && (
                    <button className="nx-exit-intent-close" onClick={handleClose}>×</button>
                )}

                <h2 className="nx-exit-intent-t5-title">{settings.exit_intent_t5_title}</h2>
                <p  className="nx-exit-intent-t5-subtitle">{settings.exit_intent_t5_subtitle}</p>

                <button className="nx-exit-intent-t5-btn" onClick={handlePrimary}>
                    {settings.exit_intent_button_text}
                </button>
            </div>
        </div>
    );
}
```

**Re-use existing handlers** (`handleClose`, the `videoPlaying` state from theme-four, the `useCountdown` hook from theme-two, etc.) — do not duplicate them.

The session-storage dismissal key (`notificationx_exit_intent_{nx_id}`) and the one-shot-per-page-load `triggered` Set are handled in [useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) and need no changes.

### Step 6 — Add the stylesheet

**File:** [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss)

Append a new block after the existing per-theme blocks (theme-four currently ends near line 657+):

```scss
.nx-exit-intent-popup.nx-exit-intent-theme-five {
    // layout, typography, button styling

    .nx-exit-intent-t5-title    { /* … */ }
    .nx-exit-intent-t5-subtitle { /* … */ }
    .nx-exit-intent-t5-btn      { /* … */ }

    @media (max-width: 768px) {
        // mobile overrides
    }
}
```

Do **not** modify the shared `.nx-exit-intent-overlay` or base `.nx-exit-intent-popup` rules unless the change is intentional for all themes.

### Step 7 — Add the preview SVG

Drop a new file at:

```
assets/admin/images/extensions/themes/exit-intent/exit-intent-theme-five.svg
```

Match the dimensions and visual language of the existing four SVGs (open them as reference) so the admin theme picker stays consistent. The path must match the `source` URL set in Step 1.

If the design uses photographic assets, place them under `assets/common/exit-intend-popup/` and reference them from the React component (mirroring how `theme-two.jpg` is used).

### Step 8 — Build the frontend

```bash
cd /Users/shakib/Documents/wordpress/notificationx/wp-content/plugins/notificationx
npm run build      # or `npm run start` for watch mode while developing
```

Compiled output lands in `nxbuild/public/js/frontend.js` and `nxbuild/public/css/frontend.css` (see [exit-intent-popup.md § Compiled Assets](exit-intent-popup.md#compiled-assets)).

---

## 4. Verification Checklist

- [ ] New theme card appears in the admin theme picker with the correct preview SVG.
- [ ] Selecting the theme reveals only the fields you defined (no leakage from other themes).
- [ ] Default values from `init_extension()` populate the form on first load.
- [ ] Saving and previewing the notification renders the new design on the frontend.
- [ ] Design controls (colors / typography) update the rendered popup live.
- [ ] Mobile breakpoint behaves correctly.
- [ ] Close button respects `show_close_button` toggle.
- [ ] `mouseleave` trigger fires once and is suppressed on subsequent loads via `sessionStorage`.
- [ ] Trigger sensitivity (`10` / `20` / `50` px) and `exit_intent_mobile_disable` still work.

---

## 5. What NOT to Touch

- **[includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php)** — type ID is already registered; new themes are added via the extension, not the type.
- **`TypesFactory.php` / `ExtensionFactory.php`** — already wired.
- **`FrontEnd.php` `get_notifications_data()`** — generic serializer; new fields flow through automatically.
- **`useNotificationX.ts` mouseleave handler / `triggered` Set / sessionStorage key** — global to all Exit Intent themes.
- **`NotificationContainer.tsx`** — already routes `config.type === 'exit_intent'` to `<ExitIntentPopup>`.
- **`customize_fields()`** — settings apply to every theme.

---

## 6. Pattern Recap

```
defaults (init_extension)
   └─► content_fields  (Rules::is theme-five)
   └─► design_fields   (Rules::is theme-five, optional)
        ↓
   React branch:  if (theme === 'theme-five') { ... }
        ↓
   SCSS block:    .nx-exit-intent-theme-five { ... }
        ↓
   Preview SVG:   exit-intent-theme-five.svg
        ↓
   npm run build
```

Following this 5-touchpoint pattern (theme registry → fields → React → SCSS → SVG) keeps the new design fully integrated with the admin builder, the live preview, and the frontend trigger/dismiss machinery already documented in [exit-intent-popup.md](exit-intent-popup.md).
