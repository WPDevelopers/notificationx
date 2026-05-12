# REST API

REST endpoints exposed by NotificationX. Base namespace: **`/wp-json/notificationx/v1`** ([includes/Core/REST.php:43](../includes/Core/REST.php)).

## Authentication

All write endpoints require:
- A logged-in WP user with the `manage_options` capability (or equivalent — verify the `permission_callback` per endpoint).
- A REST nonce (`X-WP-Nonce` header), obtained from `wpApiSettings.nonce` (or `nx.nonce` in the admin runtime).

The frontend `analytics` endpoint is the one public exception — it's how the popup runtime reports views/clicks. It uses its own validation (see below), not capability check.

## Controllers

Six controllers, each mounted via `get_instance()` in [REST.php:50–55](../includes/Core/REST.php):

| File | Purpose |
|---|---|
| [Rest/Posts.php](../includes/Core/Rest/Posts.php) | Notification CRUD |
| [Rest/Entries.php](../includes/Core/Rest/Entries.php) | Entry regenerate/reset |
| [Rest/BulkAction.php](../includes/Core/Rest/BulkAction.php) | Bulk delete/enable/disable/reset |
| [Rest/Integration.php](../includes/Core/Rest/Integration.php) | Third-party API connect |
| [Rest/Analytics.php](../includes/Core/Rest/Analytics.php) | Views/clicks reporting |
| [Rest/Popup.php](../includes/Core/Rest/Popup.php) | Popup feedback / form submissions |

## Route table

### Posts

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/nx` | List notifications |
| `POST` | `/nx` | Create notification |
| `GET` | `/nx/{id}` | Get one |
| `PUT` | `/nx/{id}` | Update one |
| `DELETE` | `/nx/{id}` | Delete one |

### Entries

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/entries/regenerate/{nx_id}` | Re-fetch entries from source for a notification |
| `POST` | `/entries/reset/{nx_id}` | Wipe entries for a notification |

### BulkAction

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/nx/delete` | Bulk delete (body: `ids[]`) |
| `POST` | `/nx/regenerate` | Bulk regenerate |
| `POST` | `/nx/enable` | Bulk enable |
| `POST` | `/nx/disable` | Bulk disable |
| `POST` | `/nx/reset` | Bulk entry reset |

### Integration

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api-connect` | Test/connect a third-party API (varies by source) |

### Analytics

| Method | Path | Purpose | Auth |
|---|---|---|---|
| `PUT` | `/analytics` | Frontend submits view/click event | Public (validated) |
| `POST` | `/analytics/get` | Admin fetches stats | `manage_options` |

### Popup (form/feedback notification Type)

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/popup-submit` | Public form submission from popup |
| `GET` | `/feedback-entries` | List submissions |
| `POST` | `/feedback-entries` | Create one |
| `GET` | `/feedback-entries/{id}` | Get one |
| `PUT` | `/feedback-entries/{id}` | Update one |
| `DELETE` | `/feedback-entries/{id}` | Delete one |
| `POST` | `/feedback-entries/bulk-delete` | Bulk delete |
| `GET` | `/feedback-entries/export` | CSV export |

## Worked example: create a notification

```http
POST /wp-json/notificationx/v1/nx
X-WP-Nonce: <nonce>
Content-Type: application/json

{
  "title": "Recent WooCommerce Sales",
  "type": "conversions",
  "source": "woocommerce_sales",
  "theme": "woocommerce_sales_theme-one",
  "enabled": true,
  "data": { /* builder field values */ }
}
```

Returns the created record with its `nx_id`.

## Worked example: frontend analytics ping

```http
PUT /wp-json/notificationx/v1/analytics
Content-Type: application/json

{ "nx_id": 42, "event": "view" }
```

No nonce required, but the endpoint validates payload shape and rate-limits.

## Error shape

Standard WP REST: `{"code": "...", "message": "...", "data": {"status": 4xx}}`. NotificationX-specific error codes are defined per-controller — grep `WP_Error\(\s*['"]` inside `includes/Core/Rest/` to enumerate.

## Adding a new endpoint

See [recipes/add-rest-endpoint.md](recipes/add-rest-endpoint.md).

## Verifying this doc

If you suspect routes have drifted:

```sh
grep -rn "register_rest_route" includes/Core/Rest/
```

Each call site shows the path and callback. Update this file when routes are added/removed.
