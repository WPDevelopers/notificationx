# QA Guides

Manual test plans for NotificationX (free) releases. Each guide says what changed, how to set up the site, and the expected result for each case. They complement the automated tests: PHPUnit in [../../tests/](../../tests/) and Jest in [../../tests/js/](../../tests/js/) (`npm run test:js`).

| Guide | Covers |
| --- | --- |
| [free-3.4.0-frontend-hooks.md](free-3.4.0-frontend-hooks.md) | Step 4 of the free/Pro code separation: the `wp-hooks` dependency, frontend JS hooks around Discount Alert / Cart Peek / CTA buttons / mobile layout, `window.nxFrontendRuntime`, the Cross Domain Notice, and the old-Pro update notice. Nothing should change visibly. |

## Conventions

- One guide per release or feature area. Name the file `<plugin>-<version>-<area>.md`.
- Number every case (`T1`, `T2`, …) so the tester can report results as a list of case ids.
- Each case gives the starting state, the steps, the expected result, and when it fails.
- Put any temporary test code (for example an mu-plugin) in the guide in full, and say when to delete it.
- Add a row to the table above when you add a guide.
