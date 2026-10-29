# QA Guides

Manual test plans for NotificationX (free) releases. Each guide says what changed, how to set up the site, and the expected result for each case. They complement the automated tests: PHPUnit in [../../tests/](../../tests/) and Jest in [../../tests/js/](../../tests/js/) (`npm run test:js`).

| Guide | Covers |
| --- | --- |
| [free-3.3.3-frontend-hooks.md](free-3.3.3-frontend-hooks.md) | Step 4 of the free/Pro code separation: the `wp-hooks` dependency, frontend JS hooks around Discount Alert / Cart Peek / CTA buttons / mobile layout, `window.nxFrontendRuntime`, the Cross Domain Notice, and the old-Pro update notice. Nothing should change visibly. |
| [free-3.3.4-pro-3.2.4-code-separation.md](free-3.3.4-pro-3.2.4-code-separation.md) | Steps 5 and 7 of the free/Pro code separation: Pro renders Discount Alert, Cart Peek, Inline and Flashing Tab, free no longer contains that code, the Cross Domain Notice loads Pro's script, and free with no Pro or an older Pro. |
| [popup-submit-stored-xss.md](popup-submit-stored-xss.md) | Security fix for the unauthenticated stored XSS through `popup-submit` (Wordfence, <= 3.3.3): the submission gate, inline shortcode / block escaping, Reviews name decoding, and no regressions for Popup, Exit Intent and the Elementor form. |
| [integration-legacy-key.md](integration-legacy-key.md) | Security fix for the Zapier / IFTTT webhook route: the `md5(home_url())` legacy key is accepted only during the window that follows the update introducing the random key, windows left open by 3.2.11 – 3.3.5 are closed on update, both REST routes, the admin notice, and no PHP warning for an unknown notification id. |
| [preview-access-gate.md](preview-access-gate.md) | Security fix for the builder preview (Patchstack): preview requests need a nonce and the `edit_notificationx` capability, the builder and Pro's Build with AI send it, Gutenberg / Elementor bars render only from their own published posts, and the Inline block in a block template. |
| [deactivation-feedback-form.md](deactivation-feedback-form.md) | Layout and behaviour fix for the "Sorry to see you go" dialog: overlay above the admin menu and admin bar, Better Payment look, no loading state on submit, redirect when the request fails, narrow screens. |

## Conventions

- One guide per release or feature area. Name the file `<plugin>-<version>-<area>.md`.
- Number every case (`T1`, `T2`, …) so the tester can report results as a list of case ids.
- Each case gives the starting state, the steps, the expected result, and when it fails.
- Put any temporary test code (for example an mu-plugin) in the guide in full, and say when to delete it.
- Add a row to the table above when you add a guide.
