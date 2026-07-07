# Creating a Gutenberg Block

NotificationX ships two Gutenberg blocks — an **Inline Notification** block that embeds a campaign into post content, and a **Countdown Timer** block. Both are *dynamic* (server-rendered): the editor stores attributes only, and PHP produces the frontend markup through a `render_callback`. There are **no `block.json` files** here — registration is split between a JS `registerBlockType()` call and a PHP `register_block_type()` call.

## Where blocks live

Everything is under [../../blocks/](../../blocks/):

| Path | Purpose |
| --- | --- |
| [`blocks/Blocks.php`](../../blocks/Blocks.php) | PHP registrar — hooks `init`, registers every block type, enqueues scripts/styles, defines the `render_callback`s. |
| `blocks/notificationx/` | Inline Notification block source (`index.jsx` + `components/`) and its built `index.js` / `index.asset.php`. |
| `blocks/countdown/` | Countdown Timer block source (`index.jsx` + `components/`) and its built bundle. |
| `blocks/controls/` | Shared editor controls bundle (`controls/dist/index.js` + `index.css`), registered as the `notificationx-block-controls` handle and used as a dependency by both block editor scripts. |
| `blocks/style-handler/` | `StyleHandler` — editor preview / device-type plumbing, instantiated from `Blocks.php`. |

The two blocks are built by separate webpack configs:

- [../../webpack.blocks.config.js](../../webpack.blocks.config.js) — entry `blocks/notificationx/index.jsx`, output `blocks/notificationx/index.js`. Build with `npm run bb` (or `npm run blocks` to watch).
- [../../webpack.countdown.config.js](../../webpack.countdown.config.js) — entry `blocks/countdown/index.jsx`, output `blocks/countdown/index.js`. Build with `npm run cd` (or `npm run cd-watch` to watch).

Both are included in `npm run release`. Note that `npm run build` builds admin + frontend only — it does **not** build blocks, so run `bb` / `cd` separately after touching block source.

## Anatomy of a block

**JS side** ([`blocks/notificationx/index.jsx`](../../blocks/notificationx/index.jsx)). The bundle calls `registerBlockType()` with `apiVersion: 2`, `attributes` (imported from `components/attributes`), an `icon`, and an `edit` component (`components/edit.js`, with UI in `components/inspector.js`). There is **no `save`** — the blocks are dynamic, so `save` is omitted and the frontend HTML comes from PHP. The Inline block registers two types: the visible `notificationx-pro/notificationx` ("Inline Notification") and its child `notificationx-pro/notificationx-render`.

**PHP side** ([`blocks/Blocks.php`](../../blocks/Blocks.php) → `notificationx_block_init()`, hooked on `init`). For each block it:

1. `include`s the generated `index.asset.php` manifest (produced by wp-scripts) to get the exact dependency list + version hash;
2. `wp_register_script()` / `wp_register_style()` the editor bundle (declaring `notificationx-block-controls` as a dependency), plus the frontend script;
3. `register_block_type( '<name>', [ 'editor_script' => ..., 'editor_style' => ..., 'render_callback' => [...], 'attributes' => [...] ] )`.

The three registered block types and their callbacks:

| Block name | Registered attributes | `render_callback` |
| --- | --- | --- |
| `notificationx-pro/notificationx` | `nx_id`, `blockId`, `product_id` | `notificationx_render_callback()` — renders via the `[notificationx_inline]` shortcode. |
| `notificationx-pro/notificationx-render` | `nx_id`, `blockId`, `product_id`, `post_type` | `gutenberg_examples_dynamic_render_callback()` — inline render helper (used inside the block editor / templates). |
| `notificationx/countdown` | server schema from `countdown_block_attributes()` | `countdown_render_callback()` — mirrors the Elementor `CountdownWidget` markup. |

The countdown block's server-side attribute defaults **must** stay in sync with the JS defaults in `blocks/countdown/components/attributes.js` — Gutenberg omits attributes left at their default from saved markup, so the PHP `wp_parse_args()` defaults are what the frontend actually falls back to (this constraint is called out in `Blocks.php`).

## Build & register

To add a new block, follow the existing pattern (there is no `block.json` scaffold to copy):

1. **Create source** under `blocks/<yourblock>/` — an `index.jsx` that calls `registerBlockType('<namespace>/<name>', { apiVersion: 2, attributes, edit, icon })`, plus a `components/` folder (`attributes.js`, `edit.js`, `inspector.js`).
2. **Add a webpack config** modeled on `webpack.countdown.config.js` (entry → your `index.jsx`, output → `blocks/<yourblock>/index.js`) and a matching `npm` script, or fold the entry into an existing config.
3. **Build** with your new script; wp-scripts emits `index.js` + `index.asset.php`.
4. **Register in PHP** inside `Blocks::notificationx_block_init()`: `include` the generated `index.asset.php`, `wp_register_script/style()` with those dependencies (add `notificationx-block-controls` if you reuse the shared controls), then `register_block_type()` with your `render_callback` and `attributes`.

**Enqueuing note:** block assets registered in `Blocks.php` are loaded from the block folders via `plugins_url(..., __FILE__)` (with `filemtime()` cache-busting) — they are built in place and are **not** subject to the `nxbuild/` ↔ `assets/` dev/prod switch that admin and frontend bundles use. The countdown block's frontend layout CSS is the exception: it is loaded from `NOTIFICATIONX_PUBLIC_URL` (`assets/public/css/nx-countdown.css`), the same file the Elementor countdown widget uses.

`Blocks::get_instance()` is bootstrapped from [`includes/NotificationX.php`](../../includes/NotificationX.php); block registration itself is skipped early if `register_block_type()` is unavailable.

## Source
- [../../blocks/Blocks.php](../../blocks/Blocks.php) — registration, enqueue, and render callbacks.
- [../../blocks/notificationx/index.jsx](../../blocks/notificationx/index.jsx) — JS `registerBlockType` example.
- [../../webpack.blocks.config.js](../../webpack.blocks.config.js) · [../../webpack.countdown.config.js](../../webpack.countdown.config.js)
- [building-assets.md](building-assets.md) — the `bb` / `cd` / `release` build commands.
