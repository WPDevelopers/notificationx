# REST Endpoints

The admin SPA, the public popup runtime, and third-party integrations all talk to the server over the WordPress REST API. This page enumerates every route registered by the plugin.

## Namespace & registration

The base namespace is **`notificationx/v1`** (`notificationx` + version `1`, from `REST::_namespace()` in [../../includes/Core/REST.php](../../includes/Core/REST.php)).

`REST` is instantiated from the root singleton. Its constructor spins up the per-area controllers in [../../includes/Core/Rest/](../../includes/Core/Rest/) — `Posts`, `Integration`, `Entries`, `Analytics`, `BulkAction`, `Popup` — and registers its own routes on `rest_api_init`. Each controller registers its own routes on `rest_api_init` as well. One legacy route also registers under the un-versioned `notificationx` namespace as a Zapier fallback.

Method mapping (WordPress `WP_REST_Server` constants): `READABLE` = GET, `CREATABLE` = POST, `EDITABLE` = POST/PUT/PATCH, `DELETABLE` = DELETE.

## Endpoints

All routes below are under `/notificationx/v1` unless noted. Permission column names the `permission_callback`; the underlying capability is in parentheses (see [Auth & nonces](#auth--nonces)).

### Core — `includes/Core/REST.php`

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/builder` | `read_permission` (`read_notificationx`) | Builder field schema (`PostType::get_localize_scripts()`). |
| POST | `/core-install` | `activate_plugin_permission` (`install_plugins`/`activate_plugins`) | Install/activate a companion plugin. |
| POST/PUT/PATCH | `/elementor/import` | `edit_permission` (`edit_notificationx`) | Create an Elementor bar design (`nx_bar`). |
| POST/PUT/PATCH | `/elementor/remove` | `edit_permission` | Delete the linked Elementor bar post. |
| POST/PUT/PATCH | `/gutenberg/import` | `edit_permission` | Create a Gutenberg bar design. |
| POST/PUT/PATCH | `/gutenberg/remove` | `edit_permission` | Delete the linked Gutenberg bar post. |
| POST/PUT/PATCH | `/exit-intent/elementor/import` | `edit_permission` | Create an Elementor exit-intent design (`nx_exit_intent`). |
| POST/PUT/PATCH | `/exit-intent/elementor/remove` | `edit_permission` | Delete the linked exit-intent post. |
| POST/PUT/PATCH | `/reporting-test` | `settings_permission` (`edit_notificationx_settings`) | Send a test analytics report email. |
| POST/PUT/PATCH | `/entries-mail-test` | `settings_permission` | Send a test entries-mail-receiver email. |
| POST/PUT/PATCH | `/settings` | `settings_permission` | Save plugin settings. |
| POST/PUT/PATCH | `/miscellaneous` | `settings_permission` | Misc settings hook (`nx_rest_miscellaneous`). |
| POST/PUT/PATCH | `/get-data` | `read_permission` | AJAX-select / source data lookup (`get_data`). |
| POST/PUT/PATCH | `/notice` | `__return_true` (public) | **Frontend**: returns resolved notifications for the current page (`FrontEnd::get_notifications_data()`). |
| GET | `/delete-cookies` | `__return_true` (public) | Clears server-side cookies. |
| POST/PUT/PATCH | `/import` | `edit_permission` | Import notifications (`ImportExport::import`). |
| POST/PUT/PATCH | `/export` | `edit_permission` | Export notifications (`ImportExport::export`). |
| POST/PUT/PATCH | `/admin-notice-close` | `read_permission` | Persist dismissal of a dashboard admin notice. |

### Notifications CRUD — `includes/Core/Rest/Posts.php` (base `nx`)

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/nx` | `read_notificationx` | List notifications (paginated, searchable, with view/click totals). |
| POST | `/nx` | `edit_notificationx` | Create a notification. |
| GET | `/nx/(?P<id>\d+)` | `read_notificationx` | Get a single notification (edit context). |
| POST/PUT/PATCH | `/nx/(?P<id>\d+)` | `edit_notificationx` | Update a notification (`PostType::save_post`). |
| DELETE | `/nx/(?P<id>\d+)` | `edit_notificationx` | Delete a notification. |

### Integrations — `includes/Core/Rest/Integration.php` (base `notification`)

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| POST/PUT/PATCH | `/api-connect` | `settings_permission` | Connect an integration (routes to `Extension::connect()` / `nx_api_connect_{source}`). |
| GET | `/notification/(?P<id>\d+)` | `__return_true` (validates `api_key`) | Verify a notification exists for the given site key. |
| POST | `/notification/(?P<id>\d+)` | `__return_true` (validates `api_key`) | Receive an integration webhook (`nx_api_response_success[_{source}]`). |
| GET/POST | `notificationx/notification/(?P<id>\d+)` | `__return_true` | Legacy un-versioned Zapier fallback (same handlers). |

`api_key` must equal `md5(home_url())` (http or https) — this is how webhook sources (Zapier and similar) authenticate without a WP login.

### Entries — `includes/Core/Rest/Entries.php`

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| GET | `/regenerate/(?P<nx_id>[0-9]+)` | `check_permission` (`edit_notificationx`) | Regenerate a notification's entries. |
| GET | `/reset/(?P<nx_id>[0-9]+)` | `check_permission` | Reset a notification's analytics. |

### Analytics — `includes/Core/Rest/Analytics.php` (base `analytics`)

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| POST/PUT/PATCH | `/analytics` | `can_insert_analytics` (setting `enable_analytics`) | **Frontend**: record a view/click/CTR. |
| POST/PUT/PATCH | `/analytics/get` | `can_read_analytics` (`read_notificationx_analytics` + setting) | Fetch aggregated stats for a date range. |

### Bulk actions — `includes/Core/Rest/BulkAction.php` (base `bulk-action`)

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| POST/PUT/PATCH | `/bulk-action/delete` | `edit_notificationx` | Delete many by `ids`. |
| POST/PUT/PATCH | `/bulk-action/regenerate` | `read_notificationx` | Regenerate many. |
| POST/PUT/PATCH | `/bulk-action/enable` | `edit_notificationx` | Enable many. |
| POST/PUT/PATCH | `/bulk-action/disable` | `edit_notificationx` | Disable many. |
| POST/PUT/PATCH | `/bulk-action/reset` | `edit_notificationx` | Reset analytics for many. |

### Popup / Feedback entries — `includes/Core/Rest/Popup.php`

These handle popup **and** exit-intent form submissions (`source IN ('popup_notification','exit_intent_custom')`), stored in `nx_entries`.

| Method | Route | Permission | Purpose |
| --- | --- | --- | --- |
| POST | `/popup-submit` | `__return_true` (public) | **Frontend**: accept a popup/exit-intent form submission. |
| GET | `/feedback-entries` | `read_notificationx` | List submissions (paginated, searchable). |
| DELETE | `/feedback-entries/(?P<id>\d+)` | `edit_notificationx` | Delete one submission. |
| POST | `/feedback-entries/bulk-delete` | `edit_notificationx` | Delete many submissions by `ids`. |
| POST | `/feedback-entries/export` | `read_notificationx` | Export submissions as CSV. |

## Auth & nonces

**Custom capabilities.** Permission callbacks check NotificationX-specific caps rather than raw WP caps: `read_notificationx`, `edit_notificationx`, `edit_notificationx_settings`, and `read_notificationx_analytics`. These are mapped onto roles by `WPDRoleManagement` using the settings role map (see [../architecture/plugin-lifecycle.md](../architecture/plugin-lifecycle.md)). A few endpoints instead require core WP caps (`install_plugins` / `activate_plugins` for `/core-install`).

**Public endpoints.** `/notice`, `/analytics`, `/delete-cookies`, `/popup-submit`, and the `/notification/{id}` integration routes use `permission_callback => '__return_true'` because they serve unauthenticated visitors. The integration routes compensate with the `api_key` (`md5(home_url())`) check; `/popup-submit` sanitizes each input field.

**Nonces & localized data.** The admin SPA authenticates with a standard `wp_rest` nonce. `REST::rest_data()` supplies the client with `root` (REST URL), `namespace`, `nonce` (`wp_create_nonce('wp_rest')`), and `omit_credentials`, filtered through `nx_rest_data`.

**Optional REST hardening.** When the `enable_rest_api` setting is on, `REST` hooks `rest_authentication_errors` (999) to still allow the public `/notice`, `/analytics`, and `/delete-cookies` routes through even if the site otherwise restricts REST access, and excludes those (plus `/send-rating`) from bbPress restriction. A `jwt_auth_whitelist` filter lists the plugin's routes for the JWT Auth plugin, including some `license/*` routes served by the Pro plugin.

## Source
- [../../includes/Core/REST.php](../../includes/Core/REST.php)
- [../../includes/Core/Rest/Posts.php](../../includes/Core/Rest/Posts.php)
- [../../includes/Core/Rest/Integration.php](../../includes/Core/Rest/Integration.php)
- [../../includes/Core/Rest/Entries.php](../../includes/Core/Rest/Entries.php)
- [../../includes/Core/Rest/Analytics.php](../../includes/Core/Rest/Analytics.php)
- [../../includes/Core/Rest/BulkAction.php](../../includes/Core/Rest/BulkAction.php)
- [../../includes/Core/Rest/Popup.php](../../includes/Core/Rest/Popup.php)
