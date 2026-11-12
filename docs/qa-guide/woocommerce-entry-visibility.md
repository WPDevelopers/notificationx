# Test Plan: WooCommerce entries and product state

**Audience:** QA tester running the NotificationX (free) release candidate that carries this change.
**Release under test:** the next free release built from branch `fix/214-builder-field-names-cache` (cut from `dev`). NotificationX Pro is not changed; test with the current Pro active, because Pro's WooCommerce sources use the same check.

## What changed

A WooCommerce sales notification does not show a sale whose product was deleted, trashed or unpublished. To decide that, the plugin loaded the product for every stored sale, on every page view. A notification stores up to 100 sales, usually of the same few products, so the same product was loaded many times.

| Before | After |
|---|---|
| The product is loaded once for each stored sale | Each product is loaded once per page request |
| Sales of deleted, trashed, draft or private products are hidden | Unchanged |

Nothing should look different. On a development machine, with 5 notifications of 100 sales each, the server part of the request went from 117 ms to 56 ms.

Automated coverage: `tests/test-woocommerce-entry-visibility.php` (PHPUnit), `tests/e2e/woocommerce-entry-visibility.js` (real products and requests against a site). The change is tracked in issue #214.

## Setup

1. Test site with WooCommerce, the free release candidate and Pro. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`, and empty `wp-content/debug.log`.
2. Three published products: `P1`, `P2`, `P3`.
3. Place orders so there are several recent sales of each product (at least three each). Set them to Processing or Completed.
4. A **Sales Notification** with source **WooCommerce**, enabled, shown everywhere, **Display From** long enough to include the orders. Note which product names appear when you watch the home page for a full cycle.

## Cases

**T1. All three products appear.** Open the home page logged out and watch a full cycle of the notification.
Expected: sales of `P1`, `P2` and `P3` all appear.

**T2. Draft product.** Set `P1` to **Draft**. Reload the home page logged out and watch a full cycle.
Expected: no sale of `P1` appears; `P2` and `P3` still do.

**T3. Published again.** Set `P1` back to **Published**. Reload.
Expected: sales of `P1` appear again on the first reload, without clearing any cache. (A page cache or CDN in front of the site can delay this; test with it off.)

**T4. Trashed product.** Move `P2` to the trash. Reload.
Expected: no sale of `P2` appears. Restore `P2`: its sales appear again.

**T5. Private product.** Set `P3` to **Private**. Reload logged out.
Expected: no sale of `P3` appears. Set it back to Published.

**T6. Deleted product.** Create a product `P4`, place one order for it, see its sale in the notification, then delete `P4` permanently.
Expected: the sale of `P4` no longer appears, and `debug.log` has no new entry.

**T7. Other WooCommerce sources (Pro).** If the site has another notification built on WooCommerce orders from Pro (for example a Growth Alert with the WooCommerce source), repeat T2 and T3 for it.
Expected: same results.

**T8. Two WooCommerce notifications on one page.** Enable a second Sales Notification with the same source. Repeat T2.
Expected: `P1` is hidden in both.

**T9. No errors.** After all cases, `debug.log` has no entry from NotificationX.

## Automated checks

Run these on the development site, not on the QA site:

```
vendor/bin/phpunit --filter 'Test_WooCommerce_Entry_Visibility'
node tests/e2e/woocommerce-entry-visibility.js
```

Expected: PHPUnit reports no failures; the e2e script prints `All checks passed.` (or `SKIP` on a site without WooCommerce and two published products). It creates one temporary notification and one temporary product, and deletes both.

## Reporting

Report results as a list of case ids (`T1 pass, T3 fail: P1 still hidden after two reloads`).
