# Discount Alert Notification Type (`offer_announcement`)

> Shows a small "toast" card announcing a discount/offer (title, description, discount
> badge, optional CTA button) — e.g. "Flash Sale: Limited Time Offer! Enjoy flat 50%
> Off...". Internally this Type is called **OfferAnnouncement**; its admin-UI title is
> **"Discount Alert"**, and its sole data-source extension is **Announcements**
> (`$id = 'announcements'`). The whole feature is Pro-only.

## At a glance

| | |
|---|---|
| **Type ID** | `offer_announcement` |
| **Class** | [`includes/Types/OfferAnnouncement.php`](../../includes/Types/OfferAnnouncement.php) |
| **Trait** | none — no matching file in [`includes/Types/Traits/`](../../includes/Types/Traits/) (only `Conversions.php` and `Reviews.php` exist there) |
| **Priority** | `36` (`$priority`) |
| **Is Pro** | `true` on both the Type (`OfferAnnouncement::$is_pro`) and its extension (`Announcements::$is_pro`) — this type is entirely Pro-gated |
| **Default source** | `announcements` (`$default_source`) — matches `Announcements::$id` |
| **Default theme** | `announcements_theme-one` (`$default_theme`) — stale/unreachable. No theme with key `theme-one` exists in `Announcements::$themes` (the real keys are `theme-1`, `theme-2`, `theme-12`, `theme-14`, `theme-15`), and the extension does not set its own `$default_theme` (confirmed), so `Extension::__source_trigger()` ([Extension.php:218-224](../../includes/Extensions/Extension.php#L218-L224)) falls back to this type value and emits `@themes:announcements_theme-one`, a pre-fill matching no registered theme id. |
| **Link type** | Inherited `'none'` from `Types` — `OfferAnnouncement::$link_type` is declared but commented out ([OfferAnnouncement.php:33](../../includes/Types/OfferAnnouncement.php#L33)), and the `link_types()` method that would add a `comment_url` option to the global Link Type field is never hooked (its `add_filter('nx_link_types', ...)` call is commented out in the constructor, [OfferAnnouncement.php:41](../../includes/Types/OfferAnnouncement.php#L41)). Treat both as dead code unless re-enabled. |
| **Module gate (`$module`)** | Type declares `['modules_announcements']` ([OfferAnnouncement.php:28](../../includes/Types/OfferAnnouncement.php#L28)), but per the pattern already documented for `conversions`/`notification_bar`, `Types::$module` is not read by `TypeFactory::register_types()` ([includes/Types/TypesFactory.php](../../includes/Types/TypesFactory.php)) — it does not gate the Type itself. What actually gates loading is the **Announcements extension**'s own `$module = 'modules_announcements'` ([Announcements.php:33](../../includes/Extensions/OfferAnnouncement/Announcements.php#L33)), checked via `Modules::get_instance()->is_enabled($obj->module)` in `ExtensionFactory::register_extensions()`. Note `modules_announcements` has no entry in `Core/Modules.php`'s registered defaults found in this pass, but `Modules::is_enabled()` treats an unrecognized/unset key as enabled by default ([Core/Modules.php:66-76](../../includes/Core/Modules.php#L66-L76)), so the module is effectively on unless a site explicitly disables it in settings. |
| **Compatible extensions** | Exactly one: **Announcements** (`$id = 'announcements'`, `$types = 'offer_announcement'`) — [`includes/Extensions/OfferAnnouncement/Announcements.php`](../../includes/Extensions/OfferAnnouncement/Announcements.php). Verified via `grep -rl "'offer_announcement'" includes/Extensions` (only hits: `GlobalFields.php` and `Announcements.php`). |

## What it does

`OfferAnnouncement::init()` ([OfferAnnouncement.php:44](../../includes/Types/OfferAnnouncement.php#L44-L58)) sets the admin title to "Discount Alert" and configures `$this->popup` — the "Upgrade to PRO" modal content (embedded demo video + "More Info" / "Upgrade to PRO" links) shown to free-plugin users who try to select this type in the builder, since both the Type and its extension are `is_pro = true`.

All actual theme/content configuration lives on the **Announcements** extension's `init_extension()` ([Announcements.php:45](../../includes/Extensions/OfferAnnouncement/Announcements.php#L45-L254)), which registers:
- 5 desktop themes (`theme-1`, `theme-2`, `theme-12`, `theme-14`, `theme-15` — a commented-out `theme-13` also exists in source but is not active) sharing one content template (`announcements_template_new`), each with its own preview image, optional `image_shape` (`rounded`/`circle`), and per-theme `defaults` (`offer_title`, `offer_description`, `link`, `announcement_link_button_text`, and — on `theme-12` only — `link_button`).
- 5 Pro-only responsive themes (`res-theme-one` … `res-theme-five`), all `is_pro => true`.

Each theme's `template` maps `first_param → tag_offer_title`, `third_param → tag_offer_description`, `fourth_param → tag_time` (a commented-out `fifth_param → tag_offer_discount` exists on several themes but is not active), following the same tag-based template system used by `conversions` (see [docs/types/conversions.md](conversions.md)). `announcements_template_new`'s tag catalogue additionally offers `tag_sometime` (fourth param) and, for a `fifth_param` not currently wired into any active theme, `tag_offer_discount` / `tag_offer_image`.

`Announcements::doc()` ([Announcements.php:268](../../includes/Extensions/OfferAnnouncement/Announcements.php#L268-L275)) supplies the admin-UI help text/links for this source.

## Data flow

Trace one Discount Alert notification end-to-end:

1. `FrontEnd::get_notifications_ids()` ([FrontEnd.php:487](../../includes/FrontEnd/FrontEnd.php#L487-L613)) has **no special-cased branch** for `source == 'announcements'` — it falls through to the generic `else { $active_notifications[] = ... }` branch alongside most standard (non-bar/popup/gdpr/exit-intent) types, so it is bucketed as a normal `active` (or `global`, if `global_queue` is set) notification.
2. `FrontEnd::get_notifications_data()` ([FrontEnd.php:243](../../includes/FrontEnd/FrontEnd.php#L243-L467)) processes the merged `active`/`global`/`shortcode` bucket generically: it calls `get_entries()`, which queries the custom Entries DB table (`Entries::get_instance()->get_entries()`) for rows matching `nx_id` + `source = 'announcements'`, then merges each row's stored `data` blob into the entry.
3. `Announcements::get_data($args = [])` ([Announcements.php:264](../../includes/Extensions/OfferAnnouncement/Announcements.php#L264-L266)) currently just `return`s the literal string `'Hello From Custom Notification'` — it does not build a real entries array. `Announcements` also sets no `$cron_schedule`, so `Extension::add_cron_job()` never schedules a cron for it either. **This strongly suggests the free-plugin copy of this extension is a stub/placeholder and the real entry-population logic (populating `offer_title`, `offer_description`, `offer_discount`, etc. per entry) lives elsewhere — most likely in the Pro plugin, consistent with `is_pro = true` on both classes.** **Verified (Pro):** the real flow does **not** use `get_data()` at all — `NotificationXPro\Extensions\OfferAnnouncement\Announcements` (in [`notificationx-pro/includes/Extensions/OfferAnnouncement/Announcements.php`](../../../notificationx-pro/includes/Extensions/OfferAnnouncement/Announcements.php)) `extends` the free class but does **not** override `get_data()` (the `'Hello From Custom Notification'` stub is simply never exercised, and no cron is scheduled). Instead it hooks `nx_frontend_get_entries` → `nx_frontend_get_entries($entries, $ids, $notifications)`, which builds **one entry per notification directly from the post/settings object** (reading the flat keys `offer_title`, `offer_description`, `offer_discount`, `image`, `link`, `announcement_link_button_text`, and `expire_time` off `$post`) and merges it into the entries array — there is **no** `announcement_entries` repeater saved into the Entries DB table (the earlier guess is wrong; `announcement_entries` is a UI `section` wrapper, not a saved repeater — see below). A companion `nx_filtered_data_{$id}` filter (`exclude_announcements()`) drops the entry once `strtotime($settings['expire_time'])` is in the past (except for themes `announcements_theme-13`/`-15`, which have no expiry).
4. On the frontend, React's `normalize()` ([nxdev/notificationx/frontend/core/utils.ts:66](../../nxdev/notificationx/frontend/core/utils.ts#L66-L97)) is used (this is a standard multi-field type, not a `normalizePressBar` config-only type). `GetTemplate()` ([nxdev/notificationx/frontend/themes/GetTemplate.ts](../../nxdev/notificationx/frontend/themes/GetTemplate.ts#L143-L160)) has explicit cases for `announcements_theme-1`/`-2`/`-12`/`-14` (renders `first_param` / `third_param` / `fourth_param`), `announcements_theme-13` (title only), and `announcements_theme-15` (title + description, no time row).
5. `Theme.tsx` ([nxdev/notificationx/frontend/themes/Theme.tsx:38-53](../../nxdev/notificationx/frontend/themes/Theme.tsx#L38-L53)) resolves each `{{tag}}` token in the template against `entry[key]` (the per-entry `data`, e.g. `entry.offer_title`) — with a special case: for `key === 'time'` and `post.source === 'announcements'`, it appends a localized `" remaining"` suffix to the relative time string (countdown-style wording, distinct from other types' plain "x days ago").
6. `nxdev/notificationx/frontend/themes/announcements/index.tsx` renders theme-specific SVG "discount badge" components (`theme-1.tsx` for `theme-1`/`theme-12`, `theme-2.tsx` for `theme-2`) alongside the generic template text; these read `offer_discount`, `link_button_bg_color/text_color`, etc. directly as props, not `{{tag}}` tokens.
7. `nxdev/notificationx/frontend/themes/helpers/Button.js` ([Button.js:9-19](../../nxdev/notificationx/frontend/themes/helpers/Button.js#L9-L19)) — the CTA button — is rendered only for `announcements_theme-13/-14/-15` and reads `link` / `announcement_link_button_text` straight off `config` (the post/settings object), **not** off the per-entry `data`. So the button's URL/label are per-notification settings, while the title/description/discount are per-entry content.

## Fields & settings schema

`OfferAnnouncement::init_fields()` just calls `parent::init_fields()` — no type-specific field registration. `Announcements` extension does **not** override `init_fields()` either (no `content_fields()`/`design_fields()`/`customize_fields()` filters are hooked from this class), unlike the `ExitIntentNotification` pattern shown in [docs/new-notification-type.md](../development/adding-a-notification-type.md). The generic builder fields consumed by this type are:

- **Template tag pickers** — `first_param`/`custom_first_param`, `third_param`/`custom_third_param`, `fourth_param`/`custom_fourth_param` (and unused `fifth_param`/`custom_fifth_param`) declared centrally in `GlobalFields.php` ([GlobalFields.php:668-743](../../includes/Extensions/GlobalFields.php#L668-L743)), same mechanism as `conversions`.
- **Design tab (Advanced Design)** — `GlobalFields.php` gates two colour fields specifically to this type and its first two themes: `discount_text_color` and `discount_background`, both ruled on `Rules::is('type', 'offer_announcement')` + `Rules::includes('themes', ['announcements_theme-1', 'announcements_theme-2'], false)` ([GlobalFields.php:517-535](../../includes/Extensions/GlobalFields.php#L517-L535)).
- **Content fields for `offer_title` / `offer_description` / `offer_discount` / `image` / `expire_time` / `announcement_entries`** — referenced by name in the Quick Builder's field allow-list ([includes/Core/QuickBuild.php:230-236](../../includes/Core/QuickBuild.php#L230-L236)), but **no field-array definition for any of these exists in this free-plugin repo**. **Verified (Pro):** they are all defined in `NotificationXPro\Extensions\OfferAnnouncement\Announcements::content_fields()` (hooked on `nx_content_fields`). `announcement_entries` is **not** a repeater of multiple offers — despite the plural-sounding name it is a `type => 'section'` wrapper (`classes => 'nx-announcement-entries'`, priority 300) holding these flat, theme-gated fields: `offer_title` (text, default "Flash Sale: Limited Time Offer!"), `offer_discount` (text, only themes `_theme-1`/`_theme-2`), `offer_description` (textarea, themes 1/2/12/14/15), `image` (media, themes 12/14), `expire_time` (date, default now +7 days, themes 1/2/12/14), and `announcement_link_button_text` (text, default "Grab Now", theme-gated). A `link` (URL) text field is present in source but **commented out** — instead the Pro `link_types()` adds an `announcements_link` "URL" Link-Type option gated to `source == announcements`. The whole section is ruled `Rules::is('source', 'announcements')`.
- `link` and `announcement_link_button_text` (CTA URL/label) are read directly off the post/settings object on the frontend (see Data flow step 7) — their field definitions are likewise not found in this repo.

## Themes / templates

`Announcements::$themes` (all Pro):

| Theme key | `image_shape` | Notes |
|---|---|---|
| `theme-1` | `rounded` | Base "Flash Sale" card |
| `theme-2` | `circle` | Same template, circular image |
| `theme-12` | `rounded` | Adds `link_button => true` default (CTA shown) |
| `theme-14` | `circle` | Default copy is "Hi There!" instead of "Flash Sale..."; renders the CTA `Button` (theme-13/14/15 only) |
| `theme-15` | _(none set)_ | No `fourth_param`/time row; renders the CTA `Button` |

All map to the single content template `announcements_template_new` ([Announcements.php:228-253](../../includes/Extensions/OfferAnnouncement/Announcements.php#L228-L253)). `Announcements::$res_themes` adds 5 Pro responsive themes (`res-theme-one` … `res-theme-five`), mirroring the desktop themes' template shapes with no free tier.

A commented-out `theme-13` ("How Does It Works") exists in source but is not active as a selectable theme card — `GetTemplate.ts` still has a live case for `announcements_theme-13` (title-only row), so re-enabling it in PHP would work without further frontend changes. **Verified (Pro):** theme-13 is a *prepared-but-not-registered* theme, not abandoned — the Pro `Announcements` class already carries live support code for it: `exclude_announcements()` special-cases `announcements_theme-13` (no expiry filtering), `preview_entry()` sets its `offer_title` to "How Does It Works" (and blanks the image), `preview_settings()` forces `show_notification_image => 'none'` for it, and the `announcement_link_button_text` rule set includes `_theme-13`. So the supporting wiring is in place in Pro; only the theme-card entry in `$themes` remains commented out.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/OfferAnnouncement.php](../../includes/Types/OfferAnnouncement.php) |
| Base class | [includes/Types/Types.php](../../includes/Types/Types.php) |
| Extension | [includes/Extensions/OfferAnnouncement/Announcements.php](../../includes/Extensions/OfferAnnouncement/Announcements.php) |
| Shared template-tag fields | [includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php) (first/third/fourth param pickers; `discount_text_color`/`discount_background`) |
| Quick Builder field allow-list | [includes/Core/QuickBuild.php](../../includes/Core/QuickBuild.php#L230-L236) |
| PHP frontend routing | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) — generic `get_notifications_ids()` / `get_notifications_data()` / `get_entries()` paths (no type-specific branch) |
| Frontend template resolution | [nxdev/notificationx/frontend/themes/GetTemplate.ts](../../nxdev/notificationx/frontend/themes/GetTemplate.ts), [nxdev/notificationx/frontend/themes/Theme.tsx](../../nxdev/notificationx/frontend/themes/Theme.tsx) |
| Frontend theme components | [nxdev/notificationx/frontend/themes/announcements/](../../nxdev/notificationx/frontend/themes/announcements/) (`index.tsx`, `theme-1.tsx`, `theme-2.tsx`), [nxdev/notificationx/frontend/themes/helpers/Button.js](../../nxdev/notificationx/frontend/themes/helpers/Button.js), [nxdev/notificationx/frontend/themes/helpers/Content.tsx](../../nxdev/notificationx/frontend/themes/helpers/Content.tsx) |
| Frontend styles | `nxdev/notificationx/frontend/scss/_themes/_announcement.scss` |

## Dependencies

None required for core WordPress — this is a manually-authored content type (no third-party integration like WooCommerce/EDD). It is, however, entirely gated behind NotificationX Pro (`is_pro = true` on both the Type and its only extension); on the free plugin, selecting it in the builder shows the "Upgrade to PRO" popup (`OfferAnnouncement::$popup`) instead of the real builder UI.

## Testing notes & gotchas

- `Announcements::get_data()` is a stub (`return 'Hello From Custom Notification';`) and no `$cron_schedule` is set, so the normal Extension → Cron → `get_data()` → Entries pipeline documented in [docs/new-notification-type.md](../development/adding-a-notification-type.md) does not appear to be how this type's real content reaches the Entries table in production. **Verified (Pro):** confirmed — this type's content never touches the Entries table. The Pro `Announcements` extension does **not** override `get_data()` and schedules no cron; it builds a single synthetic entry on the fly from the notification's own settings via the `nx_frontend_get_entries` filter (see Data flow step 3). So `get_data()` is not a reliable reference for the real data shape — the offer fields (`offer_title`/`offer_description`/`offer_discount`/`image`/`link`/`announcement_link_button_text`/`expire_time`) are read straight off the post/settings object at request time.
- The Type's `$default_theme` (`announcements_theme-one`) does not match any real theme key (`theme-1`, not `theme-one`). Because the extension sets no `$default_theme`, `Extension::__source_trigger()` still emits `@themes:announcements_theme-one` as the pre-fill — a value no registered theme matches, so this fallback is inert (the real theme is set only when the user picks a card). The remaining question — what the QuickBuilder radio field renders when handed an unmatched pre-fill — is a client-side concern, not driven from PHP.
- `$link_type` and `link_types()` are both effectively dead code (declaration/hook commented out) — don't assume `comment_url` is an available Link Type option for this notification; it inherits the base `'none'`.
- The CTA button (`link`, `announcement_link_button_text`) is rendered from `config` (post settings) while the title/description/discount are rendered from per-entry `data` — if you add a new theme, make sure new dynamic text goes through the correct one of the two paths (see Data flow steps 5 & 7).
- No dedicated test files for this type exist under `tests/` — confirmed: `grep -rli "offer_announcement\|announcements" tests/` returns no hits (the factory tests use `popup` as their representative fixture).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Sales Notification Type (`conversions`)](conversions.md) — the type this one's tag-template mechanism (`first_param`/`third_param`/`fourth_param`) most closely follows
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
