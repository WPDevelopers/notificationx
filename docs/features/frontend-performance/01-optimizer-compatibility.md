# Optimizer Compatibility

For caching and optimizer plugins (xSpeed, WP Rocket, LiteSpeed, and others) that delay or defer JavaScript, and for anyone answering their questions.

## The short version

Delay NotificationX like any other script, **unless the page renders a NotificationX GDPR notice**. Only a GDPR notice needs to load before the first interaction.

## How to tell whether a page runs a GDPR notice

On every page where NotificationX runs, [FrontEnd::footer_scripts()](../../../includes/FrontEnd/FrontEnd.php) prints the notifications for that page into the HTML:

```html
<script data-no-optimize="1">
  (function() {
    window.notificationXArr = window.notificationXArr || [];
    window.notificationXArr.push({"global":["14552"],"active":["14595"],"pressbar":["14594"],"gdpr":[],"popup":[],"exit_intent":[],"total":4, ...});
  })();
</script>
```

- The block carries `data-no-optimize="1"`, so optimizers see it unchanged in the output buffer.
- `gdpr` lists the IDs of GDPR notices that pass their display rules on **this** page. If it's empty, no banner renders here.
- A page can have more than one `push()`. NotificationX Pro's cross-domain notices add a second block, printed as a minified object literal (for example `"cross":!0`), which may have no `gdpr` key at all.

A detection function that handles all of these:

```php
/**
 * True when the page renders a NotificationX GDPR/cookie notice.
 */
function optimizer_nx_has_active_gdpr( string $html ): bool {
    if ( strpos( $html, 'notificationXArr' ) === false ) {
        return false; // NotificationX is not running on this page.
    }
    // "gdpr":[ followed by anything other than an immediate ].
    return (bool) preg_match( '/["\']?gdpr["\']?\s*:\s*\[\s*[^\]\s]/', $html );
}
```

**Fail safe.** If the HTML can't be parsed, keep NotificationX eager, which is the conservative behaviour. A detection bug must never silently turn off a real consent banner.

## Page cache: when to purge

The `gdpr` list is part of the cached HTML. After a site owner turns on a GDPR notice, cached pages still say `"gdpr":[]` until they're purged, and during that window the banner is delayed. Purge on:

| Hook | Fires when | Args |
| --- | --- | --- |
| `nx_saved_post` | A notification is created or edited ([PostType.php:218](../../../includes/Core/PostType.php#L218)) | `$post`, `$data`, `$nx_id` |
| `nx_status_updated` | A notification is enabled or disabled: list-page toggle and bulk actions ([PostType.php:267](../../../includes/Core/PostType.php#L267)). Added in B1. | `$nx_id`, `$enabled`, `$source` |
| `nx_delete_post` | A notification is deleted ([PostType.php:531](../../../includes/Core/PostType.php#L531)) | `$post_id`, `$post` |

`nx_status_updated` passes `$source` (for example `gdpr_notification`) when the caller sent it, so a plugin can purge only on GDPR changes. When disabling, the source can be `''`; purge in that case.

## Which files to exclude

Today (3.3.x) the GDPR banner is part of `assets/public/js/frontend.js`. When a page has an active GDPR notice, exclude that file (handle `notificationx-public`) from Delay JS.

After phase B3 ([04-roadmap.md](04-roadmap.md)), the banner will ship as its own small file with a fixed handle. Optimizers should then exclude only that file. This doc will be updated with the name.

## Our reply to xSpeed (drafted 2026-09-22)

- embedpress.com had `"gdpr":[]`, so the eager load bought no compliance. With the rule above, the site returns to its measured "delayed" score (95–96).
- We're removing the admin code that made up most of the bundle ([02-b1-changes.md](02-b1-changes.md)), and we plan the consent loader split (B3).
- `platform.js` loading for every notification was a bug; fixed in B1.
- The CLS is more likely the pressbar's `body` padding than the GIF; we asked for GTmetrix's CLS breakdown.
- Deferring `/notice/` would delay the GDPR banner, so it waits for B3.
