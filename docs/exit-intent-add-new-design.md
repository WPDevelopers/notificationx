# Adding a New Design (Theme) to the Exit Intent Popup

This guide is a **companion to** [exit-intent-popup.md](exit-intent-popup.md), which documents the existing feature (data flow, trigger mechanism, and existing themes `theme-one` … `theme-seven`). Read that first.

This document is a step-by-step **how-to** for adding the next design (e.g. `theme-eight`) — what to touch, in what order, and what to leave alone. The examples below use `theme-eight` placeholders for readability; the same shape applies to any subsequent theme — just bump the slug, prefix (`t8_*`), and PNG filename.

---

## 1. File Map

| # | Concern | File |
|---|---------|------|
| 1 | Theme registry, content fields, design fields, customize fields | [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php) |
| 2 | Notification type registration (no change usually needed) | [includes/Types/ExitIntent.php](../includes/Types/ExitIntent.php) |
| 3 | REST serializer (no change needed — generic) | [includes/FrontEnd/FrontEnd.php](../includes/FrontEnd/FrontEnd.php) |
| 4 | React renderer — add a branch for the new theme | [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) |
| 5 | Frontend stylesheet — add a per-theme block | [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss) |
| 6 | Admin theme picker preview PNG | [assets/admin/images/extensions/themes/exit-intent/](../assets/admin/images/extensions/themes/exit-intent/) |
| 7 | Photo/image assets used by the theme (optional) | Hosted under `https://notificationx.com/wp-content/uploads/.../exit-intend-*.png` and referenced from the `defaults` array; bundled assets in `assets/common/exit-intend-popup/` are also supported |

The factory wiring at [includes/Types/TypesFactory.php](../includes/Types/TypesFactory.php) and [includes/Extensions/ExtensionFactory.php](../includes/Extensions/ExtensionFactory.php) is already in place. **Do not modify** these for a new theme.

---

## 2. Naming Conventions

Match what existing themes do (see [exit-intent-popup.md](exit-intent-popup.md) for full field tables):

- **Theme key:** `theme-eight` (used in the React `theme === '...'` check and in field rules)
- **Theme rules selector:** `Rules::is('themes', 'exit_intent_custom_theme-eight')`
- **Field name prefix:** `exit_intent_` for shared fields, `exit_intent_t8_*` for theme-specific ones (mirroring the existing `t3_*` … `t7_*` pattern)
- **CSS root class:** `.nx-exit-intent-popup.nx-exit-intent-theme-eight`
- **Per-theme content section ID:** `exit_intent_theme_eight_section`
- **Preview filename:** `exit-intent-theme-eight.png` (themes one through seven all ship as `.png` — don't introduce SVGs unless you also update the theme-picker styling)
- **Reuse the shared CTA fields** when your theme has a single primary CTA: `exit_intent_button_text`, `exit_intent_button_url`, `exit_intent_button_new_tab`. The `renderCta` helper in `ExitIntentPopup.tsx` will switch your CTA between `<button>` and `<a href target>` automatically.

---

## 3. Step-by-Step Implementation

### Step 1 — Register the new theme in the extension

**File:** [includes/Extensions/ExitIntent/ExitIntentNotification.php](../includes/Extensions/ExitIntent/ExitIntentNotification.php)
**Method:** `init_extension()` — locate the `$this->themes` array.

Add a new entry. The order of entries in `$this->themes` determines the order of cards in the admin theme picker (it is currently *not* in slug order — see [exit-intent-popup.md](exit-intent-popup.md#exit-intent-popup) for the current ordering).

```php
'theme-eight' => [
    'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-eight.png',
    'defaults' => [
        'exit_intent_t8_title'        => __( 'Don\'t leave yet!', 'notificationx' ),
        'exit_intent_t8_subtitle'     => __( 'Grab 20% off before you go.', 'notificationx' ),
        'exit_intent_button_text'     => __( 'Claim Offer', 'notificationx' ),
        'exit_intent_button_new_tab'  => true,
        'exit_intent_image_url'       => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-eight.png' ],
        'position'                    => 'center',
        // any other field keys your theme exposes
    ],
    'column'   => '5',
],
```

> `source` is the preview image. Defaults populate the form on first load and must use the **same field names** you'll register in Step 2. `'column' => '5'` mirrors every existing theme — change only if you have a layout reason.

> If your theme uses a date/time picker (e.g. for a sale-end timestamp like theme-five's / theme-six's `exit_intent_countdown_end`), use `'type' => 'date'` — that's the same field type the Custom Notification "Time" repeater field uses. The `exit_intent_countdown_end` name is **already shared** between theme-five and theme-six; reuse it again if your theme has a single end date, since only one theme is active at a time.

### Step 2 — Add content fields for the new theme

Same file, `content_fields()`. Each existing theme has its own section gated by a theme rule. Mirror that pattern with a new section, e.g. `exit_intent_theme_eight_section`:

```php
$fields['exit_intent_theme_eight_section'] = [
    'label'    => __( 'Exit Intent Content', 'notificationx' ),
    'name'     => 'exit_intent_theme_eight_section',
    'type'     => 'section',
    'priority' => 5,
    'rules'    => Rules::logicalRule( [
        Rules::is( 'source', $this->id ),
        Rules::is( 'themes', $this->id . '_theme-eight' ),
    ] ),
    'fields'   => [
        [ 'label' => __( 'Headline',  'notificationx' ), 'name' => 'exit_intent_t8_title',    'type' => 'text',   'priority' => 10 ],
        [ 'label' => __( 'Subtitle',  'notificationx' ), 'name' => 'exit_intent_t8_subtitle', 'type' => 'text',   'priority' => 20 ],
        [ 'label' => __( 'Button Text', 'notificationx' ),  'name' => 'exit_intent_button_text',    'type' => 'text',   'priority' => 30 ],
        [ 'label' => __( 'Button URL',  'notificationx' ),  'name' => 'exit_intent_button_url',     'type' => 'text',   'priority' => 31 ],
        [ 'label' => __( 'Open in New Tab', 'notificationx' ), 'name' => 'exit_intent_button_new_tab', 'type' => 'toggle', 'priority' => 32, 'default' => true ],
    ],
];
```

Reuse shared keys (`exit_intent_button_text`, `exit_intent_button_url`, `exit_intent_button_new_tab`, `exit_intent_image_url`, `exit_intent_dismiss_text`) wherever the new theme has the same concept — the React renderer (and `renderCta`) already understands them.

> If your theme has a sub-block of related fields (theme-six does this for its countdown — see `exit_intent_theme_six_timer_section`), register a **second** section with the same theme rule but a different `name` and `priority`. The user sees two grouped headings inside the same theme.

> When marking fields as Pro-only (like theme-one's `exit_intent_show_name` / `exit_intent_show_email`), set `'is_pro' => true` on the **field definition**. The frontend reads `is_pro` from `NotificationProvider` context and force-disables those toggles on the free build before reading their values, so don't rely on saved values alone.

### Step 3 — Add design fields (optional)

Same file, `design_fields()`. The Advanced Design panel is **flat** — there is no per-theme sub-section. Each theme contributes a list of fields via a private helper (`theme_one_design_fields()`, `theme_two_design_fields()`, … `theme_seven_design_fields()`), and `design_fields()` merges them into the global `advance_design_section.fields` via a `$merge` closure with the theme rule attached to every field automatically:

```php
private function theme_eight_design_fields() {
    return [
        // Container
        [ 'label' => __( 'Popup Max Width', 'notificationx' ), 'name' => 'exit_intent_t8_max_width',     'type' => 'number',      'default' => 540, 'description' => 'px' ],
        [ 'label' => __( 'Background Color', 'notificationx' ),'name' => 'exit_intent_t8_bg_color',      'type' => 'colorpicker', 'default' => '#ffffff' ],
        [ 'label' => __( 'Overlay Color', 'notificationx' ),   'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
        [ 'label' => __( 'Close Color', 'notificationx' ),     'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#666666' ],
        [ 'label' => __( 'Close Size', 'notificationx' ),      'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],
        // Add typography / button / theme-specific elements as needed.
    ];
}
```

Then register it inside `design_fields()` next to the existing `$merge` calls:

```php
$merge( 'theme-eight', $this->theme_eight_design_fields() );
```

The `$merge` closure handles three things automatically:

1. **Theme rule** — wraps every field with `Rules::logicalRule( [ source==exit_intent_custom, advance_edit==true, themes==<source>_theme-eight ] )`, so the controls only appear when that theme is selected and Advanced Edit is on.
2. **Priority** — auto-assigns an incrementing priority starting at `20`. Custom CSS sits at priority `150`, so theme controls naturally render above it. Don't set your own `priority` ≥ 150 unless you intentionally want a field below Custom CSS.
3. **Field name uniqueness** — each field is keyed by its `name` in the merged array; collisions overwrite, so reuse names only when you want true cross-theme sharing (e.g. `exit_intent_overlay_color`, `exit_intent_close_color`, `exit_intent_close_size`).

**Conditionally-shown design fields.** When a theme has an optional sub-system (theme-five's & theme-six's countdown timer), attach a per-field `'rules' => Rules::is( 'exit_intent_t8_show_timer', true )` to each conditional design field. The `$merge` closure preserves field-level `rules` and ANDs them with the theme rule.

Use existing shared keys where possible. Pseudo-state styling (`:hover`, `:focus`, `::placeholder`) is **not supported** — inline styles can't apply pseudo-selectors, so registering a `*_hover_bg` / `*_focus_color` field would be dead. If you need pseudo-state styling, render a scoped `<style>` block from React. Full list of existing fields per theme is in [exit-intent-popup.md § Advanced Design](exit-intent-popup.md#advanced-design-design-tab).

### Step 4 — Customize tab settings

**No change needed.** The customize tab settings (`show_close_button`, `exit_intent_sensitivity`, `exit_intent_cookie_days`, `exit_intent_mobile_disable`) defined in `customize_fields()` (around line 665) apply to all themes globally.

### Step 5 — Render the new theme in React

**File:** [nxdev/notificationx/frontend/core/ExitIntentPopup.tsx](../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx)

The component reads `settings = nxExitIntent.config` and dispatches by `theme`. Each theme branch builds inline `React.CSSProperties` objects gated by the shared `adv = !!settings.advance_edit` flag and attaches them to the matching elements. Add a new branch following that pattern:

```tsx
if (theme === 'theme-eight') {
    const popupStyle: React.CSSProperties = adv ? {
        background:   s.exit_intent_t8_bg_color || undefined,
        borderRadius: px(s.exit_intent_t8_border_radius),
        maxWidth:     px(s.exit_intent_t8_max_width),
    } : {};
    const overlayStyle: React.CSSProperties = adv
        ? { background: s.exit_intent_overlay_color || 'rgba(0,0,0,0.5)' } : {};
    const btnStyle: React.CSSProperties = adv ? { /* … */ } : {};
    // titleStyle, etc. — follow the pattern in the other branches

    return (
        <div className="nx-exit-intent-overlay" style={overlayStyle} onClick={(e) => { if (e.target === e.currentTarget) handleClose(); }}>
            <div
                className={`nx-exit-intent-popup nx-exit-intent-theme-eight nx-exit-intent-${settings?.nx_id}`}
                style={popupStyle}
            >
                {showClose && (
                    <button className="nx-exit-intent-close" style={closeStyle} onClick={handleClose} aria-label="Close">×</button>
                )}

                <h2 className="nx-exit-intent-t8-title">{settings.exit_intent_t8_title}</h2>
                {/* …more theme-specific markup… */}

                {renderCta('nx-exit-intent-t8-btn', btnStyle, settings.exit_intent_button_text || 'Get Offer')}
            </div>
        </div>
    );
}
```

**Re-use existing handlers and helpers** — do not duplicate them:

- `handleClose` — sessionStorage flag + cookie + Redux `REMOVE_NOTIFICATION` dispatch.
- `closeStyle` — shared inline style for the `×` button (driven by `exit_intent_close_color` / `exit_intent_close_size`).
- `renderCta(className, style, label)` — wires `exit_intent_button_url` + `exit_intent_button_new_tab` into either `<a target>` or `<button onClick={handleClose}>`. Use it for every primary CTA unless you genuinely need form submission (theme-one, theme-seven).
- `px(n)` — `number → "{n}px"` helper.
- `s` — `settings || {}` alias.
- `useCountdown(endDateStr, fallbackDurationMs?)` — reused by theme-five & theme-six. To opt your theme into the timer, add it to the `(theme === 'theme-five' || theme === 'theme-six')` check that sets `fallbackDuration` (so the timer ticks even without an end date).
- `videoPlaying` state — theme-four only; safe to ignore unless you're embedding video.
- `submitting` / `submitted` state — reused by theme-one and theme-seven for `popup-submit` form flows.

The session-storage dismissal key (`notificationx_exit_intent_{nx_id}`) and the one-shot-per-page-load `triggered` Set are handled in [useNotificationX.ts](../nxdev/notificationx/frontend/core/useNotificationX.ts) and need no changes.

### Step 6 — Add the stylesheet

**File:** [nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss](../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss)

Append a new block after the existing per-theme blocks (theme-one … theme-seven are all defined there already — append after theme-seven's block):

```scss
.nx-exit-intent-popup.nx-exit-intent-theme-eight {
    // layout, typography, button styling

    .nx-exit-intent-t8-title    { /* … */ }
    .nx-exit-intent-t8-subtitle { /* … */ }
    .nx-exit-intent-t8-btn      { /* … */ }

    @media (max-width: 768px) {
        // mobile overrides
    }
}
```

Do **not** modify the shared `.nx-exit-intent-overlay` or base `.nx-exit-intent-popup` rules unless the change is intentional for all themes.

### Step 7 — Add the preview image

Drop a new file at:

```
assets/admin/images/extensions/themes/exit-intent/exit-intent-theme-eight.png
```

Match the dimensions and visual language of the existing PNGs (open them as reference) so the admin theme picker stays consistent. The path must match the `source` URL set in Step 1.

If the design uses photographic assets, either:

- host them on `notificationx.com` and reference the URL from the theme's `defaults` (the current convention used by theme-two/four/five/six/seven, e.g. `https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-eight.png`), **or**
- place them under `assets/common/exit-intend-popup/` and reference the local URL.

### Step 8 — Build the frontend

```bash
cd /Users/shakib/Documents/wordpress/notificationx/wp-content/plugins/notificationx
npm run build      # or `npm run start` for watch mode while developing
```

Compiled output lands in `nxbuild/public/js/frontend.js` and `nxbuild/public/css/frontend.css` (see [exit-intent-popup.md § Compiled Assets](exit-intent-popup.md#compiled-assets)).

---

## 4. Verification Checklist

- [ ] New theme card appears in the admin theme picker with the correct preview PNG.
- [ ] Selecting the theme reveals only the fields you defined (no leakage from other themes).
- [ ] Default values from `init_extension()` populate the form on first load.
- [ ] Saving and previewing the notification renders the new design on the frontend.
- [ ] Design controls (colors / typography) update the rendered popup live.
- [ ] Mobile breakpoint behaves correctly.
- [ ] Close button respects `show_close_button` toggle.
- [ ] `mouseleave` trigger fires once and is suppressed on subsequent loads via `sessionStorage`.
- [ ] Trigger sensitivity (`10` / `20` / `50` px) and `exit_intent_mobile_disable` still work.
- [ ] `exit_intent_button_url` + `exit_intent_button_new_tab` swap the CTA between `<button>` and `<a href target>` correctly.
- [ ] If your theme is Pro-gated for any field, the gated field is hidden / forced off on a free build (the React `is_pro` context flag is `false`).

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
   └─► content_fields    (own section, Rules::is theme-eight; reuse shared keys when applicable)
   └─► design_fields     ($merge('theme-eight', $this->theme_eight_design_fields()) — flat, theme rule auto-applied)
        ↓
   React branch:  if (theme === 'theme-eight') { ... renderCta('nx-exit-intent-t8-btn', btnStyle, label) }
        ↓
   SCSS block:    .nx-exit-intent-theme-eight { ... }
        ↓
   Preview PNG:   exit-intent-theme-eight.png
        ↓
   npm run build
```

Following this 5-touchpoint pattern (theme registry → fields → React → SCSS → SVG) keeps the new design fully integrated with the admin builder, the live preview, and the frontend trigger/dismiss machinery already documented in [exit-intent-popup.md](exit-intent-popup.md).
