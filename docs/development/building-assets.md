# Building Assets

> **Status:** stub — outline only. Authoritative command list is in [../../CLAUDE.md](../../CLAUDE.md).

All JS/asset builds use `@wordpress/scripts` (wp-scripts). Node version is pinned in [../../.nvmrc](../../.nvmrc).

## Commands

| Command | Builds |
| --- | --- |
| `npm run start` | Admin (`nxdev/`) **and** frontend bundles in parallel (watch). |
| `npm run admin` / `admin-watch` | Admin React app only ([../../webpack.config.js](../../webpack.config.js)). |
| `npm run frontend` / `frontend-watch` | Frontend popup/bar bundles ([../../webpack.frontend.config.js](../../webpack.frontend.config.js)). |
| `npm run blocks` / `bb` | Gutenberg blocks ([../../webpack.blocks.config.js](../../webpack.blocks.config.js)). |
| `npm run build` | Admin + frontend production (NOT blocks). |
| `npm run release` | `build` + `bb` + `pot`. |
| `npm run zip` | `release` + `wp dist-archive`. |
| `npm run pot` | Regenerate `languages/notificationx.pot`. |
| `npm run up` | Reinstall `quickbuilder` from `github:WPDevelopers/quickbuilder#notificationx`. |

## Output location
_TODO — builds land in `nxbuild/` (referenced via `NOTIFICATIONX_DEV_ASSETS`), not `assets/`. Register handles against that path._

> **Note:** this project has a watcher / the maintainer builds admin assets themselves — do not run `npm run admin` unprompted.

## Source
- [../../package.json](../../package.json)
- [../../CLAUDE.md](../../CLAUDE.md)
