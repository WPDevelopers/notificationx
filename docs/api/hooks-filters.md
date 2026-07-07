# Hooks & Filters

The public extensibility surface — how themes, other plugins, and especially `notificationx-pro` integrate with the free plugin.

Everything below is defined in `includes/`. Naming conventions to keep in mind:

- **Prefix.** NotificationX's own hooks are prefixed `nx_` (a handful of older ones use `notificationx_`). Hooks like `wpml_*`, `nf_*`, `ninja_forms_*`, and `qm/*` that appear in the source are *other plugins'* hooks that we consume — they are **not** part of our surface and are not listed here.
- **Dynamic suffixes.** Many hooks fire twice: once with a `_{$source}` (extension id, e.g. `woocommerce`, `edd`) or `_{$type}` (type id, e.g. `conversions`, `reviews`) suffix, then again with no suffix. The suffixed variant lets an extension target only its own notifications; the bare variant is global. Below, `{source}` = extension id (`$this->id`), `{type}` = Type id.

---

## Actions

| Action | Fires | Args |
| --- | --- | --- |
| `nx::extension::init` | An extension finishes booting ([Extension.php:86](../../includes/Extensions/Extension.php#L86)). **The main Pro integration point** — Pro attaches per-extension wiring here. | `$this` (the `Extension` instance) |
| `nx_saved_post` / `nx_saved_post_{source}` | A notification (CPT) is saved/updated ([PostType.php:204-205](../../includes/Core/PostType.php#L204)). | `$post`, `$data`, `$nx_id` |
| `nx_delete_post` | A notification is deleted ([PostType.php:489](../../includes/Core/PostType.php#L489)). | `$post_id`, `$post` |
| `nx_after_entry_inserted` | An analytics/data entry row is inserted ([Entries.php:75](../../includes/Admin/Entries.php#L75)). | `$entry` |
| `nx_settings_saved` | Global settings are persisted ([Settings.php:677](../../includes/Admin/Settings.php#L677)). | `$settings` |
| `nx_before_settings_fields` | Before the settings field schema is assembled ([Settings.php:105](../../includes/Admin/Settings.php#L105)). | — |
| `notificationx_scripts` | Frontend/preview runtime localizes its notification array ([FrontEnd.php:150](../../includes/FrontEnd/FrontEnd.php#L150), [Preview.php:127](../../includes/FrontEnd/Preview.php#L127)). | `$notificationXArr` |
| `notificationx_admin_scripts` | Admin builder assets are enqueued ([PostType.php:108](../../includes/Core/PostType.php#L108)). | — |
| `nx_before_metabox_load` | Before the QuickBuilder metabox/GlobalFields schema loads ([GlobalFields.php:48](../../includes/Extensions/GlobalFields.php#L48)). | — |
| `nx_api_response_success` / `nx_api_response_success_{source}` | A remote API integration call succeeds ([Rest/Integration.php:188-191](../../includes/Core/Rest/Integration.php#L188)). | `$data` |
| `nx_inline` | An inline (shortcode/block) notification renders ([Inline.php:54](../../includes/Features/Inline.php#L54), [ShortcodeInline.php:76](../../includes/Features/ShortcodeInline.php#L76)). | — |
| `{$hook}_{$source}` (dynamic cron) | A scheduled data-sync fires for a source ([Cron.php:114](../../includes/Admin/Cron.php#L114)). Extensions register the matching handler. | `$post_id`, `$post` |

> The `wpdeveloper_*_notice_for_notificationx` actions in [Notice.php](../../includes/Admin/Notice.php) belong to the shared `wp-notice` library, not to NotificationX's public API — treat them as internal.

---

## Filters

### Registration & the source/type registry

These are how a plugin (Pro or third-party) adds new Types, Extensions, sources, and themes.

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_extension_classes` | The list of `Extension` classes to instantiate. **Pro registers its extensions here** ([ExtensionFactory.php:98](../../includes/Extensions/ExtensionFactory.php#L98)). | `$extension_classes` |
| `nx_types_classes` | The list of `Types` classes to register ([TypesFactory.php:51](../../includes/Types/TypesFactory.php#L51)). | `$types` |
| `nx_sources` | Source (extension) options shown in the builder dropdown ([GlobalFields.php:141](../../includes/Extensions/GlobalFields.php#L141)); each extension appends via `__nx_sources` ([Extension.php:451](../../includes/Extensions/Extension.php#L451)). | `[]` (array of source configs) |
| `nx_is_pro_sources` | Which source ids are flagged Pro-only ([GlobalFields.php:63](../../includes/Extensions/GlobalFields.php#L63)). | `[]` |
| `nx_themes` / `nx_res_themes` | Theme (design) options, and responsive-theme options ([GlobalFields.php:340](../../includes/Extensions/GlobalFields.php#L340), [:379](../../includes/Extensions/GlobalFields.php#L379)). | `[]` |
| `nx_themes_trigger` / `nx_themes_trigger_for_responsive` / `nx_source_trigger` / `nx_source_types_title` | Default/trigger values and titles wired to the theme & source pickers. | varies |
| `nx_metabox_tabs` / `nx_metabox_config` | The full builder tab tree ([GlobalFields.php:2274-2275](../../includes/Extensions/GlobalFields.php#L2274)). | `$tabs` |
| `nx_builder_configs` | Builder tab config passed to the admin SPA ([PostType.php:142](../../includes/Core/PostType.php#L142)). | `$tabs` |

### The Pro upsell surface

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_pro_alert_popup` | Wraps a field/source's `popup` config with the "upgrade to Pro" overlay when the feature is Pro-gated ([Extension.php:451](../../includes/Extensions/Extension.php#L451), [GlobalFields.php:113](../../includes/Extensions/GlobalFields.php#L113)). This is the canonical filter referenced in [CLAUDE.md](../../CLAUDE.md). | `$popup` |
| `nx_popup_alert` / `nx_instructions` | Pro-alert payload and instruction blocks in the metabox ([GlobalFields.php:2270-2271](../../includes/Extensions/GlobalFields.php#L2270)). | `[]` |

### Save / read a notification (CPT)

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_save_post` / `nx_save_post_{source}` | Post array just before it is written ([PostType.php:184-185](../../includes/Core/PostType.php#L184)). | `$post`, `$data`, `$nx_id` |
| `nx_get_post` / `nx_get_post_{source}` | A single notification when read back ([PostType.php:202-203](../../includes/Core/PostType.php#L202), [:386-388](../../includes/Core/PostType.php#L386)). Context-dependent 2nd arg. | `$data` (`, $context`) |
| `nx_get_posts` | The full list of notifications ([PostType.php:401](../../includes/Core/PostType.php#L401)). | `$posts`, `$context` |
| `nx_can_enable` | Whether a source can be enabled/published ([PostType.php:344](../../includes/Core/PostType.php#L344)). | `$return`, `$source`, `$rest` |
| `nx_theme_preview_{source}` | Theme preview image URL ([PostType.php:505](../../includes/Core/PostType.php#L505)). | `$url`, `$post` |

### Analytics / data entries

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_can_entry_{source}` | Gate whether a fetched entry is stored — return `false` to drop it (used for GDPR, dedup, etc.) ([Extension.php:654](../../includes/Extensions/Extension.php#L654), [:680](../../includes/Extensions/Extension.php#L680)). | `true`, `$entry`, `$post` |
| `nx_can_entry_gdpr_notification` | Same gate, applied by the GDPR scanner ([Scanner.php:183](../../includes/Admin/Scanner/Scanner.php#L183)). | `true`, `$entry`, `$post` |
| `nx_insert_entry` | Entry row just before insert ([Entries.php:72](../../includes/Admin/Entries.php#L72), [:93](../../includes/Admin/Entries.php#L93)). | `$entry` |
| `nx_get_entry` / `nx_get_entries` | Entries read back from the table ([Entries.php:104-114](../../includes/Admin/Entries.php#L104)). | `$entry` / `$entries` |

### Frontend render pipeline

Fired, roughly in order, as [FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) turns stored entries into the JSON the popup runtime consumes.

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_before_enqueue_scripts` | Short-circuit: return truthy to skip enqueuing entirely ([:121](../../includes/FrontEnd/FrontEnd.php#L121)). | `$exit` |
| `nx_frontend_localize_data` | The whole localized data array before it's handed to JS ([:209](../../includes/FrontEnd/FrontEnd.php#L209)). | `$notificationXArr` |
| `nx_frontend_get_entries` | Entries pulled for the active notifications ([:722](../../includes/FrontEnd/FrontEnd.php#L722)). | `$entries`, `$ids`, `$notifications`, `$params` |
| `nx_get_entries_query_part_{source}` | Per-source SQL fragment for the entries query ([:705](../../includes/FrontEnd/FrontEnd.php#L705)). | `$global_query`, `$notification`, `$params` |
| `nx_entry_show_on_frontend_{source}` | Return `true` to skip an entry ([:310](../../includes/FrontEnd/FrontEnd.php#L310)). | `false`, `$entry`, `$settings` |
| `nx_entry_display_{source}` | Whether an entry outside the display window is still skipped ([:323](../../includes/FrontEnd/FrontEnd.php#L323)). | `true`, `$entry`, `$settings` |
| `nx_fallback_data` / `nx_fallback_data_{source}` | Default values merged into a sparse entry ([:329-330](../../includes/FrontEnd/FrontEnd.php#L329)). | `$defaults`, `$entry`, `$settings` |
| `nx_filtered_entry` / `_{type}` / `_{source}` | A single processed entry ([:338-340](../../includes/FrontEnd/FrontEnd.php#L338)). | `$entry`, `$settings` |
| `nx_filtered_data` / `_{type}` / `_{source}` | The entries array for one notification ([:376-378](../../includes/FrontEnd/FrontEnd.php#L376)). | `$entries`, `$post`, `$params` |
| `nx_filtered_post` | The notification's settings/post array ([:379](../../includes/FrontEnd/FrontEnd.php#L379), and bar/other branches). | `$post`, `$params` |
| `nx_filtered_notice` | The assembled result for all notifications ([:382](../../includes/FrontEnd/FrontEnd.php#L382)). | `$result`, `$params` |
| `nx_notification_link` / `nx_notification_link_{source}` | The click-through URL of an entry ([:742-743](../../includes/FrontEnd/FrontEnd.php#L742)). | `$link`, `$post`, `$entry`, `$params` |
| `nx_notification_image` / `nx_notification_image_{source}` | Resolved image data for an entry ([:793-794](../../includes/FrontEnd/FrontEnd.php#L793)). | `$image_data`, `$data`, `$settings` |
| `nx_should_combine` | Whether like entries are merged into one "X people…" notice ([WooCommerce.php:493](../../includes/Extensions/WooCommerce/WooCommerce.php#L493), [EDD.php:114](../../includes/Extensions/EDD/EDD.php#L114)). | `true`, `$data`, `$settings` |
| `nx_show_on_exclude` / `nx_check_location` / `nx_location_status` | Page-targeting / display-location logic ([:544](../../includes/FrontEnd/FrontEnd.php#L544), [:638](../../includes/FrontEnd/FrontEnd.php#L638), [Locations.php:92](../../includes/Core/Locations.php#L92)). | varies |
| `nx_branding_url` | The "powered by NotificationX" branding link ([:471](../../includes/FrontEnd/FrontEnd.php#L471)). | `$url` |
| `nx_frontend_js_version` / `nx_frontend_css_version` | Asset cache-bust version ([:80-81](../../includes/FrontEnd/FrontEnd.php#L80)). | `NOTIFICATIONX_VERSION` |

### In-builder preview

Preview mirrors the frontend pipeline with `nx_preview_*` variants — see [Preview.php](../../includes/FrontEnd/Preview.php): `nx_preview_entry_{type}` / `_{source}`, `nx_preview_settings_{source}`, `nx_is_preview`, `nx_preview_url`, plus reuse of `nx_fallback_data*`, `nx_filtered_entry*`, and `nx_get_post*`.

### Settings tabs

`nx_settings`, `nx_settings_configs`, `nx_settings_page_settings`, `nx_role_management`, and the per-tab filters `nx_settings_tab`, `nx_settings_tab_general`, `nx_settings_tab_advanced`, `nx_settings_tab_email_analytics`, `nx_settings_tab_entries`, `nx_settings_tab_cache`, `nx_settings_tab_miscellaneous` — all in [Settings.php](../../includes/Admin/Settings.php). Add a Pro settings section by hooking `nx_settings_tab`.

### Data-source option lists

Populated by extensions to fill builder dropdowns: `nx_post_types`, `nx_loop_taxonomies` ([Helper.php](../../includes/Core/Helper.php)), `nx_form_list` ([ContactForm.php](../../includes/Types/ContactForm.php)), `nx_elearning_course_list` ([ELearning.php](../../includes/Types/ELearning.php)), `nx_conversion_category_list`, `nx_conversion_product_list`, `nx_woo_order_status`, `nx_surecart_order_status`, `nx_fluentcart_order_status`, `nx_text_trim_length`, `nx_wp_reviews_rating_condition` (in [GlobalFields.php](../../includes/Extensions/GlobalFields.php) and the respective Type/Extension classes).

### Cron & misc

`nx_cron_schedules` ([Cron.php:102](../../includes/Admin/Cron.php#L102)), `nx_rest_data` / `nx_rest_miscellaneous` ([REST.php](../../includes/Core/REST.php)), `nx_api_response` / `nx_api_connect_{source}` ([Rest/Integration.php](../../includes/Core/Rest/Integration.php)), `nx_usage_tracker_data` / `nx_plugin_usage_tracker_data`, `nx_settings_xss_code_default` ([XSS.php](../../includes/Admin/XSS.php)), `gdpr_d_domains_filter` ([Helper.php:739](../../includes/Core/Helper.php#L739)).

---

## How Pro hooks in

Pro features live in the **separate `notificationx-pro` plugin** and integrate through the same Extension/Type system plus the filters above — never by editing the free repo. The typical path:

1. **Register classes** — `notificationx-pro` adds its Extension classes via `nx_extension_classes` and any new Types via `nx_types_classes`. From there they flow through `ExtensionFactory` / `TypesFactory` exactly like the built-in ones.
2. **Boot per-extension** — Pro attaches to the `nx::extension::init` action to wire up instance-level behavior.
3. **Gate the free UI** — free-plugin fields that represent Pro features wrap their config in `nx_pro_alert_popup` (and `nx_is_pro_sources` / `nx_popup_alert`), so the builder shows an upgrade overlay until Pro is active. `NotificationX::is_pro()` flips these off once Pro is installed.
4. **Extend data & render** — Pro extensions produce entries (`get_data()` → stored via `Entries`) and adjust the frontend through the `nx_filtered_*`, `nx_fallback_data*`, `nx_notification_link*`, and `nx_notification_image*` filters, the same way free extensions do.

**Do not add Pro-only logic to this repo.** If Pro needs a new seam, add a `do_action` / `apply_filters` here and consume it from `notificationx-pro`.

---

## Source

- Enumerate with: `grep -rn "do_action(\|apply_filters(" includes/ --include="*.php"`.
- Registration flow: [includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php), [includes/Types/TypesFactory.php](../../includes/Types/TypesFactory.php).
- Render pipeline: [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php), [includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php).
- [../../CLAUDE.md](../../CLAUDE.md) — conventions, including the Pro-separation rule.
