# Test Plan: Cookie Scanner stops polling after a failed scan (#224)

**Audience:** QA tester checking the free release candidate built from branch `fix/224-cookie-scanner-polling` (cut from `dev`). Pro has no changes; test with Pro active and inactive.

## What changed

Before this fix, the Cookie Scanner stopped polling only on a `completed` status or a JavaScript error. A failed scan, an unreachable scanner API or an invalid scan ID left the button on "Scanning…" forever, with **Scan Now** disabled, a status request every 5 seconds, and requests piling up while one was still waiting.

| Where | Change | What you can see |
|---|---|---|
| Builder (`CookieScanner.tsx`) | One status request at a time, 5 seconds after the previous reply | In the Network tab, a new `scan/status` request starts only after the last one finished |
| Builder | Stops on a `failed`/`error` status or any 4xx reply | Error toast "Scan failed. Please try again.", **Scan Now** enabled again |
| Builder | Stops after 3 bad replies in a row (network error, 5xx, not JSON, no status) | Same toast after about 15 seconds |
| Builder | Stops 10 minutes after the scan started | Error toast "The scan took too long. Please try again." |
| Builder | A failed scan is not added to the scan history; empty entries in an old saved history are skipped | The History popup opens on campaigns saved after a failed scan |
| Server (`Scanner.php`) | `scan/status` checks `scan_id` before calling the scanner API: missing, blank or over 128 characters gives **400** | No call to `api.notificationx.com` for a bad ID |
| Server | The scanner API unreachable, a non-2xx reply or a body that is not JSON gives **502** `{"data":{"status":"failed","message":"Could not reach the cookie scanner service. Please try again later."}}` | Before: HTTP 200 with a nested object |

A completed scan, the scan count (`nx_scan_count`), the 5-scan limit and Pro's unlimited scans are unchanged. Automated coverage: `tests/test-cookie-scanner.php` (PHPUnit) and `tests/js/admin/cookie-scanner.test.js` (Jest).

## Setup

1. Test site with the free release candidate. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. **Don't start a real scan.** Free sites get 5 scans, and a local site can't be reached by the scanner API anyway. Every case below fakes the scanner.
3. Go to **NotificationX → Add New**, choose **Cookie Notice**, open the **Manager** tab. The scanner is in the **Scan Cookies** section. Don't publish or save the campaign during these tests.
4. Open DevTools (Cmd+Opt+I / Ctrl+Shift+I). Keep the **Console** and **Network** (filter `notificationx/v1/scan`) tabs at hand.
5. Paste this stub into the Console and press Enter (type `allow pasting` first if Chrome asks). It fakes the start of the scan, so no real scan begins, and fakes the status replies according to `nx224`. Faked requests don't show in the Network tab; the stub logs each one to the Console. Reloading the page removes the stub.

```js
(() => {
  const realFetch = window.fetch.bind(window);
  const json = (body, status = 200) =>
    new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
  // 'failed', '404', 'completed', or 'server' (status requests go to the real site).
  window.nx224 = 'failed';
  window.fetch = (url, opts) => {
    const u = String(url);
    if (u.endsWith('notificationx/v1/scan')) {
      console.log('[nx224] start: faked');
      return Promise.resolve(json({ success: true, data: { scan_id: 'test-224', status: 'pending' } }));
    }
    if (u.endsWith('notificationx/v1/scan/status') && window.nx224 !== 'server') {
      console.log('[nx224] status poll: faked', window.nx224, new Date().toLocaleTimeString());
      if (window.nx224 === '404') {
        return Promise.resolve(json({ data: { status: 'failed', message: 'Invalid scan ID' } }, 404));
      }
      if (window.nx224 === 'completed') {
        return Promise.resolve(json({ data: {
          status: 'completed', last_scan_date: new Date().toISOString().slice(0, 19).replace('T', ' '),
          cookies: { t: { name: 'nxqa224', domain: location.hostname, expires: -1 } },
          stats: { total_cookies: 1 }, category_count: 0,
        } }));
      }
      return Promise.resolve(json({ data: { status: 'failed', message: 'Test failure' } }));
    }
    return realFetch(url, opts);
  };
})();
```

To start a scan in each case: click **Scan Now**, then **Scan Now** in the "Ready to start scanning?" popup.

## Cases

**T1. Failed status.** Stub as pasted (`nx224 = 'failed'`). Start a scan.
Expected: one "status poll: faked" line in the Console after about 5 seconds, then the error toast **"Scan failed. Please try again."**. **Scan Now** is enabled again and no more poll lines appear.
Fails if: the button stays on "Scanning…", or poll lines keep appearing every 5 seconds (the old behaviour).

**T2. Scanner API unreachable (real server path).** Add `define( 'WP_HTTP_BLOCK_EXTERNAL', true );` to `wp-config.php` above the "That's all, stop editing!" line. Reload the builder, paste the stub, run `nx224 = 'server'` in the Console, start a scan.
Expected: in the Network tab, three `scan/status` requests about 5 seconds apart, each **502** with `{"data":{"status":"failed","message":"Could not reach the cookie scanner service. Please try again later."}}`. Then the toast "Scan failed. Please try again." and **Scan Now** enabled again. Each request starts only after the previous one finished.
Fails if: the replies are HTTP 200 with a nested `"status":500`, or polling never stops.
Undo: remove the line from `wp-config.php` (it also blocks updates and licence checks).

**T3. Network error.** Paste the stub, run `nx224 = 'server'`. In DevTools, Cmd+Shift+P → "Show Network request blocking", add `*notificationx/v1/scan/status*` and make sure blocking is on. Start a scan.
Expected: three requests shown as `(blocked:devtools)`, then the toast "Scan failed. Please try again." and **Scan Now** enabled again.
Fails if: the toast reads "Scan failed! undefined." (the old behaviour), or polling never stops.
Undo: remove the pattern, reload.

**T4. Invalid scan ID (404).** Paste the stub, run `nx224 = '404'`. Start a scan.
Expected: one poll line, then the toast "Scan failed. Please try again." and **Scan Now** enabled again.
Fails if: polling continues after the 404 (the old behaviour).

**T5. Completed scan still works.** Paste the stub, run `nx224 = 'completed'`. Start a scan.
Expected: the toast "Scan complete! Your results are now available.", **Scan Now** enabled again, a cookie named `nxqa224` in the **Uncategorized** cookie list, and one new row in **History** (if the button is enabled). No more poll lines.
Reload the page without saving; the test cookie and history row are gone.

**T6. Start fails without a scan.** No stub. Add `WP_HTTP_BLOCK_EXTERNAL` as in T2, reload, start a scan.
Expected: the toast "Scan failed. Please try again." at once, **Scan Now** enabled again, no `scan/status` requests. The scanner API is not reached, so no real scan starts.
Undo: remove the line from `wp-config.php`.

**T7. Scan count unchanged.** After T1–T6, check that the "x of 5 free scans used" text (Pro inactive) shows the same number as before testing.
Fails if: the number went up. None of the cases above completes a scan on the server.

After each case, check `wp-content/debug.log`: **any PHP fatal error, warning or notice from `notificationx` is a failure.**
