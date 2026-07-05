# Elementor Extension (`elementor_form`)

> This directory is two unrelated things bundled together: (1) a single
> **Extension** class, `From.php`, that turns Elementor **Pro** form
> submissions into `form` (Contact Form) type notifications, and (2) two
> standalone **Elementor widgets** (Countdown Timer, Form) that NotificationX
> ships for use inside the Elementor page builder, registered independently of
> the Type/Extension system.

## At a glance

| | |
|---|---|
| **Integration** | Elementor (free) for the widgets; Elementor **Pro** forms for the data-source Extension |
| **Directory** | [`includes/Extensions/Elementor/`](../../includes/Extensions/Elementor/) |
| **Module key(s) (`$module`)** | `elementor_form` (gates the `From` Extension only — the two widgets are not module-gated) |
| **Feeds Types** | `form` (Contact Form — [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php), `$id = 'form'`), fed only by `From.php` |
| **Extension classes** | `From.php` → registers `$id = 'elementor_form'` against `$types = 'form'`. `CountdownWidget.php` and `FormWidget.php` are **not** `Extension` subclasses — see below |
| **Depends on** | Elementor (free, `elementor/loaded` action) for `ElementorManager` + the two widgets; **Elementor Pro** (`ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository`) for the `From` Extension |

## What it does

There are three distinct pieces in this directory, wired up separately:

1. **`ElementorManager`** — instantiated from
   [`includes/NotificationX.php`](../../includes/NotificationX.php) only once
   the base Elementor plugin has loaded (`elementor/loaded` action, or
   immediately if it already fired). It registers a "NotificationX" Elementor
   widget category and the two widgets below, plus their JS/CSS
   (`nx-countdown`, `nx-elementor-form`) via Elementor's own
   `after_register_scripts` / `after_register_styles` hooks.
2. **`CountdownWidget`** — a self-contained Elementor widget (`Widget_Base`)
   rendering a due-date or evergreen/recurring countdown timer. It does not
   read from or write to any NotificationX campaign, entry, or Type — it is a
   standalone content widget that happens to reuse NX's countdown JS/CSS.
   _TODO: verify_ exact rendering/markup (only the `register_controls()` /
   settings portion of the ~1000-line file was inspected in depth here).
3. **`FormWidget`** — a Name/Email/Message Elementor widget that must be bound
   (via its `nx_campaign_id` control) to an *existing* NotificationX campaign
   whose `source` is `popup_notification` or `exit_intent_custom`
   (`get_nx_campaigns()` reads these via
   [`Core/PostType.php`](../../includes/Core/PostType.php)). On submit it
   posts to the existing `notificationx/v1/popup-submit` REST route
   ([`Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php)), the same
   endpoint the built-in Popup/Exit-Intent forms use — it is **not** tied to
   the `elementor_form` module/Extension at all. Free vs Pro gating inside the
   widget (`is_pro()`) checks `class_exists('\NotificationXPro\NotificationX')`
   directly: free forces single-column layout and drops the Email field.
4. **`From`** — the actual `Extension` subclass. It pairs `$types = 'form'`
   with `$id = 'elementor_form'` so that, once the user installs **Elementor
   Pro** and turns on the `elementor_form` module, Elementor Pro form
   submissions become entries for the `form` Contact Form Type. In the
   inspected source, `From` only supplies `init_extension()` (title/popup copy),
   `source_error_message()` (admin notice when Elementor Pro is missing), and
   `doc()` — it does **not** override `get_data()`, `init()`, `save_post`, or
   any submission hook in this free-plugin class. _TODO: verify_ where the
   actual submission capture (presumably hooking an Elementor Pro forms action
   analogous to CF7's `wpcf7_mail_sent`) is implemented — likely in
   `notificationx-pro` since `$is_pro = true`, which is out of scope for this
   repo per `CLAUDE.md`.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`From.php`](../../includes/Extensions/Elementor/From.php) | `form` | `elementor_form` | `elementor_form` | No `get_data()` in this file. `$class = 'ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository'` is used only for the `is_active()`/`class_exists()` presence check; the free-plugin class defines no submission hook or data method. _TODO: verify_ actual capture logic (likely `notificationx-pro`). |

`CountdownWidget.php` and `FormWidget.php` do not extend `Extension`, have no
`$types` / `$id` / `$module`, and are not registered through
`ExtensionFactory` — they are Elementor `Widget_Base` widgets registered
through `ElementorManager::register_widgets()`. They are documented in
"What it does" above rather than in this table, since the template's
Type/Extension pairing model doesn't apply to them.

## Data flow

**`From` (Elementor Pro form submissions → `form` notifications):**
Source event → capture (not present in this free-plugin class; presumed
`notificationx-pro`) → entries table (inherited `Extension::save()` /
`update_notification()` plumbing, per `Extension.php`) → FrontEnd → REST →
React, same as other `form`-Type sources (see
[`docs/extensions/cf7.md`](cf7.md) for the shape of this pipeline on a sibling
integration). _TODO: verify_ the concrete hook/event for Elementor Pro.

**`FormWidget` (Elementor page → NX Popup/Exit-Intent entries):** visitor
submits the widget's form → JS POSTs to `notificationx/v1/popup-submit`
→ `Popup::handle_popup_submission()` delegates to
`NotificationX\Extensions\Popup\PopupNotification::handle_popup_submission()`
→ entry stored against the selected `nx_id`, later surfaced in the Feedback
Entries admin screen (`/feedback-entries` REST routes in the same
`Popup.php`) — this path bypasses the Type/Extension system entirely.

## Fields & settings

- `From` supplies no extension-specific builder fields beyond the base
  `Extension` (`init_extension()` only sets `title`, `module_title`, and
  `popup` copy for the source-selection UI).
- `FormWidget`'s fields (`nx_campaign_id`, `nx_form_title`, per-field Name/
  Email/Message controls, column-width controls) are native Elementor
  `Controls_Manager` controls defined directly in `register_controls()` —
  they are not sourced from `GlobalFields`.
- `CountdownWidget`'s fields (`nx_countdown_type`, due-date/evergreen
  controls, recurring options, styling groups) are likewise native Elementor
  controls, unrelated to `GlobalFields`.

## Dependency & detection

- **`ElementorManager` + widgets:** require the base **Elementor** plugin.
  Detected via the `elementor/loaded` action —
  `NotificationX.php` calls `ElementorManager::get_instance()` immediately if
  `did_action('elementor/loaded')` is already true, otherwise hooks
  `add_action('elementor/loaded', [ElementorManager::class, 'get_instance'])`.
  If Elementor is never active, `ElementorManager` is simply never
  instantiated (no widgets, no assets registered) — no error message shown.
- **`From` Extension:** requires **Elementor Pro**. Detected via
  `$class = 'ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository'`
  on the base `Extension`, checked by `is_active()` / `class_exists()`
  (`class_exists($this->class)`). When absent, `is_active(false)` returns
  `false` so `init()`/`admin_actions()`/`public_actions()`/`init_fields()`
  never run, and `source_error_message()` injects an admin error ("You have
  to install Elementor Pro plugin first.") scoped via
  `Rules::is('source', 'elementor_form')`.
- **`FormWidget`'s own Pro check** (`is_pro()`) is unrelated to either of the
  above — it tests `class_exists('\NotificationXPro\NotificationX')`, i.e.
  whether the **NotificationX Pro** plugin (not Elementor Pro) is active.

## Key files

| Purpose | File |
|---|---|
| Widget bootstrap/registrar | [`includes/Extensions/Elementor/ElementorManager.php`](../../includes/Extensions/Elementor/ElementorManager.php) |
| Countdown Timer widget | [`includes/Extensions/Elementor/CountdownWidget.php`](../../includes/Extensions/Elementor/CountdownWidget.php) |
| Form widget | [`includes/Extensions/Elementor/FormWidget.php`](../../includes/Extensions/Elementor/FormWidget.php) |
| Extension class (data source) | [`includes/Extensions/Elementor/From.php`](../../includes/Extensions/Elementor/From.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it feeds | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'elementor_form' => 'NotificationX\Extensions\Elementor\From'`) |
| Bootstrap call site | [`includes/NotificationX.php`](../../includes/NotificationX.php) (`ElementorManager::get_instance()` on `elementor/loaded`) |
| Shared popup-submit REST route | [`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php) |

## Testing notes & gotchas

- `From.php`'s docblock header still says "Envato Extension" (a leftover
  copy-paste comment) — the class has nothing to do with Envato; go by the
  class name/`$id`/`$module`, not the header comment.
- Because `From` defines no visible `get_data()`/submission hook in this
  repo, verifying the `elementor_form` source end-to-end requires
  `notificationx-pro` to be present and active — this free-plugin class alone
  will register the module/error-messaging but will not populate entries.
  _TODO: verify_ against `notificationx-pro` source if/when available.
- `FormWidget` and `CountdownWidget` are easy to confuse with `From` because
  they live in the same directory and both mention "form" — but they operate
  on completely different pipelines (`popup-submit` REST route vs. the
  Type/Extension `form` source) and neither requires Elementor Pro, only
  Elementor. Don't assume a change to `From.php`'s module gating affects
  `FormWidget`, or vice versa.
- `FormWidget`'s campaign dropdown (`get_nx_campaigns()`) silently returns just
  the placeholder option if `PostType` isn't available or no campaigns match
  `popup_notification` / `exit_intent_custom` — worth checking when the
  dropdown appears empty in the Elementor editor.
- No tests found under `tests/` specific to this directory.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Contact Form 7 Extension](cf7.md) — sibling `form`-Type integration with a
  fully-implemented `get_data()`-free, hook-based capture pattern to compare
  against once `From`'s real capture logic is located.
