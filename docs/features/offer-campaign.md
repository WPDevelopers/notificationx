# Seasonal offer campaign: dashboard notice and menu pointer

Free-only upsell UI for a sale. A campaign has two surfaces, and they share one set of dates and one link:

| Surface | Where it shows | Code |
| --- | --- | --- |
| **Offer notice** | WordPress Dashboard (`wp-admin/index.php`), above the widgets | The campaign's block in `Admin::notices()` ([includes/Admin/Admin.php](../../includes/Admin/Admin.php)) + its CSS block in [assets/admin/css/wpdeveloper-review-notice.css](../../assets/admin/css/wpdeveloper-review-notice.css) |
| **Offer pointer** | Beside the **NotificationX** menu item, on NotificationX's own pages only (not the Dashboard, which has the notice) | [includes/Admin/OfferPointer.php](../../includes/Admin/OfferPointer.php) + [assets/admin/js/offer-pointer.js](../../assets/admin/js/offer-pointer.js) |

Neither shows while NotificationX Pro is active. Nothing here is Pro code.

## Current campaign: Black Friday / Cyber Monday 2026

| | |
| --- | --- |
| Dates | 15 November 2026 00:00:00 to 14 December 2026 23:59:59, UTC (`OfferPointer::START`, `OfferPointer::EXPIRE`) |
| Link | `https://notificationx.com/bfcm2026/admin-notice` (`OfferPointer::URL`), opened in a new tab |
| Notice id | `nx_bfcm_2026` (DOM id `wpnotice-notificationx-nx_bfcm_2026`) |
| Notice copy | 🛍️ **Black Friday Sale:** Turn holiday traffic into customers with 15+ real-time social proof notifications, AI-powered features & more – **now up to 40% OFF!** |
| Notice actions | **Upgrade To Pro Now** (the link), **I Want To Pay Full Price** (dismisses) |
| Pointer id | `bfcm2026` (`OfferPointer::ID`) |
| Pointer copy | Title **Black Friday Is On!**; text "Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers." |
| Pointer actions | **View Deals** (the link), **Dismiss** |

The notice reads its dates and its link from the `OfferPointer` constants, so the two surfaces cannot drift apart.

## The offer notice

One block in `Admin::notices()`, registered with the `priyomukul/wp-notice` library like every earlier campaign (they stay in the file; an expired notice never shows). The options that matter:

- `start` / `expire`: the campaign window.
- `screens => [ 'dashboard' ]`: the WordPress Dashboard only.
- `display_if => ! is_plugin_active( 'notificationx-pro/notificationx-pro.php' )`, and `Admin::init()` registers notices only when `! NotificationX::is_pro()`.
- `dismissible => true`: the **×** and the "I Want To Pay Full Price" link (`data-dismiss='true'`) both dismiss it for that user (user meta `notificationx_nx_bfcm_2026_notice_dismissed`).

Things that are easy to get wrong:

- **The CSS is keyed on the notice id.** The stylesheet block is `#wpnotice-notificationx-nx_bfcm_2026 …`. A notice whose CSS names another id renders unstyled and nothing reports it. `tests/test-offer-campaign.php` pins the id on both sides.
- **The emoji is an HTML entity inside `<span class='nx-notice-emoji wp-exclude-emoji'>`.** WordPress replaces a raw emoji in wp-admin with a small Twemoji image; `wp-exclude-emoji` stops that. The span is 14px with `vertical-align: 0`, the text's own size: the Halloween 2026 notice used 18px / -2px, which stood 4px out of the line.
- **One WPDeveloper notice shows at a time,** across every plugin that vendors the notice library (EmbedPress, Essential Blocks, BetterDocs, …). NotificationX registers its notices as library version `1.0.0`, which the library queues after every plugin that declares a newer version. If another active plugin has a notice due on the Dashboard (for example EmbedPress's opt-in notice), that one shows and the NotificationX notice waits. This is the library's behaviour and is the same for every NotificationX notice.
- **A dismissal starts a cooldown.** Dismissing any WPDeveloper notice sets the `wpnotice_priority_time_expired` transient, and no WPDeveloper notice shows until it is gone.
- Under 575px wide the notice is hidden (`display: none`).

## The offer pointer

`OfferPointer` is a class of static methods, registered from `Admin::init()` **with or without Pro**: it shows only without Pro, and with Pro it still has to give back a pointer slot it holds. It uses WordPress's own `wp-pointer` script and stylesheet.

### When it shows

`OfferPointer::enqueue()` runs on `admin_enqueue_scripts` and loads the pointer only when all of these hold:

1. NotificationX Pro is not active (`OfferPointer::is_pro_active()`).
2. The time is inside the window (`in_window()`, both ends included).
3. The screen is `toplevel_page_nx-admin` or a `notificationx_page_nx-*` page (`is_screen()`). Not the WordPress Dashboard, not the Plugins screen, not the network admin.
4. The user can `manage_options`.
5. The user has not dismissed it (`is_dismissed()`, user meta `_nx_offer_pointer_bfcm2026_dismissed`).
6. No other WPDeveloper plugin holds the shared pointer slot with a smaller number (`may_take_slot()`).

The script then needs the menu link `#toplevel_page_nx-admin > a.menu-top` on the page. If a role or a menu editor removed it, nothing shows. Under 783px, where the admin menu is off canvas, the pointer is hidden until the menu is opened.

### The shared pointer slot

Every WPDeveloper plugin that shows a pointer reads and writes the option `_wpdeveloper_plugin_pointer_priority`. The smallest number wins; a plugin clears the option when its pointer is dismissed.

- NotificationX's number is **5**, the priority its notices use. (Essential Blocks is 3, Better Payment is 9.)
- NotificationX takes the slot when it is empty or held by a larger number, and waits while a smaller number holds it. It never overwrites a smaller number.
- It releases the slot **only while it holds it**: on a dismissal (`record_dismissal()`), and on `admin_init` once the campaign is over or Pro is active (`release_when_over()`), so a later plugin is not kept waiting for a pointer that will not come back.

### Dismissal

**Dismiss** closes the pointer and posts to `admin-ajax.php` with action `nx_dismiss_offer_pointer` and a nonce (`nx_offer_pointer`). `OfferPointer::dismiss()` checks the nonce and `manage_options`, stores the time in the user meta and releases the slot. The dismissal is per user and kept for good; the id is part of the meta key, so a new campaign needs a new `ID`. Following **View Deals** does not dismiss it. The action is registered for logged-in users only.

### Script and look

[assets/admin/js/offer-pointer.js](../../assets/admin/js/offer-pointer.js) is plain JavaScript with no build step; the file in the repository is the file the browser loads. Do not move it into the webpack build: `assets/admin/js/` also holds built files, but webpack only deletes the ones it names.

- The data arrives as `window.nxOfferPointer` (printed with `wp_add_inline_script()`): copy, link, target selector, edge (`right` on RTL sites), AJAX URL, action and nonce.
- The copy is plain text. Every node is built with jQuery `.text()` / `.attr()`, never by joining strings into HTML.
- WordPress places a pointer once, in page coordinates, but the admin menu stays in view while the page scrolls. The script keeps the pointer `position: fixed` and moves it with the menu link on scroll, resize and when the menu is folded.
- The look is fixed (`OfferPointer::css()`, printed inline after the `wp-pointer` stylesheet): default admin blue `#2271b1` on white, the button left of Dismiss. A user's admin colour scheme recolours core pointers and primary buttons, so each rule carries the pointer's own class `nx-offer-pointer`. Every selector starts with `.wp-pointer.nx-offer-pointer`, so no rule can restyle another plugin's pointer.

### Filter

| Filter | Default | Use |
| --- | --- | --- |
| `nx_offer_pointer_pro_active` | `NotificationX::is_pro()` | Return `true` to hide the pointer on a free site (and give back its slot). The tests use it to stand in for Pro. |

## Tests

| What | Where | Run |
| --- | --- | --- |
| Notice id, link, dates, copy, emoji, CSS following the id; pointer window, slot rules, screens, CSS scope, enqueue, dismissal, slot release | [tests/test-offer-campaign.php](../../tests/test-offer-campaign.php) | PHPUnit, `--filter Test_Offer_Campaign` |
| Pointer markup built from text, button row, placement maths, visibility | [tests/js/admin/offer-pointer.test.js](../../tests/js/admin/offer-pointer.test.js) | `npm run test:js` |
| Both surfaces in a real browser: copy, links, styling, screens, scrolling, colour scheme, Dismiss, the AJAX request | [tests/e2e/offer-campaign.js](../../tests/e2e/offer-campaign.js) | `node tests/e2e/offer-campaign.js`, see [tests/e2e/README.md](../../tests/e2e/README.md#offer-campaign) |

Manual test plan: [../qa-guide/bfcm-2026-offer-campaign.md](../qa-guide/bfcm-2026-offer-campaign.md).

## Starting the next campaign

1. **Pointer:** in `OfferPointer`, change `ID` (or the new pointer inherits the old dismissals), `START`, `EXPIRE`, `URL` and the copy in `content()`.
2. **Notice:** add a new block in `Admin::notices()` with a new id, just before `self::$cache_bank->create_account( $notices );`, reading `OfferPointer::START` / `EXPIRE` / `URL`. The previous campaign's block then holds stale constants: replace its three constants with the literal dates and link it had, or delete the block once it has expired. Append a CSS block for the new id. The [admin-notice-generator skill](../../.claude/skills/admin-notice-generator/SKILL.md) has the templates.
3. **Tests:** update the id, selector, link, dates and copy in `tests/test-offer-campaign.php`, `tests/js/admin/offer-pointer.test.js`, `tests/e2e/offer-campaign.js` and `tests/e2e/offer-campaign.php`.
4. Update the "Current campaign" table above and add a QA guide.
5. If `OfferPointer` moved or was renamed, run `composer dump-autoload --no-dev -o` (the classmap is committed) and restore `vendor/composer/platform_check.php` if only that file's wording changed.
