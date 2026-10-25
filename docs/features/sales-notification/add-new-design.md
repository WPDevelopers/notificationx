# Adding a New Design (Theme) to the Sales Notification

This guide is a step-by-step **how-to** for adding the next Sales Notification design (a new "theme") — what to touch, in what order, and what to leave alone. It documents how every existing Sales theme (`theme-one` … `conv-theme-eleven`) was wired across PHP, React, and SCSS, so you can replicate the same pattern for a new one.

> **Naming note:** internally, "Sales Notification" is the **Conversions** type (type id `conversions`). It is shared by two source families:
> - **Conversions.php** — EDD, Freemius, Envato, Custom Notification, Zapier, BitIntegration sources.
> - **WooCommerceSales.php** — the WooCommerce source.
>
> Both classes declare their **own copy** of the `$themes` / `$res_themes` / `$templates` arrays, and they are kept **in lockstep**. A theme that should appear for both WooCommerce *and* the other sources must be added to **both files** with identical keys. This is the single most common mistake — see [§5](#5-what-not-to-touch).

The examples below use a `conv-theme-twelve` placeholder for readability; bump the slug, the preview PNG number, and the SCSS partial name for whatever the real next theme is.

---

## 1. File Map

| # | Concern | File |
|---|---------|------|
| 1 | Theme registry (EDD/Freemius/etc. sources) — add to `$themes`, `$res_themes`, `$templates` | [includes/Types/Conversions.php](../../../includes/Types/Conversions.php) |
| 2 | Theme registry (WooCommerce source) — **mirror the same entries** | [includes/Types/WooCommerceSales.php](../../../includes/Types/WooCommerceSales.php) |
| 3 | Frontend content layout — add a `case` returning the row template for the new theme | [nxdev/notificationx/frontend/themes/GetTemplate.ts](../../../nxdev/notificationx/frontend/themes/GetTemplate.ts) |
| 4 | Frontend container — add to `splitThemes` **only if** it is a split layout (image fills one side) | [nxdev/notificationx/frontend/core/Notification.tsx](../../../nxdev/notificationx/frontend/core/Notification.tsx) |
| 5 | Per-theme stylesheet — new SCSS partial | [nxdev/notificationx/frontend/scss/_themes/_theme-twelve.scss](../../../nxdev/notificationx/frontend/scss/_themes/) (new file) |
| 6 | SCSS import — register the new partial | [nxdev/notificationx/frontend/scss/_themes/_common.scss](../../../nxdev/notificationx/frontend/scss/_themes/_common.scss) (line ~261) |
| 7 | Admin theme-picker preview image | [assets/admin/images/extensions/themes/](../../../assets/admin/images/extensions/themes/) |
| 8 | Responsive theme preview (if you add a `res-theme-*`) | [assets/admin/images/extensions/themes/res_conv/](../../../assets/admin/images/extensions/themes/res_conv/) |

**Do not modify** for a new theme:
- [includes/Core/PostType.php](../../../includes/Core/PostType.php) — `get_theme_preview_image()` (line 493) already resolves the preview image generically from `get_themes()`.
- [includes/Types/Types.php](../../../includes/Types/Types.php) — `get_themes()` / `get_res_themes()` / `get_templates()` are generic accessors (lines 86–109).
- The theme-picker UI / `TypesFactory` / `ExtensionFactory` — the picker is auto-generated from the registry arrays.
- [nxdev/notificationx/frontend/themes/helpers/Content.tsx](../../../nxdev/notificationx/frontend/themes/helpers/Content.tsx) — the generic 3-row renderer (see [§3](#3-how-rendering-actually-works)).

> **Verified Cards (`vc-*`) are different.** They render through their own React card ([themes/vc/VerifiedCard.tsx](../../../nxdev/notificationx/frontend/themes/vc/VerifiedCard.tsx)), not through `Content.tsx`, and their `GetTemplate` cases return *slots*, not rows. See [§8](#8-verified-cards-vc-).

---

## 2. Naming Conventions

These conventions tie the four layers (PHP registry → content template → SCSS → preview image) together. Get them right and the theme "just works."

- **Theme key (slug):** `conv-theme-twelve`. This is the array key in `$themes`. Free base themes use `theme-one` … `theme-five`; the numbered "conv" family uses `conv-theme-six` … `conv-theme-eleven`. Pick the next in sequence.
- **Fully-qualified theme name (stored in DB / used in CSS):** `<source>_<slug>`, e.g. `conversions_conv-theme-twelve` and `woocommerce_sales_conv-theme-twelve`. The stored value lives in the post's `themes` field.
- **Stripped theme name (used in `GetTemplate.ts` and JS switches):** `conv-theme-twelve` — the source/type prefix is stripped by `getThemeName()` ([nxdev/notificationx/core/functions.ts:203](../../../nxdev/notificationx/core/functions.ts#L203)).
- **CSS container classes:** the frontend container gets *both* `themes-<stripped>` and `themes-<fully-qualified>` ([Notification.tsx:172-173](../../../nxdev/notificationx/frontend/core/Notification.tsx#L172)). So your SCSS targets the fully-qualified forms for both sources:
  ```scss
  &.themes-woocommerce_sales_conv-theme-twelve,
  &.themes-conversions_conv-theme-twelve { … }
  ```
- **Preview image:** `assets/admin/images/extensions/themes/nx-conv-theme-N.(jpg|png)` for free themes, `…/themes/pro/nx-conv-theme-N.png` for Pro themes, `…/themes/res_conv/nx-conv-res-theme-N.png` for responsive previews. Match the dimensions/visual language of the existing files.
- **Pro gating:** add `'is_pro' => true` to the registry entry. (Most `conv-theme-*` numbered themes are Pro; the base `theme-one/two/three/five` are free.)
- **Image shape:** `'image_shape' => 'square' | 'circle' | 'rounded'` sets the default avatar shape for the theme.

---

## 3. How Rendering Actually Works

Unlike Exit Intent (where each theme has bespoke JSX), Sales themes share a **generic renderer** and differ only in (a) which content rows are produced and (b) CSS. Understanding this saves you from writing React for a new theme in most cases.

1. **Container + classes** — [Notification.tsx](../../../nxdev/notificationx/frontend/core/Notification.tsx) builds the `.notification-item` container and attaches `themes-<stripped>` + `themes-<fully-qualified>` classes (lines 162–173).
2. **Content rows** — [GetTemplate.ts](../../../nxdev/notificationx/frontend/themes/GetTemplate.ts) `switch (themeName)` returns an **array of up to 3 strings** (rows), each built from placeholders like `${params?.first_param}`. Example (the standard 3-row sales layout, lines 200–212):
   ```ts
   case "theme-one":
   case "theme-two":
   case "theme-three":
   case "conv-theme-ten":
   case "conv-theme-eleven":
       return [
           `${params?.first_param} ${params?.second_param}`,  // "John D. just purchased"
           `${params?.third_param}`,                          // "NotificationX Pro"
           `${params?.fourth_param}`,                          // "1 minute ago"
       ];
   ```
   The "people count" themes (`conv-theme-seven/eight/nine`) return a 2-row layout instead (lines 222–228).
3. **Rows → DOM** — [helpers/Content.tsx](../../../nxdev/notificationx/frontend/themes/helpers/Content.tsx) maps that array to `<p class="nx-first-row">`, `nx-second-row`, `nx-third-row` (rowClasses, line 8). **Your SCSS styles those three row classes** — that's the whole layout surface.
4. **Image** — rendered by the generic `Image` helper; its shape comes from `image_shape` in the registry.
5. **Split layouts** — if your theme's image fills one whole side (like `conv-theme-nine` / `theme-five`), add the stripped slug to the `splitThemes` array in [Notification.tsx:187-194](../../../nxdev/notificationx/frontend/core/Notification.tsx#L187). Split themes opt out of the global background-color advance-edit override so the image side isn't recolored.

**Bottom line:** a new theme that reuses the standard 3-row or 2-row content shape needs **no new React** — you just add its slug to the matching `case` in `GetTemplate.ts` and write SCSS.

---

## 4. Step-by-Step Implementation

### Step 1 — Register the theme in **Conversions.php**

**File:** [includes/Types/Conversions.php](../../../includes/Types/Conversions.php) → `init()` → the `$this->themes` array (lines 73–149).

Add an entry. Order in the array = order of cards in the admin picker.

```php
'conv-theme-twelve' => array(
    'is_pro'      => true, // omit for a free theme
    'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-12.png',
    'image_shape' => 'rounded',          // 'square' | 'circle' | 'rounded'
    'template'    => $common_fields,      // the shared name/product/time field set (line 64)
    'defaults'    => [                    // optional — pre-fill fields on first load
        'link_button'      => true,
        'link_button_text' => __( 'Buy Now', 'notificationx' ),
    ],
),
```

- `source` is the **preview** image shown in the picker (Step 7).
- `template => $common_fields` reuses the standard `first_param` (name) / `second_param` ("just purchased") / `third_param` (product) / `fourth_param` (time) mapping defined at line 64. Reuse it unless your theme genuinely needs different content params.
- `defaults` seed the campaign form when the theme is first selected (`conv-theme-ten` / `conv-theme-eleven` use this to turn on the "Buy Now" button — lines 113–132).

**If the theme should expose specific content-tab params**, add its fully-qualified names to the right `_themes` list inside `$this->templates` (lines 207–252):
- `woo_template_new` (lines 208–232) — the standard name / product-title / definite-time layout. Add both `conversions_conv-theme-twelve` and `woocommerce_sales_conv-theme-twelve` here for a standard sales theme.
- `woo_template_sales_count` (lines 233–251) — the "X people purchased in last N days" layout (used by `conv-theme-six/seven/eight/nine`). Use this list instead if your theme is a count style.

> If your theme is a **count** style, also add its fully-qualified names to the `$conversions_count` property (line 42) so the count param logic in [GetTemplate.ts:65](../../../nxdev/notificationx/frontend/themes/GetTemplate.ts#L65) treats it correctly.

### Step 2 — Mirror it in **WooCommerceSales.php**

**File:** [includes/Types/WooCommerceSales.php](../../../includes/Types/WooCommerceSales.php) → `init()`.

Add the **same** `'conv-theme-twelve' => [...]` entry (lines 70–145 mirror Conversions exactly), and add `woocommerce_sales_conv-theme-twelve` to the matching `_themes` list. If you skip this, the theme will be missing for WooCommerce-sourced campaigns.

> Tip: diff the two files' `init()` after editing — the `$themes` arrays should be identical except where a theme is intentionally source-specific (e.g. `maps_theme`).

### Step 3 — Add the content layout in **GetTemplate.ts**

**File:** [nxdev/notificationx/frontend/themes/GetTemplate.ts](../../../nxdev/notificationx/frontend/themes/GetTemplate.ts)

Find the main `switch (themeName)` for conversions (line 200). Add your slug to the `case` group whose row shape matches:

```ts
// Standard 3-row sales layout — just add the case label:
case "conv-theme-ten":
case "conv-theme-eleven":
case "conv-theme-twelve":   // ← add here
    return [
        `${params?.first_param} ${params?.second_param}`,
        `${params?.third_param}`,
        `${params?.fourth_param}`,
    ];
```

Only write a **new** `return [...]` block if the row composition is genuinely different from the existing groups.

> There is a second `switch` higher up (line 165) guarded by `settings.source === 'freemius_conversions'`. Add a `case` there **only** if the theme must behave differently for the Freemius source (extra version/plan params). Most themes don't need this.

### Step 4 — Mark it split (only if needed)

**File:** [nxdev/notificationx/frontend/core/Notification.tsx](../../../nxdev/notificationx/frontend/core/Notification.tsx) (line 187).

If the image occupies a full side of the card:

```ts
let splitThemes = [
    "theme-five",
    "theme-six-free",
    "conv-theme-nine",
    "conv-theme-twelve",   // ← add if split layout
    "review-comment",
    "page_analytics_pa-theme-two",
];
```

Skip this for standard inline-avatar themes.

### Step 5 — Create the per-theme SCSS partial

**New file:** `nxdev/notificationx/frontend/scss/_themes/_theme-twelve.scss`

Use the existing partials as templates ([_theme-ten.scss](../../../nxdev/notificationx/frontend/scss/_themes/_theme-ten.scss) is a clean reference). Target **both** fully-qualified class names, and style the three generic row classes:

```scss
&.themes-woocommerce_sales_conv-theme-twelve,
&.themes-conversions_conv-theme-twelve {
    &, .notificationx-inner {
        border-radius: 8px;
    }
    .no-advance-edit .notificationx-content {
        .nx-first-row  { /* name + action line */ }
        .nx-second-row { /* product line */ }
        .nx-third-row  { /* time / branding */ }
    }
}
```

> The leading `&` is required — these partials are `@import`ed **inside** the `.notification-item` selector in `_common.scss`, so `&.themes-…` resolves to `.notification-item.themes-…`. Do not write a top-level `.notification-item { … }` wrapper inside the partial.

> **Branding byline → brand logo.** The new Sales themes render the full NotificationX brand logo (icon + wordmark) in the byline instead of the plain "NotificationX" text. This is automatic **if** you add the theme slug to the `BRAND_LOGO_THEMES` array in [NXBranding.js](../../../nxdev/notificationx/frontend/themes/helpers/NXBranding.js) — the component then renders `<BrandLogo />` (from [BrandLogo.js](../../../nxdev/notificationx/frontend/themes/helpers/BrandLogo.js), the inline `full-logo.svg`) in place of `<NotificationText />`. In the partial, size it via the branding link's `svg`: `.nx-branding > a > svg { height: 16px; width: auto; }` (height-based so the 180:48 logo keeps its aspect ratio — do **not** set the old `width:70px; height:9px` wordmark dimensions, which squash it).

### Step 6 — Register the partial import

**File:** [nxdev/notificationx/frontend/scss/_themes/_common.scss](../../../nxdev/notificationx/frontend/scss/_themes/_common.scss) (around line 261).

```scss
    @import "./theme-two";
    @import "./theme-three";
    @import "./theme-six";
    @import "./theme-seven";
    @import "./theme-eight";
    @import "./theme-ten";
    @import "./theme-eleven";
    @import "./theme-twelve";   // ← add
```

> Note: these are imported from `_common.scss`, **not** from `theme.scss`. `theme.scss` only imports `_common` and a few siblings — adding your import to `theme.scss` directly will put it outside the `.notification-item` scope and the `&.` selectors won't compile correctly.

### Step 7 — Add the preview image(s)

- Free theme: `assets/admin/images/extensions/themes/nx-conv-theme-N.(jpg|png)`
- Pro theme: `assets/admin/images/extensions/themes/pro/nx-conv-theme-N.png`
- Responsive (if you registered a `res-theme-*`): `assets/admin/images/extensions/themes/res_conv/nx-conv-res-theme-N.png`

The filename must exactly match the `source` URL set in Steps 1–2. Match the dimensions of the existing previews so the picker grid stays aligned.

### Step 8 — (Optional) Register a responsive variant

If the theme needs a distinct mobile layout, add a `res-theme-*` entry to `$this->res_themes` in **both** PHP files (Conversions.php lines 150–206), pointing `_template` at the matching template key (`woo_template_new` / `woo_template_sales_count`). The responsive name is resolved by `getResThemeName()` ([functions.ts:37](../../../nxdev/notificationx/frontend/core/functions.ts#L37)) and rendered through the same generic pipeline.

### Step 9 — Build

```bash
cd /Users/shakib/Documents/wordpress/notificationx/wp-content/plugins/notificationx
npm run start      # watch admin + frontend while developing
# or
npm run build      # production admin + frontend bundles
```

Frontend output lands under `nxbuild/` (the `NOTIFICATIONX_DEV_ASSETS` path).

---

## 5. What NOT to Touch

- **`PostType.php` `get_theme_preview_image()`** — resolves previews from the registry generically; new themes flow through automatically.
- **`Types.php` accessors** (`get_themes`, `get_res_themes`, `get_templates`) — generic.
- **`Content.tsx`** — the 3-row renderer is shared by every theme; changing it affects all of them.
- **`TypesFactory.php` / `ExtensionFactory.php`** — the Conversions type and its sources are already wired.
- **The theme-picker UI component** — auto-generated from `$themes` / `$res_themes`.
- **Forgetting the second registry file** — the #1 bug. A theme added only to `Conversions.php` is invisible for WooCommerce campaigns (and vice versa).

---

## 6. Pattern Recap

```
Conversions.php  $themes[ 'conv-theme-twelve' ]  ─┐
WooCommerceSales.php  (identical entry)           ─┴─►  registry (preview + image_shape + defaults + _themes)
        ↓
GetTemplate.ts:  case "conv-theme-twelve": return [ row1, row2, row3 ]   (content layout)
        ↓  (rows → .nx-first-row / .nx-second-row / .nx-third-row via Content.tsx)
Notification.tsx:  splitThemes += "conv-theme-twelve"   (ONLY if split layout)
        ↓
_themes/_theme-twelve.scss:  &.themes-conversions_conv-theme-twelve, &.themes-woocommerce_sales_conv-theme-twelve { … }
_themes/_common.scss:  @import "./theme-twelve";
        ↓
assets/admin/images/extensions/themes/(pro|res_conv)/nx-conv-theme-12.png
        ↓
npm run build
```

Two PHP registries (kept identical) → one content `case` → one SCSS partial (+ its import) → one preview image. That's the full footprint of a new Sales Notification theme.

---

## 7. Verification Checklist

- [ ] New theme card appears in the picker **for both** a WooCommerce campaign and an EDD/Freemius campaign.
- [ ] Preview PNG renders (path matches `source` in both PHP files).
- [ ] Selecting the theme shows the correct content-tab params (driven by the `_themes` list it was added to).
- [ ] `defaults` populate on first selection (e.g. the Buy-Now button, if set).
- [ ] Saving + previewing renders the design on the frontend with the right rows.
- [ ] SCSS targets both `themes-conversions_…` and `themes-woocommerce_sales_…` (test under both sources).
- [ ] If split: image side is not recolored by the advanced-edit background color.
- [ ] If Pro-gated: the card shows the Pro lock on a free build.
- [ ] Responsive variant (if added) renders on mobile widths.

---

## 8. Verified Cards (`vc-*`)

The Verified Cards ("vc", issue 84707) are a theme family that does **not** use the generic renderer from [§3](#3-how-rendering-actually-works). Each vc theme has its own React layout, a slim two-line phone card, and the credit "✓ Verified by [bell] NotificationX". The first batch has five themes: **Receipt** and **Showcase** (Sales, Part 1), **Thread** (Comments), **Scorecard** (Reviews) and **Counter** (Download Stats) (Part 2). A vc key whose slug is not whitelisted (for example a PHP entry shipped before its JS) takes the generic path.

### Keys and slots

| Theme | Full keys | Slots returned by `GetTemplate` |
|---|---|---|
| Receipt | `conversions_vc-receipt`, `woocommerce_sales_vc-receipt` | `[0]` `first_param` (name) · `[1]` `second_param` (action) · `[2]` `third_param` (product) · `[3]` `fourth_param` (time) · `[4]` Freemius plan and price: `` `${freemius_fifth_param} ${freemius_seventh_param}` `` when `source === 'freemius_conversions'`, otherwise `'<span></span>'` |
| Showcase | `conversions_vc-showcase`, `woocommerce_sales_vc-showcase` | `[0]` `second_param` (action chip, default "Just purchased") · `[1]` `third_param` (product) · `[2]` `first_param` (name) · `[3]` `'<span>{{city_country}}</span>'` · `[4]` `fourth_param` (time) |
| Thread | `comments_vc-thread` | `[0]` `first_param` (name) · `[1]` `second_param` ("commented on") · `[2]` `third_param` (comment; "Post Title" makes the block a 600-weight title) · `[3]` `fourth_param` (time) |
| Scorecard | `reviews_vc-scorecard` (wp_reviews, Freemius, Zapier, Bit Integrations), `woo_reviews_vc-scorecard`, `woocommerce_sales_reviews_vc-scorecard`, `reviewx_vc-scorecard`, `google_reviews_vc-scorecard` | `[0]` `first_param` (reviewer, `tag_username`) · `[1]` `fourth_param` (`tag_rating`, `rating::N` once filled) · `[2]` `sixth_param` (review title) only for `wp_reviews`, `zapier_reviews`, `bitintegrations_reviews` and `reviewx`, otherwise `'<span></span>'` · `[3]` `fifth_param` (review text) · `[4]` `second_param` ("on") · `[5]` `third_param` (plugin, product or place) · `[6]` `'<span>{{time}}</span>'` |
| Counter | `download_stats_vc-counter` | `[0]` `first_param` (plugin or theme name) · `[1]` `second_param` ("has been downloaded") · `[2]` `third_param` (count, e.g. "2.1K times in last 7 days") · `[3]` `fourth_param` (call to action). No time. |

**Scorecard details.** The stars and the time show together: the rating comes from `fourth_param` (`tag_rating`) and the time from a fixed `{{time}}` slot (`updated_at` is always sent), so no template field change was needed. The stars are [VcStars.tsx](../../../nxdev/notificationx/frontend/themes/vc/VcStars.tsx) (currentColor SVGs, label "%s out of 5"), not `Star.tsx`, because `Content.tsx` strips HTML from rating rows. Each review source has its own Scorecard group (`vc_scorecard_template`; Google: `google_reviews_vc_scorecard_template`) that offers only the tags the card shows, so `tag_rated` (which switches WooCommerce and Google reviews to aggregate mode) and `tag_time` are never offered. ReviewX uses the new option key `tag_product_post_title` ("Product Title"), which resolves to the product (`post_title`); its review title is the ReviewX headline. The image keeps each source's own default; avatars are circles and a Featured Image (class `featured_image` from `image_data.classes`) is an 8px rounded square. When a review has no headline, `Reviews::conversion_data` copies the product into `title`; the card then hides the title instead of showing the product twice.

**The slot contract.** A vc case in [GetTemplate.ts](../../../nxdev/notificationx/frontend/themes/GetTemplate.ts) returns a fixed-length array whose indexes the card reads by position. Every slot must be a **non-empty string**: [Theme.tsx](../../../nxdev/notificationx/frontend/themes/Theme.tsx) drops falsy rows, which would shift the indexes. Each param is already wrapped in a `<span>`, so an unset param is `'<span></span>'`; the card hides a part whose slot has no text once the tags are stripped (for example the Showcase pin and its `·` when there is no city and country, as on EDD, Envato and Freemius). The vc cases sit in the stripped-slug `switch`, so `freemius_conversions` falls through the Freemius block to them. Unit tests lock the arrays ([tests/js/frontend/vc-get-template.test.js](../../../tests/js/frontend/vc-get-template.test.js)).

### How a vc card renders

1. **PHP registry** — each vc entry is appended at the **end** of its `$this->themes`, so every existing picker card keeps its position: Receipt and Showcase in [Conversions.php](../../../includes/Types/Conversions.php) and [WooCommerceSales.php](../../../includes/Types/WooCommerceSales.php), Thread in [Comments.php](../../../includes/Types/Comments.php), Scorecard in [Reviews.php](../../../includes/Types/Reviews.php), [WOOReviews.php](../../../includes/Extensions/WooCommerce/WOOReviews.php), [WooCommerceSalesReviews.php](../../../includes/Extensions/WooCommerce/WooCommerceSalesReviews.php), [ReviewX.php](../../../includes/Extensions/ReviewX/ReviewX.php) and [GoogleReviews.php](../../../includes/Extensions/Google/GoogleReviews.php), and Counter in [DownloadStats.php](../../../includes/Types/DownloadStats.php). The Sales keys are appended to `woo_template_new['_themes']` (not to `$conversions_count` or `woo_template_sales_count`), Thread to `comments_template_with_comments`, Counter to `wp_stats_template_new`; each Scorecard has its own group (`vc_scorecard_template`, Google `google_reviews_vc_scorecard_template`). In the builder preview, `Reviews::preview_entry` gives Scorecard a sample headline, because the sample title and plugin name are both "NotificationX" and the card hides a title that equals the subject. The entries have no `defaults`, so picking one sets `link_button` to false. `GlobalFields::vc_theme_keys()` (the one PHP list of vc keys) hides the **Advanced Design** toggle and the **Button** toggle for them. The Custom Notification type and the Sales › Custom source do not offer vc themes (`'_vc-'` filters in [CustomNotification.php](../../../includes/Extensions/CustomNotification/CustomNotification.php) and [CustomNotificationConversions.php](../../../includes/Extensions/CustomNotification/CustomNotificationConversions.php)).
2. **Whitelist** — [themes/vc/isVcTheme.ts](../../../nxdev/notificationx/frontend/themes/vc/isVcTheme.ts) holds the one JS list of vc slugs (`VC_SLUGS`) plus an anchored key regex (`^[a-z0-9_]+_vc-[a-z]+$`). `isVcTheme(settings)` is true only when both match.
3. **Root class** — [Notification.tsx](../../../nxdev/notificationx/frontend/core/Notification.tsx) adds `nx-vc` to the root for `isVcTheme(settings) && !settings.template_adv`. `classnames` drops it for every other key, so existing root classes are unchanged.
4. **Theme branch** — Theme.tsx sets `isVc = isVcTheme(post) && !post.template_adv`.
   - `isVc && is_mobile` (NotificationX Pro's mobile card, `NotificationForMobile` renders Theme with `is_mobile`): the slots go through [vcCompactRows.ts](../../../nxdev/notificationx/frontend/themes/vc/vcCompactRows.ts) (at most three theme-one style rows: "name action", "product", "time") and then the generic Image/Content/Close path with the old "by NotificationX" credit.
   - `isVc && !is_mobile`: after the `{{tag}}` substitution, Theme returns `<VerifiedCard slots={template} themeName={…} />`.
   - Every other theme: the template and return path are exactly as before.
5. **Card** — [VerifiedCard.tsx](../../../nxdev/notificationx/frontend/themes/vc/VerifiedCard.tsx) renders the 344px card on desktop and tablet, and at 480px and below (guarded `matchMedia('(max-width: 480px)')`) the slim two-line card: a 32px image, line 1 with the time at its right end, and line 2 with credit A. Only one layout is mounted at a time. It uses the existing `Image` and `Close` helpers (concrete imports, never `core/index`).
6. **Credit** — [VcCredit.tsx](../../../nxdev/notificationx/frontend/themes/vc/VcCredit.tsx): `<small class="nx-branding nx-branding-verified nx-vc-credit">` with [VerifiedCheck](../../../nxdev/notificationx/frontend/themes/vc/VerifiedCheck.js), the translatable "Verified by", and a link to `affiliate_link` (`rel="nofollow"`, `target="_blank"`, `aria-label="NotificationX"`) holding the bell and the lettering as two SVGs ([VcBrandMark.js](../../../nxdev/notificationx/frontend/themes/vc/VcBrandMark.js), path data copied from BrandLogo). It is not rendered when `disable_powered_by` is on. `NXBranding.js`, `BrandLogo.js`, `Content.tsx` and `Star.tsx` are not changed by the vc family.
7. **SCSS** — [_themes/_vc.scss](../../../nxdev/notificationx/frontend/scss/_themes/_vc.scss), imported last in `_common.scss`. Every selector is rooted at `.notification-item.nx-vc.themes-vc-<slug>` (the `$nx-vc-root` list at the top of the file), so an existing theme, an Advanced Template card and Pro's `.notification-res-item` never match. The root sets the palette (`background`, `color`, `border` with `!important`), which overrides the inline Advanced Design styles of a notification saved before switching theme, and `max-width: 360px !important` over the inline Size. The close sits outside the top-right corner (theme twelve's reset); the credit link is above the Analytics link overlay. At 480px and below the root becomes the slim card (`width: calc(100% - 12px)`, `max-width: calc(100vw - 32px) !important`, a 40x40 close in the corner). RTL rules live under `[dir="rtl"] &`.

### Option C (slim card, line 1)

When line 1 does not fit, it scrolls once to show its hidden end and stays there. The timing is the pure [vcScrollPlan.ts](../../../nxdev/notificationx/frontend/themes/vc/vcScrollPlan.ts); the behaviour is the React hook [useVcLineScroll.ts](../../../nxdev/notificationx/frontend/themes/vc/useVcLineScroll.ts):

- One pass: wait 800ms, then (only then) measure the hidden width `d`; scroll at 40px/s, raised so the scroll fits in 2900ms and capped at 80px/s; hold 800ms; stay at the end (no scroll back, no loop). Total ≤ 4.5s plus any paused time.
- No scroll (the CSS "…" stays) when the line fits, when more than 232px is hidden, when `prefers-reduced-motion` matches (also when it turns on mid-run), or when `matchMedia` / `Element.animate` / `Animation.finished` is missing.
- Pointer or touch on the **notification root** pauses it, also during the waits (the root also gets the events that land on the Analytics link overlay). Release resumes it. A tap still opens the card link.
- `translateX(-d)` in LTR, `+d` when the computed direction is `rtl`. Only `transform` is animated; the full text stays in the DOM.
- The hook runs once per mount (Notification re-renders about every 25ms), guards every promise with a run token and cleans up on unmount. It imports only React (never `@wordpress/hooks`, never `core/index`).

### Frontend hooks

The vc desktop and slim cards do **not** run `nx_theme_before_content`, `nx_theme_after_content` or `nx_content_append` (NotificationX Pro uses them only for its Discount Alert themes). `nx_frontend_image` still runs through `Image`, and `nx_frontend_template` / `nx_frontend_time_is_countdown` run as for every theme. See [frontend-js-hooks.md](../../api/frontend-js-hooks.md#verified-cards-vc-).

### Advanced Template

The admin `nx_adv_template_default` callback ([AddEditNx/index.ts](../../../nxdev/notificationx/admin/AddEditNx/index.ts)) gives a vc key the compact rows (`vcCompactRows(getThemeName(v), GetTemplate(v))`) instead of the raw slots; every other key still gets `GetTemplate(v)`. On the frontend a vc key with an Advanced Template renders the merchant's lines in the generic card, with no `nx-vc` class and no vc styling.

### Thumbnails

`assets/admin/images/extensions/themes/vc/`: `vc-receipt.png`, `vc-showcase.png`, `vc-thread.png`, `vc-scorecard.png` and `vc-counter.png`. One PNG per design, shared by every key prefix of that design and used by the picker card and the list preview. They are committed static assets, not build output.

### Adding another vc theme

1. PHP: append the entry at the end of the Type's `$themes`, add the full keys to the right `_themes` list and to `GlobalFields::vc_theme_keys()`.
2. Add the slug to `VC_SLUGS` in `isVcTheme.ts`.
3. Add a stripped-slug case in `GetTemplate.ts` that returns the slot array (every slot a non-empty string) and document its indexes.
4. Add the desktop layout and the slim line 1 in `VerifiedCard.tsx`, and a case in `vcCompactRows.ts`.
5. Add the root to `$nx-vc-root` and a theme block at the end of `_vc.scss`.
6. Extend `tests/js/frontend/vc-*.test.js`. The golden tests ([golden-get-template.test.js](../../../tests/js/frontend/golden-get-template.test.js), [golden-render.test.js](../../../tests/js/frontend/golden-render.test.js)) must keep passing without updating their snapshots.
7. Add the thumbnail.

