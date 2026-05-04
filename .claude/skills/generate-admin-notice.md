# Skill: generate-admin-notice

Generate a fully functional, dated promotional admin notice for the NotificationX plugin.

Trigger phrases: "generate admin notice", "create admin notice", "add admin notice", "new admin notice", "add a promo notice", "add a deal notice"

---

## What this skill does

Scaffolds a complete admin notice by:
1. Asking the user for 5 core inputs (ID, campaign name, Figma link, dates)
2. Fetching the Figma design to extract all content and visual details automatically
3. Confirming extracted values with the user before writing code
4. Generating the PHP snippet and injecting it into `includes/Admin/Admin.php`
5. Adding a matching CSS rule to `assets/admin/css/wpdeveloper-review-notice.css`

---

## Step 0 — Ask these 5 questions

Ask the user for the following in one message:

| # | Question | Required | Notes |
|---|---|---|---|
| 1 | **Notice ID** (slug) | Yes | e.g. `nx_summer_deal_2026` — must be unique across all notices in Admin.php |
| 2 | **Campaign / display name** | Yes | e.g. `Summer Deal 2026` — used in comments and CSS |
| 3 | **Figma design link** | Yes | Full Figma URL to the notice design frame |
| 4 | **Start date & time** | Yes | e.g. `12:00:00am 1st June, 2026` |
| 5 | **End date & time** | Yes | e.g. `11:59:59pm 30th June, 2026` |

---

## Step 1 — Fetch the Figma design and extract content

Once the user provides the Figma link, use the Figma MCP tool (or WebFetch if Figma MCP is unavailable) to open the design and extract the following values:

| Field | What to look for in Figma |
|---|---|
| **Offer headline** | The primary bold/large text — usually the discount or offer name with emoji |
| **Body copy** | Smaller supporting text describing the offer |
| **CTA button label** | Text inside the primary action button |
| **CTA URL** | URL in the button's link property or prototype connection |
| **Dismiss link label** | Text on the secondary dismiss/skip link |
| **Show only to free users** | Look for any annotation or layer named "free only", "pro check", or similar; default `yes` if unclear |
| **Limit to dashboard only** | Look for a "screens" annotation or layer note; default `yes` if unclear |
| **Thumbnail image** | The logo/icon used — map to nearest available: `nx-icon.svg`, `full-logo.svg`, `logo.svg`, `crown.svg` |
| **Background color** | Fill color of the notice container frame (hex) |
| **Accent / border color** | Left border or accent stripe color (hex) |

After extracting, present a confirmation table to the user:

```
I extracted the following from your Figma design. Please confirm or correct any values:

| Field              | Extracted Value              |
|--------------------|------------------------------|
| Offer headline     | ...                          |
| Body copy          | ...                          |
| CTA button label   | ...                          |
| CTA URL            | ...                          |
| Dismiss label      | ...                          |
| Free users only    | yes / no                     |
| Dashboard only     | yes / no                     |
| Thumbnail          | full-logo.svg                |
| Background color   | #fff                         |
| Accent color       | #7600ff                      |

Reply "looks good" to proceed, or correct any values above.
```

Wait for confirmation before continuing.

**Fallback defaults** if a value cannot be extracted from Figma:
- Thumbnail → `full-logo.svg`
- Background color → `#fff`
- Accent color → `#7600ff`
- Free users only → `yes`
- Dashboard only → `yes`
- CTA URL → ask the user explicitly if not found

---

## Step 2 — Read the current notices() method

Before generating any code, read `includes/Admin/Admin.php` and locate the `notices()` method. Find:
- The last `$notices->add(...)` call in the method — your new block goes after it
- The exact line number so you can insert precisely
- The `self::ASSET_URL` pattern to confirm the asset path prefix

Also read `assets/admin/css/wpdeveloper-review-notice.css` and locate the last notice-specific CSS block to insert after it.

---

## Step 3 — Generate the PHP block

Produce the following block, substituting all `{placeholders}`:

```php
        // {Campaign Name}
        // Figma: {figma_link_or_none}
        $__{notice_id} = "<p><strong>{offer_headline}</strong> {body_copy}</p>
                <div class='nx-notice-action-button'>
                    <a style='display: inline-flex;column-gap:5px;' class='button button-primary' href='{cta_url}' target='_blank'>
                        {cta_label}
                    </a>
                    <a class='nx-notice-action-dismiss dismiss-btn' data-dismiss='true' href='#'>
                        {dismiss_label}
                    </a>
                </div>";

        $_{notice_id}_html = [
            'thumbnail' => self::ASSET_URL . 'images/{thumbnail}',
            'html'      => $__{notice_id},
        ];

        $notices->add(
            '{notice_id}',
            $_{notice_id}_html,
            [
                'start'       => strtotime( '{start_datetime}' ),
                'recurrence'  => false,
                'dismissible' => true,
                'refresh'     => NOTIFICATIONX_VERSION,
{screens_line}
                'expire'      => strtotime( '{end_datetime}' ),
{display_if_line}
            ]
        );
```

**Conditional lines:**

- `{screens_line}` — include only if "dashboard only" is yes:
  ```php
                  'screens'     => [ 'dashboard' ],
  ```

- `{display_if_line}` — include only if "free users only" is yes:
  ```php
                  'display_if'  => !is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) ),
  ```

- `{thumbnail}` — use the user-supplied value or default to `full-logo.svg`

**Variable naming rules** to avoid collisions:
- HTML string variable: `$__{notice_id}` (double underscore prefix)
- Config array variable: `$_{notice_id}_html` (single underscore prefix + `_html` suffix)

---

## Step 4 — Insert into Admin.php

1. Find the closing brace of the `notices()` method.
2. Insert the generated block **before** the closing brace, after the last existing `$notices->add(...)` call.
3. Leave one blank line between the previous block and the new one.
4. Do not move or reformat any existing code.

---

## Step 5 — Generate the CSS block

Produce this CSS block substituting placeholders:

```css
/* {Campaign Name} */
#wpnotice-notificationx-{notice_id} {
    background: {bg_color};
    border-left-color: {accent_color};
}
#wpnotice-notificationx-{notice_id} .wpnotice-thumbnail-wrapper img {
    width: 160px;
}
```

Defaults:
- `{bg_color}` → `#fff`
- `{accent_color}` → `#7600ff`

---

## Step 6 — Insert into CSS file

Append the CSS block at the end of `assets/admin/css/wpdeveloper-review-notice.css`, after the last existing notice block. Leave one blank line before the new block.

---

## Step 7 — Verify

After all edits confirm:
- [ ] `includes/Admin/Admin.php` — new `$notices->add('{notice_id}', ...)` block is present
- [ ] No PHP syntax errors: `php -l includes/Admin/Admin.php`
- [ ] Notice ID is unique — grep confirms it appears only once: `grep -c '{notice_id}' includes/Admin/Admin.php`
- [ ] `assets/admin/css/wpdeveloper-review-notice.css` — new CSS block appended
- [ ] Start timestamp is earlier than expire timestamp (sanity check the dates)

---

## Example output

Given user inputs:
- Notice ID: `nx_summer_deal_2026`
- Campaign: `Summer Deal 2026`
- Figma: `https://figma.com/file/abc123`
- Start: `12:00:00am 1st June, 2026`
- End: `11:59:59pm 30th June, 2026`

Extracted from Figma (confirmed by user):
- Headline: `☀️ Summer Sale: Flat 30% OFF!`
- Body: `Boost conversions with real-time social proof — don't miss this limited offer.`
- CTA label: `Upgrade To PRO`
- CTA URL: `https://notificationx.com/summer2026-admin-notice`
- Dismiss label: `Maybe Later`
- Free users only: yes
- Dashboard only: yes
- Thumbnail: `full-logo.svg`
- Background: `#fff`
- Accent: `#7600ff`

**PHP output:**
```php
        // Summer Deal 2026
        // Figma: https://figma.com/file/abc123
        $__nx_summer_deal_2026 = "<p><strong>☀️ Summer Sale: Flat 30% OFF!</strong> Boost conversions with real-time social proof — don't miss this limited offer.</p>
                <div class='nx-notice-action-button'>
                    <a style='display: inline-flex;column-gap:5px;' class='button button-primary' href='https://notificationx.com/summer2026-admin-notice' target='_blank'>
                        Upgrade To PRO
                    </a>
                    <a class='nx-notice-action-dismiss dismiss-btn' data-dismiss='true' href='#'>
                        Maybe Later
                    </a>
                </div>";

        $_nx_summer_deal_2026_html = [
            'thumbnail' => self::ASSET_URL . 'images/full-logo.svg',
            'html'      => $__nx_summer_deal_2026,
        ];

        $notices->add(
            'nx_summer_deal_2026',
            $_nx_summer_deal_2026_html,
            [
                'start'       => strtotime( '12:00:00am 1st June, 2026' ),
                'recurrence'  => false,
                'dismissible' => true,
                'refresh'     => NOTIFICATIONX_VERSION,
                'screens'     => [ 'dashboard' ],
                'expire'      => strtotime( '11:59:59pm 30th June, 2026' ),
                'display_if'  => !is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) ),
            ]
        );
```

**CSS output:**
```css
/* Summer Deal 2026 */
#wpnotice-notificationx-nx_summer_deal_2026 {
    background: #fff;
    border-left-color: #7600ff;
}
#wpnotice-notificationx-nx_summer_deal_2026 .wpnotice-thumbnail-wrapper img {
    width: 160px;
}
```

---

## Reference files

| File | Purpose |
|---|---|
| [includes/Admin/Admin.php](includes/Admin/Admin.php) | Insert new notice block here (inside `notices()` method) |
| [assets/admin/css/wpdeveloper-review-notice.css](assets/admin/css/wpdeveloper-review-notice.css) | Append CSS here |
| [assets/admin/images/](assets/admin/images/) | Available thumbnails (`nx-icon.svg`, `full-logo.svg`, `logo.svg`, `crown.svg`) |
| [AGENTS.md](AGENTS.md) | Full project agent guidance |
