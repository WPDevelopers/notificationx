# Notification Bar — Reference (for porting Elementor / Gutenberg into Exit Intent)

This is the reference doc for the **Notification Bar** (PressBar) feature. It focuses on the bits we need to mirror when adding Elementor + Gutenberg builder support to the **Exit Intent Popup**:

1. How a campaign chooses between *built-in themes*, an *Elementor-built* design, and a *Gutenberg-built* design.
2. How the Elementor and Gutenberg builder templates are stored, imported, edited, and removed.
3. How a builder-backed campaign is rendered on the frontend.
4. The admin field schema that wires the builder modals, edit/remove buttons, and conditional rules together.

Pair this with the existing [`exit-intent-popup.md`](./exit-intent-popup.md) — Exit Intent today has only the built-in React themes path; we need to bolt on the same Elementor + Gutenberg paths that PressBar already has.

---

## 1. Key files

### Backend (PHP)

| File | Purpose |
|------|---------|
| [includes/Extensions/PressBar/PressBar.php](../includes/Extensions/PressBar/PressBar.php) | The extension class — registers `nx_bar` + `nx_bar_eb` post types, defines all fields (content / design / customize / display), wires the *Build/Edit/Remove with Elementor* and *Build/Edit/Remove with Gutenberg* buttons + modals, handles save/delete lifecycle, and ultimately renders the bar via `print_bar_notice()`. |
| [includes/Extensions/PressBar/importer.php](../includes/Extensions/PressBar/importer.php) | Elementor importer. Extends `Elementor\TemplateLibrary\Source_Local`, reads `jsons/{theme}.json`, runs Elementor's element-id replacement + on-import processors, then creates an `nx_bar` document via `Elementor\Plugin::$instance->documents->create(...)`. |
| [includes/Core/REST.php](../includes/Core/REST.php) | Registers `/notificationx/v1/elementor/import`, `/elementor/remove`, `/gutenberg/import`, `/gutenberg/remove`. Each route is a thin wrapper that calls back into `PressBar::get_instance()`. |
| [includes/Extensions/PressBar/jsons/](../includes/Extensions/PressBar/jsons/) | Five Elementor template seeds: `theme-one.json` … `theme-five.json`. Each is an exported Elementor document (`{version, title, type, content[]}`) used as the import payload. |
| [includes/Extensions/PressBar/jsons-gb/](../includes/Extensions/PressBar/jsons-gb/) | Seven Gutenberg block-pattern seeds (`__file: wp_block`, `title`, `content` containing Gutenberg block markup). |

### Frontend

The bar runtime itself isn't React-based; once a notification is fetched, `print_bar_notice()` decides between three rendering paths and emits HTML directly. Builder-backed paths emit the Elementor or Gutenberg post's compiled HTML so all styling/scripts come from those builders' own asset pipelines.

---

## 2. Two custom post types

`register_post_type()` ([PressBar.php:1549](../includes/Extensions/PressBar/PressBar.php#L1549)) declares **both** CPTs:

| Post type | Used by | Notes |
|---|---|---|
| `nx_bar` | Elementor builder | `supports: ['title','content','author','elementor']` — the `elementor` support is what lets Elementor's documents API treat it as a first-class document. Public is `false` but `publicly_queryable` is `true` so Elementor's preview routes still resolve. |
| `nx_bar_eb` | Gutenberg block pattern | `show_in_rest: true`, `template_lock: 'block'`, `rest_controller_class: 'WP_REST_Blocks_Controller'`, `capability_type: 'block'`. This is what allows the post to be edited inside the standard block editor like a reusable block / pattern. |

There's also a `get_edit_post_link` filter (constructor, line 55) that rewrites the edit URL for any `nx_bar` post to the Elementor editor URL — so once a Notification Bar campaign is linked to an `nx_bar` post, clicking "Edit" anywhere in WP jumps straight into Elementor.

> For Exit Intent we'll mirror this with separate `nx_exit_intent` (Elementor) + `nx_exit_intent_eb` (Gutenberg) post types — or pick distinct slugs; either way **don't reuse `nx_bar` / `nx_bar_eb`**, because the `get_edit_post_link` filter, `theme_preview`, `nx_get_post`, and the templately push filter all branch on those specific post types.

---

## 3. State machine: which renderer runs?

Every NotificationX post that has `source = press_bar` carries these hidden fields (defined in `design_tab_fields()` around [line 934-1117](../includes/Extensions/PressBar/PressBar.php#L934-L1117)):

| Field | Type | Default | Meaning |
|---|---|---|---|
| `is_elementor` | hidden bool | `class_exists('\Elementor\Plugin')` | Elementor is *available* on this site |
| `is_gutenberg` | hidden bool | `use_block_editor_for_post_type('nx_bar_eb')` | Block editor is *available* for this CPT |
| `elementor_id` | hidden int / false | `false` | If set, the `nx_bar` post ID powering this campaign |
| `gutenberg_id` | hidden int / false | `false` | If set, the `nx_bar_eb` post ID powering this campaign |
| `elementor_edit_link` | hidden / button | empty | Deep link into the Elementor editor (populated when `elementor_id` is set) |
| `gutenberg_edit_link` | hidden / button | empty | Deep link into the block editor (populated when `gutenberg_id` is set) |
| `elementor_bar_theme` | hidden | — | Slug of the seed theme used for the Elementor import (e.g. `theme-one`) |
| `gutenberg_bar_theme` | hidden | — | Slug of the seed pattern used for the Gutenberg import |
| `is_confirmed` | hidden bool | `false` | One-shot flag inside the Elementor "Choose theme" modal: starts false; the Next button flips it; controls modal step visibility |
| `is_gb_confirmed` | hidden bool | `false` | Same one-shot flag for the Gutenberg modal |

Render-time selection happens in `print_bar_notice()` ([line 2070-2088](../includes/Extensions/PressBar/PressBar.php#L2070-L2088)):

```php
if ($elementor_post_id && get_post_status($elementor_post_id) === 'publish' && class_exists('\Elementor\Plugin')) {
    // Elementor path
    $elementor_post_id = apply_filters('wpml_object_id', $elementor_post_id, 'nx_bar', true);
    return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($elementor_post_id, false);
}
else if (!empty($gb_post_id)) {
    // Gutenberg path
    $gb_post_id = apply_filters('wpml_object_id', $gb_post_id, 'wp_block', true);
    $post       = get_post($gb_post_id);
    return do_blocks($post->post_content);
}
else {
    // Built-in theme path (uses press_content + the themes array)
    return do_shortcode($settings->press_content);
}
```

The three paths are mutually exclusive. The admin UI enforces this via field `rules`: as soon as `elementor_id` *or* `gutenberg_id` is set, the "Build With …" buttons hide and only the Edit/Remove buttons for the chosen builder show; the built-in `themes` radio also gets hidden by a number of `Rules::isOfType('elementor_id', 'number', true, …)` checks scattered across `customize_fields()` / `content_fields()`.

---

## 4. The "Import Design" panel (admin UI)

All builder-related UI lives under a single section in `design_tab_fields()`:

```
themes.fields.nx_bar_import_design        (rules: source = press_bar)
├── elementor_edit_link        (button "Edit With Elementor", priority 1)        when: elementor_edit_link set
├── nx-bar_with_elementor-remove (button "Remove", priority 2)                   when: elementor_id set & is_elementor
├── nx-bar_with_elementor       (modal "Build With Elementor")                   when: !elementor_id & !gutenberg_id & is_elementor
├── nx-bar_with_elementor_install (button "Install/Activate Elementor", pri 3)   when: !is_elementor
├── is_elementor                (hidden)
├── elementor_id                (hidden)
├── is_confirmed                (hidden)
├── gutenberg_edit_link         (button "Edit With Gutenberg", priority 4)       when: gutenberg_edit_link set
├── nx-bar_with_gutenberg-remove (button "Remove", priority 5)                   when: gutenberg_id set & is_gutenberg
├── nx-bar_with_gutenberg       (modal "Build With Gutenberg")                   when: !elementor_id & !gutenberg_id & is_gutenberg
├── is_gutenberg                (hidden)
├── gutenberg_id                (hidden)
└── is_gb_confirmed             (hidden)
```

Two sibling stub sections (`nxbar_with_elementor`, `nxbar_with_gutenberg`) sit alongside `nx_bar_import_design`; they're declared at [line 718-738](../includes/Extensions/PressBar/PressBar.php#L718-L738) and currently exist to scope additional builder-state-aware visibility rules (their `fields:[]` is left empty so they act as gated containers).

### The "Build With X" modal

QuickBuilder's `type: 'modal'` field is the central trick. See [line 799-893](../includes/Extensions/PressBar/PressBar.php#L799-L893) for Elementor, [line 1006-1100](../includes/Extensions/PressBar/PressBar.php#L1006-L1100) for Gutenberg. Structure:

```php
[
    'name'   => 'nx-bar_with_elementor',
    'type'   => 'modal',
    'button' => [/* trigger button shown in the panel */],
    'confirm_button' => [
        'type'  => 'button',
        'group' => true,
        'fields' => [
            // Step 1 — actually do the import (ajax POST to /elementor/import)
            [ ... 'ajax' => [
                'on'      => 'click',
                'api'     => '/notificationx/v1/elementor/import',
                'data'    => [ 'theme_id' => '@elementor_bar_theme' ],
                'trigger' => '@is_confirmed:true',
            ], 'rules' => Rules::is('is_confirmed', true, true) ],
            // Step 2 — after the import succeeds, jump the tab to "display_tab"
            [ ..., 'rules' => Rules::is('is_confirmed', true), 'trigger' => [
                ['type' => 'setContext', 'action' => ['config.active' => 'display_tab']],
                ...
            ]],
        ],
    ],
    'body' => [
        'header' => 'Choose Your ',
        'fields' => [
            'themes' => [
                'type'    => 'radio-card',
                'name'    => 'elementor_bar_theme',
                'default' => 'theme-one',
                'options' => $this->bar_themes,  // 5 Elementor seed themes
            ],
        ],
    ],
    'rules' => Rules::logicalRule([
        Rules::is('gutenberg_id', false),
        Rules::is('elementor_id', false),
        Rules::is('is_elementor', true),
        Rules::is('is_confirmed', true, true),
        Rules::is('source', $this->id),
    ]),
]
```

The interesting flow detail: the import button's `ajax.trigger` is `'@is_confirmed:true'`. QuickBuilder runs this *after* the ajax succeeds — it sets `is_confirmed` to `true` on the form state, which (a) hides the Import button (rule `is_confirmed:true,negate`), (b) reveals the Next button (rule `is_confirmed:true`), and (c) reveals the success message. The `cancel` key (`'cancel' => 'import_elementor_theme_next'`) tells QuickBuilder which sibling field, when clicked, should close the modal — so clicking *Next* both navigates to the Display tab and dismisses the modal.

Two theme registries feed the radio-cards:

- `$this->bar_themes` ([line 198-235](../includes/Extensions/PressBar/PressBar.php#L198-L235)) — 5 Elementor themes; each has `{label, value, icon, column, title, enable_coupon?}`. `icon` points at `assets/admin/images/extensions/themes/bar-elementor/theme-N.jpg`.
- `$this->block_themes` ([line 236-296](../includes/Extensions/PressBar/PressBar.php#L236-L296)) — 7 Gutenberg themes. Some entries (`theme-five..seven`) carry `popup` payloads — see [§7](#7-essential-blocks-dependency-gate) below.

### The Edit / Remove buttons

`elementor_edit_link` ([line 753-767](../includes/Extensions/PressBar/PressBar.php#L753-L767)) renders as a `type: 'button'` with `href: -1` — QuickBuilder reads the *current* form value of `elementor_edit_link` (which the importer populated in its response `context`) and uses it as the anchor href. Same trick for `gutenberg_edit_link`.

`nx-bar_with_elementor-remove` ([line 768-797](../includes/Extensions/PressBar/PressBar.php#L768-L797)) fires `POST /elementor/remove`, then runs a client-side `setFieldValue` trigger to wipe `elementor_id`, `elementor_edit_link`, `is_confirmed`, and reset the built-in `themes` back to `press_bar_theme-one`. So after a Remove, the campaign falls back to the built-in theme renderer.

---

## 5. The Elementor import path (deep dive)

**Endpoint**: `POST /notificationx/v1/elementor/import` → `REST::elementor_import` → `PressBar::create_bar_of_type_bar_with_elementor($params)` ([line 1644-1673](../includes/Extensions/PressBar/PressBar.php#L1644-L1673)).

```php
public function create_bar_of_type_bar_with_elementor($params) {
    $theme    = sanitize_text_field($params['theme_id']);          // e.g. "theme-one"
    $importer = new Importer();
    $ID = $importer->create_nx([
        'theme'      => $theme,
        'post_title' => 'Design for NotificationX Bar - ',
    ]);

    if ($ID && !is_wp_error($ID)) {
        update_post_meta($ID, '_wp_page_template', 'elementor_canvas');
        wp_send_json_success([
            'context' => [
                'themes'              => null,
                'elementor_id'        => $ID,
                'elementor_edit_link' => \Elementor\Plugin::$instance->documents->get($ID)->get_edit_url(),
            ]
        ]);
    } else {
        wp_send_json_error('failed');
    }
}
```

The `context` payload is what QuickBuilder merges back into the form state — that's how `elementor_id` and `elementor_edit_link` get populated without a page reload. `themes: null` clears the built-in theme selection.

The `_wp_page_template = 'elementor_canvas'` post meta is critical: it tells Elementor to render the bar with no theme header/footer chrome, since the resulting HTML will be inlined into the host site's page (`get_builder_content_for_display` returns just the document's HTML + style/script registration).

`Importer::create_nx()` ([importer.php:44](../includes/Extensions/PressBar/importer.php#L44)) does:

1. `get_template_content` — `json_decode(file_get_contents("/jsons/$theme.json"))`.
2. `get_data` — runs `replace_elements_ids` (so element IDs in the doc are fresh per-import) and `process_export_import_content(..., 'on_import')` (Elementor's standard import hook, handles URL/media re-mapping, dynamic tags, etc.).
3. Creates a brand-new `nx_bar` document of `type = $template_data['type']` (the section type stored in the JSON).
4. Calls `$document->save(['elements' => ..., 'settings' => ...])` and returns the new post ID.

### When the bar title is renamed

The campaign's QuickBuilder save also calls `PressBar::saved_post()` ([line 355-364](../includes/Extensions/PressBar/PressBar.php#L355-L364)) which updates the linked `nx_bar` post's `post_title` to `"NxBar: <campaign title>"` so the two stay in sync from the WP admin side.

### Editing reverse-fills `elementor_bar_theme`

`nx_get_post()` ([line 2102-2127](../includes/Extensions/PressBar/PressBar.php#L2102-L2127)) is hooked to `nx_get_post` (priority 9). On load it:

- Reads `elementor_id` from the saved post.
- Resolves the live Elementor document, refreshes `elementor_edit_link`, and (by string-matching the document's `post_title` against `bar_themes[*]['title']`) re-derives `elementor_bar_theme` so the admin UI knows which seed theme this campaign started from.
- If the Elementor document is gone (deleted manually), it `unset`s `elementor_id` so the form falls back to the built-in renderer.

### Deletion / WPML

`nx_delete_post` ([line 1520-1526](../includes/Extensions/PressBar/PressBar.php#L1520-L1526)) → `delete_elementor_post()` walks every active WPML language, resolves the translated `nx_bar` IDs via `apply_filters('wpml_object_id', ...)`, and `wp_delete_post`s each one. Same for `gutenberg_remove()` against `wp_block` translations.

---

## 6. The Gutenberg import path

**Endpoint**: `POST /notificationx/v1/gutenberg/import` → `REST::gutenberg_import` → `PressBar::gutenberg_import($params)` ([line 2129-2174](../includes/Extensions/PressBar/PressBar.php#L2129-L2174)).

This one is *much* simpler than the Elementor side because Gutenberg's block markup is just HTML-with-comments stored as `post_content`; nothing needs decoding or element-id rewriting.

```php
public function gutenberg_import($params) {
    $pattern_data = json_decode(file_get_contents(__DIR__ . "/jsons-gb/" . $params['theme_id'] . '.json'), true);

    $post_id = wp_insert_post([
        'post_title'   => $pattern_data['title'],
        'post_content' => $pattern_data['content'],   // raw block markup
        'post_status'  => 'publish',
        'post_type'    => 'nx_bar_eb',
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        if (!empty($pattern_data['syncStatus'])) {
            update_post_meta($post_id, 'wp_pattern_sync_status', $pattern_data['syncStatus']);
        }
        return [ 'success' => true, 'data' => [ 'context' => [
            'themes'              => null,
            'gutenberg_id'        => $post_id,
            'gutenberg_edit_link' => get_edit_post_link($post_id, 'link'),
        ]]];
    }
    return [ 'success' => true, 'data' => 'failed' ];
}
```

The seed JSON files in [`jsons-gb/`](../includes/Extensions/PressBar/jsons-gb/) carry three keys: `__file: wp_block`, `title`, `content` (Gutenberg block markup string with HTML comments like `<!-- wp:columns ... -->`), and optionally `syncStatus`. `wp_pattern_sync_status` is what tells the editor whether the pattern is synced — relevant if the same `nx_bar_eb` post is later surfaced as a reusable block.

### Frontend render

In `print_bar_notice()` the Gutenberg branch runs `do_blocks($post->post_content)` — that's all. The block editor's frontend asset pipeline registers any required scripts/styles when the post type is recognised, so no extra enqueue work is needed in this extension.

`add_scripts()` ([line 2090-2095](../includes/Extensions/PressBar/PressBar.php#L2090-L2095)) tacks on a `gutenberg_url` field (the post's permalink) into the campaign settings before they're handed to the frontend / preview — useful for "open this in the editor" links on the preview side.

### Templately export support

`templately_cloud_push_post_type` filter ([line 2192-2198](../includes/Extensions/PressBar/PressBar.php#L2192-L2198)) renames the `nx_bar_eb` post type to a friendly `NX Bar` label when Templately exports the post to the cloud. Carry this over for Exit Intent if we want the same Templately integration.

---

## 7. Essential Blocks dependency gate

`load_plugin_dependencies()` ([line 2236-2261](../includes/Extensions/PressBar/PressBar.php#L2236-L2261)) is hooked on `init` (priority -1). If **Essential Blocks** is not active, it populates `$this->popup` with a SweetAlert payload (`forced: true`, "You are missing a dependency", install button). That payload is then attached to `block_themes[theme-five|six|seven]['popup']` ([line 273-294](../includes/Extensions/PressBar/PressBar.php#L273-L294)) so QuickBuilder's radio-card renderer pops the dependency warning the moment the user picks one of those themes.

Themes one–four don't have this gate — they use only core blocks. For Exit Intent, the equivalent question is *which seed designs require Essential Blocks (or any other block plugin)*; gate those entries the same way.

---

## 8. Caches, integrations, miscellaneous

- **WP Rocket RUCSS safelist** — `rocket_rucss_safelist()` ([line 2214-2229](../includes/Extensions/PressBar/PressBar.php#L2214-L2229)) iterates all active PressBar posts, and for any with an `elementor_id` it calls `\Elementor\Core\Files\CSS\Post::create($id)->get_url()` and adds that file to the Remove-Unused-CSS exclusion list. Without this, RUCSS strips Elementor-built bar styles. Mirror this for the Exit Intent Elementor path.
- **`bar_reappearance` + `bar_cache_duration_for_dont_show`** ([line 1126-1153](../includes/Extensions/PressBar/PressBar.php#L1126-L1153)) — these are PressBar's analogue of Exit Intent's `exit_intent_cookie_days`. Not relevant to porting builder support, but worth knowing they live in the Display tab via `display_fields()`.
- **Behaviour / content rule suppression when a builder is used** — see [line 1364-1370](../includes/Extensions/PressBar/PressBar.php#L1364-L1370) and [line 2058-2063](../includes/Extensions/PressBar/PressBar.php#L2058-L2063): once `elementor_id` or `gutenberg_id` is a number, the entire `behaviour` and most of the `content` sections are hidden — there's no `press_content`, no random order, no last/from/loop controls to apply, because the builder owns all of that. We'll want the same suppression for Exit Intent (no point showing `exit_intent_title` etc. when the user is editing the popup in Elementor).

---

## 9. End-to-end data flow

```
[Admin] User picks "Build With Elementor"
    └── QuickBuilder shows nx-bar_with_elementor modal
        └── User picks elementor_bar_theme = theme-one, clicks Import
            └── POST /notificationx/v1/elementor/import { theme_id: theme-one }
                └── REST::elementor_import
                    └── PressBar::create_bar_of_type_bar_with_elementor
                        └── new Importer()->create_nx({ theme: theme-one })
                            ├── json_decode(jsons/theme-one.json)
                            ├── replace_elements_ids + process_export_import_content
                            └── Elementor\Plugin::$instance->documents->create('section', { post_type: 'nx_bar', ... })
                        └── update_post_meta(ID, '_wp_page_template', 'elementor_canvas')
                        └── wp_send_json_success({ context: { elementor_id, elementor_edit_link } })
            └── QuickBuilder merges context → form state now has elementor_id
                ├── Import button hides (rule is_confirmed:!true → false)
                ├── Next button shows (rule is_confirmed:true → true)
                └── User clicks Next → setContext jumps to display_tab; modal closes
        └── User saves campaign → PressBar::save_post strips is_elementor/is_confirmed flags
                                → PressBar::saved_post renames nx_bar post_title to "NxBar: <campaign>"

[Frontend] Visitor loads page
    └── NotificationX prints the campaign via print_bar_notice($settings)
        └── elementor_id is set & post is published & Elementor is active
            └── \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($elementor_id, false)
                └── Returns the Elementor document's HTML (and registers its CSS/JS)
```

The Gutenberg flow is the same shape with `/gutenberg/import`, `wp_insert_post({ post_type: 'nx_bar_eb' })`, and `do_blocks($post->post_content)` at render time.

---

## 10. Porting checklist for Exit Intent

When we move on to actually implementing Elementor + Gutenberg support inside `ExitIntentNotification.php`, the concrete work falls out of the sections above:

- [ ] **Post types** — register `nx_exit_intent` (`supports: ['title','content','author','elementor']`) and `nx_exit_intent_eb` (block-pattern style, `template_lock: 'block'`, `rest_controller_class: 'WP_REST_Blocks_Controller'`).
- [ ] **`get_edit_post_link` filter** — point those two post types at the Elementor editor / standard editor edit URL respectively.
- [ ] **Hidden state fields** — `is_elementor`, `is_gutenberg`, `elementor_id`, `gutenberg_id`, `elementor_edit_link`, `gutenberg_edit_link`, `elementor_exit_theme`, `gutenberg_exit_theme`, `is_confirmed`, `is_gb_confirmed`.
- [ ] **Theme registries** — `$this->elementor_themes` (5–7 entries pointing at `assets/admin/images/extensions/themes/exit-intent-elementor/*`) and `$this->block_themes` (entries pointing at `exit-intent-gutenberg/*`). Both image folders already exist.
- [ ] **Seed JSONs** — drop Elementor exports into `includes/Extensions/ExitIntent/jsons/theme-*.json` and Gutenberg block pattern dumps into `jsons-gb/theme-*.json`. These folders already exist but are empty.
- [ ] **Importer class** — copy `Importer extends Source_Local` from PressBar, swap the post type to `nx_exit_intent`, and update the JSON path.
- [ ] **REST endpoints** — either add `/exit-intent/elementor-import` + `/exit-intent/gutenberg-import` siblings in `Core/REST.php`, *or* generalise the existing `/elementor/import` to accept a `source` param. Generalising is the lower-duplication path but requires touching the existing PressBar callers; greenfield endpoints are safer for shipping incrementally.
- [ ] **`design_tab_fields()` / `customize_fields()`** — add the same Import-Design section with `Build / Edit / Remove with Elementor` and `Build / Edit / Remove with Gutenberg`, gated by `source = exit_intent` and the corresponding ID fields.
- [ ] **Suppress built-in fields when a builder is active** — wrap the existing per-theme content sections + `customize_fields` defaults with `Rules::isOfType('elementor_id', 'number', true, …)` / `Rules::isOfType('gutenberg_id', 'number', true, …)`, mirroring lines 1364-1370 / 2058-2063 of PressBar.
- [ ] **Render branch in the frontend** — Exit Intent today goes through the React `ExitIntentPopup` component (see [exit-intent-popup.md](./exit-intent-popup.md)). For builder-backed exit intents we need to: (a) flag the campaign payload with `mode: 'elementor' | 'gutenberg' | 'built_in'`, (b) on the PHP side pre-render the Elementor / Gutenberg HTML into the REST payload (so the React runtime can drop it into a popup shell + overlay), and (c) skip all the per-theme branches in `ExitIntentPopup.tsx` when `mode !== 'built_in'`, rendering only the overlay + close button + injected HTML. The PressBar pattern of "render server-side and inject" cleanly extends here — what's new is that Exit Intent's chrome (overlay, dismiss handling, sensitivity, cookie/session persistence) still needs to wrap the builder output.
- [ ] **Lifecycle hooks** — `before_delete_post` / `nx_delete_post` to garbage-collect the linked `nx_exit_intent` / `nx_exit_intent_eb` post (including WPML translations), and `saved_post` to keep titles in sync.
- [ ] **RUCSS safelist** — extend `rocket_rucss_safelist` to also iterate Exit Intent posts.
- [ ] **Templately push label** — extend `templately_cloud_push_post_type` to relabel `nx_exit_intent_eb`.

The "render builder HTML inside the React popup chrome" item is the only piece without a clean precedent in PressBar — PressBar bypasses React entirely. Worth deciding upfront whether Exit Intent should also bypass React when in builder mode (simpler; but loses the overlay/animation/cookie wiring) or keep React as the shell and inject `dangerouslySetInnerHTML` (preserves the existing dismiss machinery; needs careful asset enqueue so Elementor's CSS/JS still loads on pages where Exit Intent fires).
