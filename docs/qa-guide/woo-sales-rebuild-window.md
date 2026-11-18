# Test Plan: WooCommerce sales window after a save or Regenerate

**Audience:** QA tester running the next NotificationX free release candidate (built from branch `fix/218-sales-entries-lost-after-resave`). Below, "the fixed version" means that release candidate. Rename this file to `free-<version>-woo-sales-rebuild-window.md` once the version number is fixed.

Issue: [#218](https://github.com/WPDevelopers/notificationx/issues/218).

## What changed

A WooCommerce sales notification keeps a stored copy of recent orders. Saving the notification, or **Regenerate** on the list page, deletes that copy and reads the orders again, from the start of the **Display From The Last** window until now.

The start of that window was calculated wrongly since 3.0.3. It was 12 hours off for half of the day:

| Window starts (UTC) | Before | Result before |
|---|---|---|
| 00:00 – 00:59 | Start moved 12 hours later | Orders from the first 12 hours of the window were not stored, so they never showed |
| 01:00 – 12:59 | Correct | None |
| 13:00 – 23:59 | Start moved 12 hours earlier | Up to 12 hours of older orders were stored. They did not show, because the front end checks the window again |

With a window in whole days, the window starts at the same time of day as the save. A save between 00:00 and 00:59 UTC therefore dropped orders.

In the fixed version the window starts exactly where the settings say, at any time of day.

Not changed: **Display The Last**, **Order Status**, how a new order is added when it is placed, and every source other than WooCommerce. The issue also reports that all conversions disappear after any save. That part did not reproduce and is not covered by this fix.

Automated coverage: `tests/test-woo-orders-query.php` (the order query) and `tests/e2e/woo-orders-rebuild.js` (real orders, REST save, Regenerate, visitor).

## Setup

1. Test site with WooCommerce active and one published product. `WP_DEBUG` and `WP_DEBUG_LOG` on.
2. Site with no Processing or Completed order from the last 3 days, so the counts below are exact.
3. WP-CLI access. The cases create orders with a chosen date, which the WooCommerce screens make slow.
4. Note the current UTC hour as `H` (`date -u +%H`, without a leading zero). The cases use a window of **1 day `H` hours**, which starts between 00:00 and 00:59 UTC yesterday. If the hour changes during the test, use the new `H` from then on.
5. Create three orders. Replace `PRODUCT` with the product id and `H` with the hour:
   ```
   wp eval '
   $h = H; $window = DAY_IN_SECONDS + $h * HOUR_IN_SECONDS;
   foreach ( array( "Recent" => 600, "Early" => $window - 6 * HOUR_IN_SECONDS, "Old" => $window + 6 * HOUR_IN_SECONDS ) as $name => $age ) {
       $o = wc_create_order( array( "status" => "processing" ) );
       $o->add_product( wc_get_product( PRODUCT ), 1 );
       $o->set_billing_first_name( $name ); $o->set_billing_last_name( "Buyer" );
       $o->set_date_created( time() - $age ); $o->save();
       echo $name, " = order ", $o->get_id(), PHP_EOL;
   }'
   ```
   - **Recent**: 10 minutes old.
   - **Early**: 6 hours inside the window, in its first 12 hours.
   - **Old**: 6 hours outside the window.
6. To see which orders a notification stored, replace `N` with the notification id. The number before the dash is the order id:
   ```
   wp db query "SELECT entry_key FROM wp_nx_entries WHERE nx_id = N"
   ```

Check the front end in a **new private window every time**. If you close a popup with its X, NotificationX hides that notification for the rest of the browser session, which looks like missing orders.

After each part, check `wp-content/debug.log`: **any PHP warning or notice from `notificationx` is a failure.**

## Part 1: the window

**T1. Reproduce on 3.3.8 (optional).** With 3.3.8 installed, create a **WooCommerce → Sales Notification**, Display The Last 20, Display From The Last **1 day `H` hours 0 minutes**. Publish. Run the query from step 6.
Expected on 3.3.8: only **Recent** is stored. **Early** is missing.

**T2. Save stores every order in the window.** On the fixed version, open the notification (or create it as in T1) and click **Update**. Run the query.
Expected: **Recent** and **Early** are stored, **Old** is not.
Fails if: Early is missing, or Old is stored.

**T3. A visitor sees both.** Open the home page in a new private window and wait for the popups to rotate.
Expected: one popup for "Recent B." and one for "Early B.". None for "Old B.".

**T4. Regenerate gives the same result.** On **NotificationX → All NotificationX**, use the row action **Regenerate** and confirm. Run the query.
Expected: same as T2.

**T5. Window that starts in the afternoon.** Set Display From The Last to **1 day `K` hours**, where `K` is `H + 6` (subtract 24 if it is 24 or more). The window now starts between 18:00 and 18:59 UTC. Click **Update** and run the query.
Expected: every stored order is newer than the window start. An order older than the window is never stored. (Which of the three orders are inside depends on `K`; compare each order date with "now minus 1 day `K` hours".)
Fails if: an order older than the window is stored.

**T6. Hours and minutes.** Set **0 days 0 hours 30 minutes** and click **Update**.
Expected: only **Recent** is stored and shown.

**T7. Default window.** Set **30 days 0 hours 0 minutes** and click **Update**.
Expected: all three orders are stored and shown.

## Part 2: nothing else changed

**T8. Display The Last below the number of orders.** Set Display The Last to **2**, window 30 days. **Update**. Run the query.
Expected: 2 orders stored, **Recent** and **Early** (the two newest).

**T9. Display The Last above 10.** Run the command from step 5 four more times, which adds 12 orders. Set Display The Last to **20**, window 30 days. **Update**.
Expected: all 15 orders are stored.

**T10. Order Status.** Set one of the orders to **On hold** in WooCommerce. Open the notification, leave Order Status at its default, **Update**.
Expected: the On hold order is not stored. Select **On hold** in Order Status and **Update**: it is stored, and Processing orders are not.
Set Order Status back to the default afterwards.

**T11. New order.** Place an order from the shop as a customer.
Expected: its popup shows on the next page load in a new private window, without saving the notification.

**T12. Sales type with the WooCommerce source.** Create **Sales Notification → WooCommerce** (the older type), 30 days, Display The Last 20. Publish.
Expected: the same orders as T9 are stored and shown.

**T13. With NotificationX Pro.** Activate the current NotificationX Pro and repeat T2 and T4.
Expected: same results.

## Cleanup

Delete the test notifications and the test orders (WooCommerce → Orders → move to Trash → empty Trash).

## Report

List the case ids that failed, with the UTC time of the save, the window you set, and the output of the query in step 6.
