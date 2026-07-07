# API Reference

The programmatic surface of NotificationX — REST endpoints consumed by the admin SPA and frontend runtime, plus the WordPress hooks/filters other code (including `notificationx-pro`) integrates through.

## Contents

| Doc | What it covers |
| --- | --- |
| [rest-endpoints.md](rest-endpoints.md) | REST namespace, routes, request/response shapes. |
| [hooks-filters.md](hooks-filters.md) | Public actions & filters (e.g. `nx_pro_alert_popup`) and how Pro hooks in. |

## Where REST is registered

Endpoints register through [../../includes/Core/REST.php](../../includes/Core/REST.php) and the handlers in `../../includes/Core/Rest/`. See [../architecture/admin-spa.md](../architecture/admin-spa.md) for the consumer side.
