# Building Assets

All JS/CSS builds use `@wordpress/scripts` (wp-scripts) driven by four webpack configs. The Node version is pinned in [../../.nvmrc](../../.nvmrc) (Node **16**) — run `nvm use` before building. PHP tooling (Composer, PHPUnit, PHPCS) is covered at the end.

## Commands

Defined in [`package.json`](../../package.json) `scripts`:

| Command | Runs | Builds |
| --- | --- | --- |
| `npm run admin` | `wp-scripts build` | Admin React app (production) — [../../webpack.config.js](../../webpack.config.js), entry `nxdev/index.tsx`. |
| `npm run admin-watch` | `wp-scripts start` | Admin app in watch mode (development). |
| `npm run frontend` | `wp-scripts build --config webpack.frontend.config.js --webpack-no-externals` | Frontend popup/bar runtime (production) — [../../webpack.frontend.config.js](../../webpack.frontend.config.js). Entries: `frontend`, `crossSite`, `flashing-tab`. |
| `npm run frontend-watch` | `wp-scripts start --config webpack.frontend.config.js --webpack-no-externals` | Frontend runtime in watch mode. |
| `npm run start` | `admin-watch & frontend-watch` | Admin **and** frontend together, both in watch mode. |
| `npm run build` | `admin && frontend` | Admin + frontend production build. **Does not** build blocks or countdown. |
| `npm run bb` | `wp-scripts build --config webpack.blocks.config.js` | Gutenberg "Inline Notification" block — [../../webpack.blocks.config.js](../../webpack.blocks.config.js). |
| `npm run blocks` | `wp-scripts start --config webpack.blocks.config.js` | The block in watch mode. |
| `npm run cd` | `wp-scripts build --config webpack.countdown.config.js` | Countdown Timer block — [../../webpack.countdown.config.js](../../webpack.countdown.config.js). |
| `npm run cd-watch` | `wp-scripts start --config webpack.countdown.config.js` | Countdown block in watch mode. |
| `npm run release` | `build && bb && cd && pot` | Full production build: admin + frontend + both blocks + translation template. |
| `npm run zip` | `release && wp dist-archive .` | `release` then packages a distributable zip. |
| `npm run pot` | `wp i18n make-pot . languages/notificationx.pot --exclude='nxbuild'` | Regenerates the translation template (excludes `nxbuild/`). |
| `npm run up` | `npm install github:WPDevelopers/quickbuilder#notificationx` | Reinstalls the `quickbuilder` form engine from its GitHub branch (do not bump from npm). |
| `npm run packages-update` | `wp-scripts packages-update` | Updates `@wordpress/*` packages. |

> **Note:** this project has a watcher and the maintainer builds admin assets themselves — do not run `npm run admin` / `admin-watch` unprompted.

## Output location

The output directory depends on whether wp-scripts runs in production or development mode (`build` sets `NODE_ENV=production`, `start` does not):

- **Admin & frontend bundles** are written to `assets/` on a production `build`, and to `nxbuild/` in watch mode (`start`). This branch is explicit in both webpack configs:
  ```js
  path: path.resolve(process.cwd(), isProduction ? "assets" : "nxbuild"),
  ```
  Admin emits under `admin/js` + `admin/css`; frontend emits under `public/js` + `public/css`.
- **Block bundles** are written **in place** regardless of mode: the block config outputs `blocks/notificationx/index.js` and the countdown config outputs `blocks/countdown/index.js` (each alongside a generated `index.asset.php` dependency manifest).

At runtime the plugin decides which copy to load through [`Helper::file()`](../../includes/Core/Helper.php): when the `NX_DEBUG` constant is defined **and** the requested file exists under `nxbuild/`, it serves from the dev build; otherwise it falls back to the committed `assets/` build. The path constants are defined in [../../notificationx.php](../../notificationx.php):

```php
define( 'NOTIFICATIONX_ASSETS',          NOTIFICATIONX_URL  . 'assets/' );   // committed production build
define( 'NOTIFICATIONX_DEV_ASSETS',      NOTIFICATIONX_URL  . 'nxbuild/' );  // watch-mode build
define( 'NOTIFICATIONX_DEV_ASSETS_PATH', NOTIFICATIONX_PATH . 'nxbuild/' );
```

So during active development, run a watch task (`npm run start`) and define `NX_DEBUG` to load the live `nxbuild/` output; ship the `assets/` build for distribution. `assets/` is committed source/static plus the production build target — it is *not* wiped by watch mode. Distribution exclusions live in `.distignore` (consumed by `wp dist-archive`); update it if you add top-level dev-only files.

## PHP tooling

From the plugin root (see [../../CLAUDE.md](../../CLAUDE.md) for details):

- `composer install` — installs PHP libraries (`lib-settings`, `query-builder`, `wp-notice`) from the VCS repos declared in [../../composer.json](../../composer.json). Run `composer dump-autoload` after adding new classes so the classmap picks them up.
- `vendor/bin/phpunit` — runs the test suite (config [../../phpunit.xml.dist](../../phpunit.xml.dist), bootstrap `tests/bootstrap.php`).
- `vendor/bin/phpcs --standard=phpcs.xml` — coding-standards check ([../../phpcs.xml](../../phpcs.xml)).

## Source
- [../../package.json](../../package.json) — the authoritative `scripts` list.
- [../../webpack.config.js](../../webpack.config.js) · [../../webpack.frontend.config.js](../../webpack.frontend.config.js) · [../../webpack.blocks.config.js](../../webpack.blocks.config.js) · [../../webpack.countdown.config.js](../../webpack.countdown.config.js)
- [../../includes/Core/Helper.php](../../includes/Core/Helper.php) — `Helper::file()` dev/prod asset resolution.
- [../../notificationx.php](../../notificationx.php) — asset path constants.
- [../../CLAUDE.md](../../CLAUDE.md)
