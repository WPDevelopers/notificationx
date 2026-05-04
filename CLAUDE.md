# NotificationX — CLAUDE.md

For full agent guidance on this project, see [AGENTS.md](AGENTS.md).

## Quick Reference

- **Plugin entry:** `notificationx.php`
- **PHP source:** `includes/` (namespace root `NotificationX\`)
- **React/TS source:** `nxdev/` (never edit compiled `assets/` directly)
- **REST namespace:** `notificationx/v1`
- **Version:** 3.2.6

## Build

```bash
npm run start      # watch mode (admin + frontend)
npm run build      # production build
npm run release    # full build + blocks + POT
npm run zip        # release + distributable ZIP
```

## PHP

```bash
composer install
composer dump-autoload   # after adding a new class
```

## Tests

```bash
phpunit   # requires WP test suite
```
