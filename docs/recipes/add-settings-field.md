# Recipe: Add a Settings Field

Where to add a new configurable field, depending on its scope.

## Pick the right layer

| Field scope | Layer | Where |
|---|---|---|
| Global plugin setting (visible in **NotificationX → Settings**) | Settings tab | [includes/Admin/Settings.php](../../includes/Admin/Settings.php) |
| Module toggle (on/off for an Extension's whole module) | Module config | Set `$module` + `$module_title` on the Extension; toggle appears automatically in Modules tab |
| Field on the notification builder, **shared across all sources of a Type** | Type schema | The Type class in [includes/Types/](../../includes/Types/) |
| Field on the notification builder, **specific to one Extension** | Extension schema | The Extension class via `GlobalFields` registration |
| Field shared by **multiple** Extensions (e.g. country selector) | `GlobalFields` | [includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php) |

## Field schema shape

QuickBuilder consumes a schema like this:

```php
array(
    'name'    => 'show_country',
    'type'    => 'toggle',          // toggle, text, select, multi-select, image, color, group, …
    'label'   => __( 'Show country', 'notificationx' ),
    'default' => true,
    'rules'   => array(             // conditional visibility (optional)
        'depends_on' => 'show_location',
        'condition'  => 'equals',
        'value'      => true,
    ),
    'priority' => 50,               // ordering
)
```

Common `type` values: `toggle`, `text`, `number`, `select`, `multi-select`, `radio`, `image`, `color`, `range`, `group`, `repeater`, `code`, `wysiwyg`. The QuickBuilder source is pinned to `github:WPDevelopers/quickbuilder#notificationx` — check its README for the authoritative type list.

## Adding a field to a Type

Edit the Type class, append to its templates/fields array. Example for `Conversions`:

```php
// includes/Types/Conversions.php
$this->templates = array_merge( $this->templates, array(
    array(
        'name'    => 'show_anonymous',
        'type'    => 'toggle',
        'label'   => __( 'Anonymize buyer name', 'notificationx' ),
        'default' => false,
        'priority' => 80,
    ),
) );
```

Every Extension that uses this Type gets the field automatically.

## Adding a field to one Extension

Inside `init_extension()`:

```php
public function init_extension() {
    $this->title        = __( 'Acme', 'notificationx' );
    $this->module_title = __( 'Acme', 'notificationx' );
    $this->templates    = \NotificationX\Types\Conversions::get_instance()->templates;

    add_filter( 'nx_builder_configs', array( $this, 'add_acme_fields' ), 20 );
}

public function add_acme_fields( $configs ) {
    $configs[ $this->id ]['fields'][] = array(
        'name'    => 'acme_api_key',
        'type'    => 'text',
        'label'   => __( 'Acme API key', 'notificationx' ),
        'rules'   => array(
            'depends_on' => 'source',
            'condition'  => 'equals',
            'value'      => $this->id,
        ),
    );
    return $configs;
}
```

The exact filter name and config shape may vary — verify by reading [includes/Core/PostType.php:224](../../includes/Core/PostType.php) and how existing Extensions use it (e.g. [includes/Extensions/MailChimp/](../../includes/Extensions/MailChimp/)).

## Adding a global setting

[includes/Admin/Settings.php](../../includes/Admin/Settings.php) holds the settings tab schema. Add to the relevant tab's `fields` array. Example for a new "AI" tab:

```php
'ai' => array(
    'title'  => __( 'AI Integrations', 'notificationx' ),
    'fields' => array(
        array(
            'name'    => 'ai_provider',
            'type'    => 'select',
            'label'   => __( 'AI Provider', 'notificationx' ),
            'options' => array(
                'none'      => __( 'None', 'notificationx' ),
                'anthropic' => __( 'Anthropic', 'notificationx' ),
                'openai'    => __( 'OpenAI', 'notificationx' ),
            ),
            'default' => 'none',
        ),
        array(
            'name'    => 'ai_api_key',
            'type'    => 'text',
            'label'   => __( 'API Key', 'notificationx' ),
            'rules'   => array(
                'depends_on' => 'ai_provider',
                'condition'  => 'not_equals',
                'value'      => 'none',
            ),
        ),
    ),
),
```

To add a tab dynamically from an Extension or from Pro, use the `nx_settings_tab` filter.

## Reading the value at runtime

```php
$value = \NotificationX\Admin\Settings::get_instance()->get( 'settings.ai.ai_api_key' );
```

For notification-level fields (stored in `nx_posts.data`), they're available on the notification post array:
```php
$post['show_anonymous'];  // matches the field 'name'
```

## Sanitization

QuickBuilder applies type-based sanitization on save. For sensitive fields (API keys, URLs, raw HTML), don't rely on it alone — sanitize/escape on read or in the saved hook:

```php
public function save_post( $post_id, $post, $update ) {
    if ( isset( $post['acme_api_key'] ) ) {
        $post['acme_api_key'] = sanitize_text_field( $post['acme_api_key'] );
    }
    return $post;
}
```

## i18n

Every `label`, `description`, and `placeholder` string must be wrapped in `__( ..., 'notificationx' )` so it lands in `notificationx.pot`. Run `npm run pot` after adding strings.

## Frontend reflection

If your field affects what the popup displays, the frontend Type renderer in `nxdev/notificationx/frontend/` must read the field by the same `name`. Adding a field without updating the frontend = silent no-op on the popup. See [../architecture.md § dual frontend runtimes](../architecture.md#dual-frontend-runtimes-the-silent-desync-trap).

## Anti-patterns

- ❌ Storing settings outside `Settings::get_instance()` — fragments storage, breaks WPML translation export, breaks the auto-commit guarantee.
- ❌ Hard-coding option keys (`get_option('nx_my_thing')`). Always go through `Settings`.
- ❌ Adding identical fields to multiple Extensions instead of putting them on the parent Type or in `GlobalFields`.
- ❌ Forgetting `priority` — fields ordering becomes unpredictable when Pro adds more.
