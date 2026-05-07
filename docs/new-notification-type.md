# Adding a New Notification Type — End-to-End Guide

This guide documents every layer you must touch to add a fully working notification type to NotificationX, using the **Exit Intent Popup** (`exit_intent`) as the reference implementation.

---

## Overview of the Architecture

```
Admin creates NX               PHP routes IDs          React fetches data
  ↓                              ↓                         ↓
PostType (DB)  →  TypesFactory / ExtensionFactory  →  FrontEnd.php  →  REST API
                                                              ↓
                                                      window.notificationXArr
                                                              ↓
                                                      useNotificationX.ts  (state)
                                                              ↓
                                                      NotificationContainer.tsx
                                                              ↓
                                                      <YourComponent />
```

There are **7 touch-points** spread across PHP and TypeScript/React:

| # | Layer | File(s) |
|---|-------|---------|
| 1 | Type class | `includes/Types/YourType.php` |
| 2 | Extension class | `includes/Extensions/YourType/YourExtension.php` |
| 3 | Factory registrations | `TypesFactory.php`, `ExtensionFactory.php` |
| 4 | Composer autoload | `vendor/composer/autoload_classmap.php`, `autoload_static.php` |
| 5 | PHP data pipeline | `includes/FrontEnd/FrontEnd.php` |
| 6 | React data pipeline | `useNotificationX.ts`, `utils.ts` |
| 7 | React rendering | `NotificationContainer.tsx`, `YourComponent.tsx`, SCSS |

---

## Step 1 — Create the Type Class

**Path:** `includes/Types/ExitIntent.php`

The Type class declares the notification type to the admin UI and associates it with a module and default extension.

```php
namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

class ExitIntent extends Types {
    use GetInstance;

    public $priority       = 17;          // Admin sidebar ordering
    public $is_pro         = false;       // Set true to lock behind pro
    public $module         = ['modules_exit_intent']; // Module key (must exist in Modules)
    public $default_source = 'exit_intent_custom';    // Extension $id
    public $default_theme  = 'exit_intent_theme-one';
    public $link_type      = 'none';

    public function __construct() {
        parent::__construct();
        $this->id = 'exit_intent'; // Unique slug — used everywhere as the "type"
    }

    public function init() {
        parent::init();
        $this->title           = __( 'Exit Intent Popup', 'notificationx' );
        $this->dashboard_title = __( 'Exit Intent Popup', 'notificationx' );

        $this->themes = [
            'theme-one' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/themes/theme-blank.jpg',
                'template' => [ /* default template tokens */ ],
            ],
        ];
    }
}
```

**Key rules:**
- `$this->id` must be globally unique across all types.
- `$module` must reference a key registered in the Modules system. If the module is disabled, the type is hidden in the admin.
- `$default_source` must match the `$id` of your Extension class (Step 2).

---

## Step 2 — Create the Extension Class

**Path:** `includes/Extensions/ExitIntent/ExitIntentNotification.php`

The Extension class controls the admin settings tabs (Content, Design, Customize) for your source.

```php
namespace NotificationX\Extensions\ExitIntent;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

class ExitIntentNotification extends Extension {
    use GetInstance;

    public $priority = 16;
    public $id       = 'exit_intent_custom'; // Matches Type's $default_source
    public $types    = 'exit_intent';        // Matches Type's $this->id
    public $module   = 'modules_exit_intent';

    public function init_extension() {
        $this->title        = __( 'Exit Intent Popup', 'notificationx' );
        $this->module_title = __( 'Exit Intent Popup', 'notificationx' );
        $this->themes = [
            'theme-one' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/themes/theme-blank.jpg',
                'defaults' => [
                    'position' => 'center', // Default position for this source
                ],
            ],
        ];
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_content_fields',    [ $this, 'content_fields' ],   999 );
        add_filter( 'nx_design_tab_fields', [ $this, 'design_fields' ],    99  );
        add_filter( 'nx_customize_fields',  [ $this, 'customize_fields' ], 999 );
    }

    public function content_fields( $fields ) {
        // Use Rules::is() to show/hide fields based on the active source
        $fields['your_section'] = [
            'label'  => __( 'Content', 'notificationx' ),
            'name'   => 'your_section',
            'type'   => 'section',
            'rules'  => Rules::is( 'source', $this->id ),
            'fields' => [
                [ 'label' => __( 'Title', 'notificationx' ), 'name' => 'ei_title', 'type' => 'text' ],
                // ... more fields
            ],
        ];
        return $fields;
    }

    // design_fields() and customize_fields() follow the same pattern.
}
```

**Key rules:**
- `$id` is the `source` value stored in the post meta — it must be unique.
- `$types` must match the Type's `$this->id`.
- Always gate fields with `Rules::is( 'source', $this->id )` so they only show for your source.

---

## Step 3 — Register in the Factories

**`includes/Types/TypesFactory.php`** — add your Type:

```php
private $types = [
    // ... existing types ...
    'exit_intent' => 'NotificationX\Types\ExitIntent',
];
```

**`includes/Extensions/ExtensionFactory.php`** — add your Extension:

```php
private $extensions = [
    // ... existing extensions ...
    'exit_intent_custom' => 'NotificationX\Extensions\ExitIntent\ExitIntentNotification',
];
```

Both keys must match the `$id` properties from Steps 1 and 2.

---

## Step 4 — Update Composer Autoload

Because NotificationX uses a classmap autoloader, new classes must be registered manually.

**`vendor/composer/autoload_classmap.php`:**

```php
'NotificationX\\Extensions\\ExitIntent\\ExitIntentNotification' =>
    $baseDir . '/includes/Extensions/ExitIntent/ExitIntentNotification.php',
```

**`vendor/composer/autoload_static.php`** — add the same entry inside `$classMap`.

> If you run `composer dump-autoload` this is handled automatically. Only update manually when Composer is not available.

---

## Step 5 — PHP Data Pipeline (`FrontEnd.php`)

This is the backend side of the data flow. Three sections need updating.

### 5a. `get_notifications_ids()` — Route the source to its own bucket

Find the chain of `elseif` blocks that sort notifications by source:

```php
// Before your change:
$active_notifications = $global_notifications = $bar_notifications =
    $gdpr_notification = $popup_notifications = array();

// After — add your bucket:
$active_notifications = $global_notifications = $bar_notifications =
    $gdpr_notification = $popup_notifications = $exit_intent_notifications = array();
```

Then add a routing branch:

```php
} elseif($settings['source'] == 'popup_notification') {
    $popup_notifications[] = $settings['nx_id'];
} elseif($settings['source'] == 'exit_intent_custom') {   // <-- add this
    $exit_intent_notifications[] = $settings['nx_id'];
} elseif ($active_global_queue && NotificationX::is_pro()) {
```

Finally, include your bucket in the return value:

```php
return apply_filters('get_notifications_ids', [
    'global'      => $global_notifications,
    'active'      => $active_notifications,
    'pressbar'    => $bar_notifications,
    'gdpr'        => $gdpr_notification,
    'popup'       => $popup_notifications,
    'exit_intent' => $exit_intent_notifications,            // <-- add this
    'total'       => (... + count($exit_intent_notifications)), // <-- add to total
], $notifications);
```

### 5b. `get_notifications_data()` — Initialize the result key and parse params

```php
$result = [
    'global'      => [],
    'active'      => [],
    'pressbar'    => [],
    'gdpr'        => [],
    'shortcode'   => [],
    'popup'       => [],
    'exit_intent' => [],   // <-- add this
];

$params = wp_parse_args($params, [
    'global'      => [],
    'active'      => [],
    'pressbar'    => [],
    'gdpr'        => [],
    'popup'       => [],
    'exit_intent' => [],   // <-- add this
    'shortcode'   => [],
]);

$exit_intent = $params['exit_intent'];   // <-- extract the variable
```

### 5c. `get_notifications_data()` — Process and populate the result

Add a processing block after the popup block (before `$result['settings'] = ...`):

```php
if (!empty($exit_intent)) {
    $notifications = $this->get_notifications($exit_intent);
    foreach ($notifications as $key => $settings) {
        $_nx_id = $settings['nx_id'];
        if (!$settings['enabled']) {
            continue;
        }
        $settings = apply_filters('nx_filtered_post', $settings, $params);
        $result['exit_intent'][$_nx_id]['post']    = $settings;
        $result['exit_intent'][$_nx_id]['content'] = "";
        unset($_nx_id);
    }
}
```

The shape of each entry — `{ post: {}, content: "" }` — is consumed by `normalizePressBar()` on the frontend.

---

## Step 6 — React Data Pipeline

### 6a. `utils.ts` — Add to `normalizeResponse()`

```ts
// nxdev/notificationx/frontend/core/utils.ts

export const normalizeResponse = (response: any) => {
    // ... existing normalizations ...
    let exit_intent = normalizePressBar(response?.exit_intent, response?.settings);

    return {
        // ... existing keys ...
        exit_intent,
    };
};
```

Use `normalizePressBar` (not `normalize`) because your entries have the pressbar/popup shape: `{ post, content }` rather than `{ post, entries[] }`.

### 6b. `useNotificationX.ts` — State, API request, and dispatch

**Add state:**

```ts
const [exitIntentNotices, setExitIntentNotices] = useState(null);
```

**Include in the API request body:**

```ts
const data = {
    // ... existing fields ...
    exit_intent: props.config?.exit_intent || [],
};
```

**Set state after the response:**

```ts
.then((response: any) => {
    // ... existing setters ...
    setExitIntentNotices(response?.exit_intent);
});
```

**Add a `useEffect` that triggers the dispatch at the right moment.**

For an overlay/popup that shows immediately:

```ts
useEffect(() => {
    if (popupNotices != null && popupNotices.length > 0) {
        popupNotices.forEach((item) => {
            const args = { intervalID: null, timeoutID: null, data: item.content, config: item.post };
            dispatchNotification(args);
        });
    }
}, [popupNotices]);
```

For an event-triggered popup (exit intent — fires on `mouseleave`):

```ts
useEffect(() => {
    if (exitIntentNotices != null && exitIntentNotices.length > 0) {
        const triggered = new Set();

        const handleMouseLeave = (e: MouseEvent) => {
            if (e.clientY > 10) return; // Only fire when cursor exits from the top

            exitIntentNotices.forEach((exitItem) => {
                const config = exitItem.post;
                const nx_id  = config?.nx_id;
                if (triggered.has(nx_id)) return;

                // Respect session-based dismissal
                const sessionKey = `notificationx_exit_intent_${nx_id}`;
                if (sessionStorage.getItem(sessionKey) === 'closed') return;

                triggered.add(nx_id);
                dispatchNotification({
                    intervalID: null,
                    timeoutID:  null,
                    data:       exitItem.content || null,
                    config,
                });
            });
        };

        document.addEventListener('mouseleave', handleMouseLeave);
        return () => document.removeEventListener('mouseleave', handleMouseLeave);
    }
}, [exitIntentNotices]);
```

**How dispatch works:**
`dispatchNotification` calls `isNotClosed(data)` then pushes `{ id, data, config }` into `state.notices` via `useReducer`. The `config` object is your post settings — it must contain `position` so `getNxToRender` can place it in the right container.

**Position values in `fixedOrder`:**

```ts
['top','bottom','bottom_left','bottom_right','top_left','top_right',
 'center','cookie_notice_bottom_left','cookie_notice_bottom_right',
 'cookie_notice_center','cookie_banner_top','cookie_banner_bottom']
```

If your notification's `config.position` is not in this list it will not render. Set the default in your Extension theme defaults (Step 2).

---

## Step 7 — React Rendering

### 7a. Create your component

**Path:** `nxdev/notificationx/frontend/core/YourComponent.tsx`

```tsx
import React, { useState } from 'react';

const ExitIntentPopup = (props: any) => {
    const { nxExitIntent, dispatch } = props;
    const { config: settings }       = nxExitIntent;
    const [isVisible, setIsVisible]  = useState(true);

    const handleClose = () => {
        // Persist dismissal so the popup does not re-appear this session
        sessionStorage.setItem(`notificationx_exit_intent_${settings?.nx_id}`, 'closed');
        setIsVisible(false);
        dispatch?.({ type: "REMOVE_NOTIFICATION", payload: nxExitIntent.id });
    };

    if (!isVisible) return null;

    return (
        <div className="nx-exit-intent-overlay" onClick={handleClose}>
            <div className="nx-exit-intent-popup" onClick={(e) => e.stopPropagation()}>
                <button className="nx-exit-intent-close" onClick={handleClose}>&times;</button>
                <div className="nx-exit-intent-content">
                    <h2>Hello World</h2>
                </div>
            </div>
        </div>
    );
};

export default ExitIntentPopup;
```

**Props contract:**

| Prop | Source | Contains |
|------|--------|---------|
| `nxExitIntent.config` | `dispatchNotification` → `state.notices[i]` | All post settings (type, nx_id, position, …) |
| `nxExitIntent.data` | The `data` arg passed to `dispatchNotification` | Entry content (may be null for config-only types) |
| `nxExitIntent.id` | UUID generated by `dispatchNotification` | Used to call `REMOVE_NOTIFICATION` |
| `dispatch` | `frontendContext.dispatch` | The `useReducer` dispatcher |

### 7b. Export from the index

**`nxdev/notificationx/frontend/core/index.ts`:**

```ts
export { default as ExitIntentPopup } from './ExitIntentPopup'
```

### 7c. Route in `NotificationContainer.tsx`

Add a branch inside `renderNotice()` **before** the fallback `<Notification>` return:

```tsx
import ExitIntentPopup from "./ExitIntentPopup";

// Inside renderNotice(), after the popup block:
if (notice?.config?.type == 'exit_intent') {
    return (
        <ExitIntentPopup
            key={`exit-intent-${notice?.config?.nx_id}`}
            nxExitIntent={notice}
            dispatch={frontendContext.dispatch}
        />
    );
}
```

The `type` value comes from the post's `type` field, which maps to your Type class `$this->id`.

### 7d. Add SCSS

**`nxdev/notificationx/frontend/scss/_themes/_your-type.scss`:**

```scss
.nx-exit-intent-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nx-exit-intent-popup {
    background: #fff;
    border-radius: 8px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    position: relative;
}

.nx-exit-intent-close {
    position: absolute;
    top: 12px;
    right: 16px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}
```

**Import it in `theme.scss`:**

```scss
@import "./_themes/your-type";
```

---

## Step 8 — Build Assets

```bash
npm run frontend   # builds public/js/frontend.js + public/css/frontend.css
npm run build      # builds admin + frontend together
```

---

## Checklist

```
PHP
  [ ] Type class created with unique $this->id
  [ ] Extension class created with matching $id and $types
  [ ] Registered in TypesFactory.php
  [ ] Registered in ExtensionFactory.php
  [ ] Added to composer autoload_classmap.php + autoload_static.php
  [ ] get_notifications_ids() — new bucket variable, routing branch, return key, total count
  [ ] get_notifications_data() — result key, wp_parse_args default, extract variable, processing block

Frontend
  [ ] utils.ts — normalizeResponse() returns your key via normalizePressBar or normalize
  [ ] useNotificationX.ts — state, API request field, setState after response, dispatch useEffect
  [ ] YourComponent.tsx created
  [ ] index.ts — component exported
  [ ] NotificationContainer.tsx — import + type routing branch
  [ ] _your-type.scss created
  [ ] theme.scss — @import added
  [ ] npm run frontend (no errors)
```

---

## Data Shape Reference

### What the PHP processing block produces

```json
{
  "exit_intent": {
    "42": {
      "post": { "nx_id": 42, "type": "exit_intent", "source": "exit_intent_custom", "position": "center", "enabled": true, "...": "all other settings" },
      "content": ""
    }
  }
}
```

### After `normalizePressBar()` in `utils.ts`

```ts
// exitIntentNotices[i]
{
  post:    { nx_id: 42, type: 'exit_intent', position: 'center', ... },
  content: ""
}
```

### After `dispatchNotification()` — what arrives in your component

```ts
// nxExitIntent (= notice in NotificationContainer)
{
  id:     "uuid-v4",          // for REMOVE_NOTIFICATION
  config: { nx_id: 42, type: 'exit_intent', position: 'center', ... },
  data:   ""                  // exitItem.content
}
```

---

## Choosing `normalize` vs `normalizePressBar`

| Function | Use when | Shape of PHP entries |
|----------|----------|----------------------|
| `normalize` | Type has multiple data **entries** (sales, reviews, comments) | `{ post, entries: [...] }` |
| `normalizePressBar` | Type is config-only or has a single content blob | `{ post, content: "" }` |

Exit Intent, Popup, GDPR, and PressBar all use `normalizePressBar`.
Standard notifications (WooCommerce sales, reviews, etc.) use `normalize`.
