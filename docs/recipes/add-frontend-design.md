# Recipe: Add a Frontend Design / Theme

Add a new visual design (theme) to an existing Type. A "design" is the layout + styling of the popup — e.g. for Sales notifications you have Theme 1 through Theme 7.

> The detailed step-by-step for the Exit Intent Type lives in [../exit-intent-add-new-design.md](../exit-intent-add-new-design.md). This recipe generalizes the pattern.

## What gets touched

A new design spans **four** layers. Miss one and the design breaks silently.

| Layer | File | What goes here |
|---|---|---|
| 1. PHP theme registry | The Type class in [includes/Types/](../../includes/Types/) | Theme metadata (id, name, preview image) |
| 2. PHP template (if server-rendered) | Type class `templates` array | Tag placeholders, HTML structure for fallback render |
| 3. Frontend React renderer | `nxdev/notificationx/frontend/themes/` (or similar — verify path per Type) | The actual popup UI |
| 4. CSS | Scoped CSS module or `nxdev/notificationx/frontend/.../themes.scss` | Styles for the new theme |

## Steps

### 1. Pick a theme ID

Convention: `{type_id}_theme-{name}` where `name` is short and kebab-case. Examples: `woocommerce_sales_theme-one`, `exit_intent_theme-discount`.

This ID lives in the `theme` column of `wp_nx_posts`.

### 2. Register the theme in the Type

In the Type class (e.g. `includes/Types/Conversions.php`), append to `$themes`:

```php
$this->themes['woocommerce_sales_theme-eight'] = array(
    'id'      => 'woocommerce_sales_theme-eight',
    'label'   => __( 'Theme 8 — Compact', 'notificationx' ),
    'image'   => NOTIFICATIONX_ASSETS . 'admin/img/themes/sales-theme-8.png',
    'priority' => 80,
);
```

The preview image (1) appears in the builder's design picker — drop it under `assets/admin/img/themes/`. Use a real screenshot, not a placeholder.

### 3. (Optional) Add a server-rendered template

If your Type renders server-side (rare — most use the React runtime), add an HTML template string in the Type's `$templates`. For React-rendered Types, skip this step.

### 4. Add the React component

Locate the frontend renderer for your Type. The pattern (verify in your case):

```
nxdev/notificationx/frontend/themes/<type>/ThemeEight.tsx
```

Component skeleton:

```tsx
import React from 'react';
import { NotificationProps } from '../../types';

export default function ThemeEight({ data, settings }: NotificationProps) {
    return (
        <div className="nx-theme-eight">
            <img src={data.image} alt="" className="nx-theme-eight__avatar" />
            <div className="nx-theme-eight__body">
                <p className="nx-theme-eight__title">{data.tag_name}</p>
                <p className="nx-theme-eight__meta">
                    {data.tag_product_title} · {data.tag_time}
                </p>
            </div>
        </div>
    );
}
```

Register it in the Type's theme switch (often a `switch` statement or a theme map in `themes/index.ts`):

```tsx
import ThemeEight from './ThemeEight';

const themes = {
    'woocommerce_sales_theme-one':   ThemeOne,
    // …
    'woocommerce_sales_theme-eight': ThemeEight,  // ← add
};
```

### 5. Add the styles

In the theme's SCSS file (or matching CSS-in-JS file — verify per Type):

```scss
.nx-theme-eight {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: var(--nx-bg, #fff);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

    &__avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 12px;
    }

    &__title { font-weight: 600; }
    &__meta  { font-size: 12px; opacity: 0.7; }
}
```

Use design tokens / CSS vars where the builder exposes them (colors, fonts) — they let users customize without code.

### 6. Build and test

```sh
npm run frontend     # production build
# or
npm run frontend-watch    # rebuild on save
```

Then in WP admin, create a notification of this Type and pick the new theme. Verify:
- Preview image renders in the design picker.
- Builder preview shows the theme.
- Frontend popup renders correctly.
- Customizer controls (color, font size, etc.) actually affect the new theme.

### 7. Handle responsive variants

If your Type has `$res_themes` (mobile-specific themes), register a mobile variant alongside. The frontend will switch based on viewport.

## Anti-patterns

- ❌ Adding a theme in PHP but not registering the React component. The popup renders blank.
- ❌ Adding a React component but forgetting to register the theme metadata in PHP. The theme doesn't appear in the design picker.
- ❌ Hard-coding colors in CSS. Use the existing CSS variables so user customizations work.
- ❌ Reusing another theme's CSS class names. Themes must be isolated; collisions break specificity in subtle ways.
- ❌ Skipping the preview image. Users won't pick a blank thumbnail.

## Verifying nothing desynced

After adding a theme:

```sh
grep -rn "theme-eight" includes/ nxdev/
```

You should see entries from **both** `includes/Types/<Name>.php` and `nxdev/notificationx/frontend/themes/`. If only one side has it, you've half-implemented the theme.
