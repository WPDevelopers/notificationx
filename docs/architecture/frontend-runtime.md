# Frontend Runtime

> **Status:** stub — outline only.

How notifications are rendered on the public site.

## Enqueue & render
_TODO — [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) enqueues the runtime and localizes data; per-Type `FrontEnd.php` routing._

## The popup/bar/exit-intent runtime
_TODO — React-driven runtime built from [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/) via [../../webpack.frontend.config.js](../../webpack.frontend.config.js)._

## In-builder preview
_TODO — [../../includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php) powers the live preview inside the admin builder._

## The dual-build desync trap
_TODO — two webpack builds (admin + frontend) and three runtime contexts (admin builder, frontend runtime, Gutenberg blocks). Popup display changes usually need edits in BOTH `nxdev/notificationx/frontend/` and the matching PHP Type class, or the design silently desyncs._

## Source
- [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)
- [../../includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php)
- [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/)
