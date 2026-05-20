---
name: admin-notice-generator
description: Generates WordPress admin notice code for NotificationX campaigns from a Figma design link. Pulls text, colors, CTA, and dismiss copy from Figma via the Figma MCP, then writes ready-to-paste PHP and CSS into Admin.php and wpdeveloper-review-notice.css.
tools: Read, Edit, Write, mcp__figma__*, Skill
---

# NotificationX Admin Notice Generator

This skill is **Figma-first**: it begins by asking the user for a Figma design link, then extracts the campaign's content (headline, CTA text, dismiss text, colors, URL) from that design using the Figma MCP. Only values that cannot be derived from Figma (or that the user wants to override) are asked interactively. Finally, it writes the PHP notice block and CSS into the correct files.

## Target Files

- **PHP** : `wp-content/plugins/notificationx/includes/Admin/Admin.php`
- **CSS** : `wp-content/plugins/notificationx/assets/admin/css/wpdeveloper-review-notice.css`

---

## Step 0 — Ask for the Figma design link (FIRST QUESTION)

Before anything else, ask the user **one question**:

> "Please share the Figma design link for this admin notice campaign."

Wait for the link. Accept any of these forms:
- `https://www.figma.com/file/<KEY>/...`
- `https://www.figma.com/design/<KEY>/...?node-id=<NODE_ID>`
- A `figma.com` share URL with or without a `node-id` query parameter.

If the link does **not** contain a `node-id`, ask the user to share a link that points to the specific frame/node of the notice design (so we extract only the relevant frame, not the whole file).

---

## Step 1 — Pull content from Figma

Before calling any Figma MCP tool, **load the `figma:figma-use` skill** (it is a mandatory prerequisite per its own description). Then use the Figma MCP to extract the design content:

1. Use `mcp__figma__authenticate` if not authenticated; complete with `mcp__figma__complete_authentication` if prompted.
2. Use `use_figma` (read-mode JS) on the node from the URL to read:
   - All visible **text layers** (headline, CTA label, dismiss label).
   - **Fill colors** of: the notice container/left-border accent, the primary CTA button background, the CTA button text color.
   - Any **hyperlink/URL** attached to the CTA (Figma prototypes / link props). If none, ask the user for it.
   - Any **image/icon** placed inside the CTA (e.g. a crown SVG). Record asset name if present.

3. Map the extracted content to the campaign fields:

| Campaign field | Figma source (typical) |
|---|---|
| `CAMPAIGN_TEXT` | Largest body text layer / headline frame |
| `CTA_BUTTON_TEXT` | Text layer inside the primary button component |
| `DISMISS_TEXT` | Secondary text-link / "maybe later" layer (optional) |
| `BORDER_COLOR` | Left-edge accent stroke/fill of the notice frame |
| `BUTTON_BG` | Background fill of the primary CTA button |
| `BUTTON_TEXT_COLOR` | Text fill of the CTA button label |
| `PRICING_URL` | Prototype link / annotation on the CTA (ask user if missing) |

4. **Show the extracted values back to the user as a table for review** before continuing. The user can correct any field.

---

## Step 2 — Ask only for values Figma cannot provide

After confirming the Figma-derived values, ask the user **one question at a time** (sequentially, waiting for each answer) for the remaining fields that cannot be inferred from the design:

1. **Campaign key** (unique snake_case ID, e.g. `nx_summer_sale_2026`) — used as the notice ID and CSS selector. The rendered DOM ID will be `wpnotice-notificationx-{campaign_key}`.
2. **Pricing / CTA URL** — only if not present in the Figma design.
3. **Campaign start date** — when the notice should start showing (e.g. `now`, `+1 day`, or a date like `1st April, 2026`). Default: `now`.
4. **Campaign expiry date** — when the notice should stop showing (e.g. `11:59:59pm 30th April, 2026`). Leave blank for no expiry.
5. **Show only on screens** — comma-separated WordPress screen IDs (e.g. `dashboard`). Leave blank for all screens.

After all questions are answered, **confirm the full collected values** (Figma-derived + asked) with the user before writing any code.

---

## Step 3 — Generate Code

### PHP block template

Insert the following block **just before** the closing line `self::$cache_bank->create_account( $notices );` in the `admin_notices()` method of `Admin.php`.

```php
        // {CAMPAIGN_KEY} notice
        $_{CAMPAIGN_KEY}_text = "<p>{CAMPAIGN_TEXT}</p>
                        <div class='nx-notice-action-button' style='display: inline-flex;column-gap:5px;align-items: center;'>
                            <a class='button button-primary' href='{PRICING_URL}' target='_blank'>{CTA_BUTTON_TEXT}</a>{DISMISS_BUTTON}
                        </div>";
        $_{CAMPAIGN_KEY} = [
            'thumbnail' => self::ASSET_URL . 'images/full-logo.svg',
            'html'      => $_{CAMPAIGN_KEY}_text,
        ];
        $notices->add(
            '{CAMPAIGN_KEY}',
            $_{CAMPAIGN_KEY},
            [
                'start'       => {START_METHOD},
                'recurrence'  => false,
                'dismissible' => true,
                'refresh'     => NOTIFICATIONX_VERSION,{SCREENS_LINE}{EXPIRE_LINE}
                'display_if'  => !is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) )
            ]
        );
```

**Substitution rules:**

| Placeholder | Value |
|---|---|
| `{CAMPAIGN_KEY}` | The snake_case campaign key provided |
| `{CAMPAIGN_TEXT}` | The headline/main text from Figma (HTML allowed; preserve `<strong>` tags if Figma used bold runs) |
| `{PRICING_URL}` | The CTA URL (from Figma prototype link or asked) |
| `{CTA_BUTTON_TEXT}` | The CTA button label from Figma |
| `{DISMISS_BUTTON}` | If dismiss text found in Figma (or supplied): ` <a class='nx-notice-action-dismiss dismiss-btn' data-dismiss='true' href='#'>{DISMISS_TEXT}</a>` — otherwise empty string |
| `{START_METHOD}` | `$notices->time()` if start is `now`, else `$notices->strtotime( '+N day' )` for relative, or `strtotime( '{DATE}' )` for an absolute date |
| `{SCREENS_LINE}` | If screens provided: `\n                'screens'     => [ '{SCREEN}' ],` — otherwise omit |
| `{EXPIRE_LINE}` | If expiry provided: `\n                "expire"      => strtotime( '{EXPIRY_DATE}' ),` — otherwise omit |

---

### CSS block template

Append the following block at the **end** of `wpdeveloper-review-notice.css`. Note the DOM ID prefix is `wpnotice-notificationx-`.

```css
/*
 * {CAMPAIGN_KEY_HUMAN} Notice
 */
#wpnotice-notificationx-{CAMPAIGN_KEY} {
    border-left-color: {BORDER_COLOR};
    padding: 10px 40px 10px 20px;
}
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper {
    display: flex;
    align-items: start;
    justify-content: space-between;
    width: 100%;
    flex-direction: column;
    gap: 5px;
}
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper p {
    font-size: 14px;
    margin-top: 0;
    padding-top: 0;
}
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper .nx-notice-action-button > a.button-primary,
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper .nx-notice-action-button > a.button-primary:focus,
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper .nx-notice-action-button > a.button-primary:hover {
    background-color: {BUTTON_BG};
    color: {BUTTON_TEXT_COLOR};
    box-shadow: 0px 1px 0px 0px #000000;
    border: none;
    border-radius: 6px;
    padding: 2px 16px;
}
#wpnotice-notificationx-{CAMPAIGN_KEY} .wpnotice-content-wrapper .nx-notice-action-button .nx-notice-action-dismiss {
    background: none;
    border: none;
    color: #424242;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.5;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
    margin: 0;
    display: inline-block;
    margin-left: 5px;
}
@media screen and (max-width: 574px) {
    #wpnotice-notificationx-{CAMPAIGN_KEY} {
        display: none !important;
    }
}
```

**Substitution rules:**

| Placeholder | Value |
|---|---|
| `{CAMPAIGN_KEY_HUMAN}` | Campaign key with underscores replaced by spaces, title-cased |
| `{CAMPAIGN_KEY}` | Raw snake_case campaign key |
| `{BORDER_COLOR}` | Border-left hex color from Figma |
| `{BUTTON_BG}` | CTA button background hex color from Figma |
| `{BUTTON_TEXT_COLOR}` | CTA button text hex color from Figma |

---

## Step 4 — Confirm & Write

1. Show the user the **complete generated PHP block** and **complete generated CSS block** for review.
2. Ask: *"Shall I write these changes into Admin.php and wpdeveloper-review-notice.css?"*
3. On confirmation:
   - Use `Edit` to insert the PHP block in `Admin.php` — find the exact line `self::$cache_bank->create_account( $notices );` and insert **just before** it.
   - Use `Edit` to append the CSS block at the end of `wpdeveloper-review-notice.css`.
4. Report the exact lines changed in each file.

---

## Notes

- **Always** start with the Figma link question. Do not ask for text/colors/CTA before pulling them from Figma — the design is the source of truth.
- If the Figma MCP is unavailable, unauthenticated, or the link fails to load, tell the user and offer a fallback: ask each field interactively (the original 11-question flow).
- Convert Figma color values (which the MCP returns as `{r,g,b,a}` floats 0–1, or hex) to `#RRGGBB` hex strings before substituting. Round to nearest 8-bit.
- Preserve text emphasis from Figma — if a text layer contains a bold run inside a regular paragraph, wrap that run in `<strong>...</strong>` in `CAMPAIGN_TEXT`.
- If the design includes an icon inside the CTA (e.g. a crown), wrap it as `<img style='width:15px;' src='{PATH}'/>` inside the `<a>` tag. The crown is available at `self::ASSET_URL . 'images/crown.svg'`.
- Never overwrite existing notices — always insert/append.
- Validate hex colors: if Figma or the user supplies a value without `#`, prepend it automatically.
- If start date is `now`, use `$notices->time()`. If it's a relative string like `+1 day`, use `$notices->strtotime( '+1 day' )`. If it's an absolute date, use PHP `strtotime( '...' )` directly.
- The Pro plugin check for NotificationX is `notificationx-pro/notificationx-pro.php`.
- The asset URL constant in this plugin is `self::ASSET_URL` and the version constant is `NOTIFICATIONX_VERSION`.
- The action button wrapper class is `nx-notice-action-button` and the dismiss link class is `nx-notice-action-dismiss` (note the `nx-` prefix).
