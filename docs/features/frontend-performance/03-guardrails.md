# Frontend Performance: Guardrails

The 3.3.1 bundle grew to 1.25 MB because four small frontend imports reached into admin code. Nothing failed, and nobody noticed. These rules and checks make that visible.

## Rules

1. **Frontend code imports only from `frontend/`, `shared/` and `icons/`** (all under `nxdev/notificationx/`). Never from `core/`, `hooks/`, `admin/`, `fields/` or `components/`.
2. **Code needed by both admin and frontend goes in [shared/helpers.ts](../../../nxdev/notificationx/shared/helpers.ts),** and that file has no imports. The admin module re-exports it, so admin imports don't change.
3. **Admin-only packages never reach the frontend:** `sweetalert2`, `react-toastify`, `moment-timezone`, `@wordpress/date`, `@wordpress/components`, `@wordpress/data`, `quickbuilder`, `react-router(-dom)`, `react-select`.
4. **`frontend.js` stays under its budget** (550 KB raw; ~445 KB after B1). Raise the budget in [bin/check-frontend-bundle.js](../../../bin/check-frontend-bundle.js) only on purpose, in its own commit, with the reason.

## Checks

| Command | What it checks | When |
| --- | --- | --- |
| `npm run test:js` | Jest suites in [tests/js/](../../../tests/js/), including [frontend-import-boundary.test.js](../../../tests/js/frontend-import-boundary.test.js), which walks the import graph from every frontend entry and enforces rules 1–3 | Every change to `nxdev/` |
| `npm run check:bundle` | The built `frontend.js` and `crossSite.js` against the size budget and three admin-library markers (`swal2-container`, `Toastify__`, `Africa/Abidjan`) | After `npm run frontend`. Runs automatically inside `npm run release`. |
| PHPUnit | Includes [test-status-updated-hook.php](../../../tests/test-status-updated-hook.php) | Every PHP change |
| `node tests/e2e/compare-frontend-bundles.js` | Old vs new bundle in a real site: DOM, pixels, consent side effects, errors | Before releasing any change to the frontend runtime or its CSS |

### When the import-boundary test fails

It prints each offending edge, for example:

```
nxdev/notificationx/frontend/core/Pressbar.tsx -> nxdev/notificationx/core/functions.ts
```

Move the helper you need into `shared/helpers.ts` (dependency-free), re-export it from the admin module, and import it from `shared/` in the frontend.

### When `check:bundle` fails

- **Marker found:** some frontend import reaches admin code. Run `npm run test:js`; the boundary test names the edge.
- **Over budget, no marker:** find out what grew. Build with stats and group modules by package:

  ```bash
  npx webpack --config webpack.frontend.config.js --mode production --json=/tmp/stats.json
  ```

  Note that a plain `webpack` run keeps `DependencyExtractionWebpackPlugin` and externalizes React and `@wordpress/*`, so the numbers come out smaller than the shipped bundle. The shipped build uses `--webpack-no-externals`; remove that plugin from the config to reproduce it exactly.

## Running the tests

JS unit tests:

```bash
npm run test:js
```

The Jest config is [tests/js/jest.config.js](../../../tests/js/jest.config.js). It extends `@wordpress/jest-preset-default` and compiles the ESM-only `memize` that `@wordpress/i18n` ships.

PHPUnit: `composer.json` has no `require-dev`, so `vendor/bin/phpunit` doesn't exist. Use a PHPUnit **9.x** phar and the WordPress core test library (with `yoast/phpunit-polyfills` 1.1):

```bash
WP_TESTS_DIR=/path/to/wordpress-tests-lib php phpunit-9.phar -c phpunit.xml.dist
```

Use a throwaway database for the test suite. `bin/install-wp-tests.sh` drops the database it's given.

E2E comparison: see [tests/e2e/README.md](../../../tests/e2e/README.md).
