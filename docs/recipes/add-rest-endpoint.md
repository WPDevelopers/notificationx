# Recipe: Add a REST Endpoint

Add a new route under `/wp-json/notificationx/v1/`. Read [../rest-api.md](../rest-api.md) first for the existing surface.

## Decide: new controller or extend existing?

| Situation | Choice |
|---|---|
| Endpoint is closely related to existing ones (e.g. another notification action) | Add to existing controller (e.g. [Posts.php](../../includes/Core/Rest/Posts.php)) |
| Endpoint is a new domain (e.g. AI workflow triggers) | New controller class in `includes/Core/Rest/` |

## Steps for a new controller

### 1. Create the class

```
includes/Core/Rest/Workflows.php
```

```php
<?php
namespace NotificationX\Core\Rest;

use NotificationX\GetInstance;

class Workflows {
    use GetInstance;

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            'notificationx/v1',
            '/workflows/(?P<id>[\d]+)/trigger',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,  // POST
                'callback'            => array( $this, 'trigger' ),
                'permission_callback' => array( $this, 'permission' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => function( $v ) { return is_numeric( $v ) && $v > 0; },
                        'sanitize_callback' => 'absint',
                    ),
                    'payload' => array(
                        'required'          => false,
                        'sanitize_callback' => array( $this, 'sanitize_payload' ),
                    ),
                ),
            )
        );
    }

    public function permission( \WP_REST_Request $request ) {
        return current_user_can( 'manage_options' );
    }

    public function trigger( \WP_REST_Request $request ) {
        $id      = $request->get_param( 'id' );
        $payload = $request->get_param( 'payload' );

        // Do work…

        return new \WP_REST_Response( array(
            'success' => true,
            'id'      => $id,
        ), 200 );
    }

    public function sanitize_payload( $payload ) {
        return is_array( $payload ) ? array_map( 'sanitize_text_field', $payload ) : array();
    }
}
```

### 2. Mount it in REST.php

[includes/Core/REST.php](../../includes/Core/REST.php), inside the constructor (around lines 50–55):

```php
Rest\Workflows::get_instance();
```

### 3. Permission callbacks (don't skip)

WP REST will refuse to register a route without `permission_callback`. Use:
- `current_user_can( 'manage_options' )` for admin-only.
- `'__return_true'` **only** for genuinely public endpoints, and validate the payload yourself (see [Rest/Analytics.php](../../includes/Core/Rest/Analytics.php) for the public-endpoint pattern).

### 4. Nonce verification (admin endpoints)

WP automatically verifies the `X-WP-Nonce` header when `permission_callback` is set and the request comes from a logged-in user. You don't need to call `wp_verify_nonce` manually unless you're doing custom auth.

### 5. Returning responses

- Success: `new WP_REST_Response( $data, 200 )` or just return `$data`.
- Error: `new WP_Error( 'nx_workflow_not_found', __( 'Workflow not found', 'notificationx' ), array( 'status' => 404 ) )`.

Error codes should be prefixed with `nx_` for grep-ability.

### 6. Schema (optional but recommended)

For typed clients:
```php
public function get_schema() {
    return array(
        '$schema'    => 'http://json-schema.org/draft-04/schema#',
        'title'      => 'workflow_trigger_result',
        'type'       => 'object',
        'properties' => array(
            'success' => array( 'type' => 'boolean' ),
            'id'      => array( 'type' => 'integer' ),
        ),
    );
}
```

Pass via `'schema' => array( $this, 'get_schema' )` in `register_rest_route`.

### 7. Test

```sh
# Bare curl (replace nonce):
curl -X POST http://localhost/wp-json/notificationx/v1/workflows/42/trigger \
  -H "X-WP-Nonce: <nonce>" \
  -H "Content-Type: application/json" \
  -d '{"payload": {"foo": "bar"}}'
```

Get a nonce in a logged-in browser console:
```js
wpApiSettings.nonce  // or wp.apiFetch.createNonceMiddleware
```

### 8. Document it

Add the route to [../rest-api.md](../rest-api.md) under a new section. Update the route count in this doc's intro if needed.

## Calling REST from the admin SPA

Use `@wordpress/api-fetch` (already a dependency):

```ts
import apiFetch from '@wordpress/api-fetch';

const result = await apiFetch({
    path: '/notificationx/v1/workflows/42/trigger',
    method: 'POST',
    data: { payload: { foo: 'bar' } },
});
```

`api-fetch` sets the nonce header automatically.

## Anti-patterns

- ❌ Skipping `permission_callback` — WP refuses to register without it. `'__return_true'` is allowed but treat that endpoint as if it were unauthenticated and validate hard.
- ❌ Doing auth via custom nonce checks when `permission_callback` already handles it.
- ❌ Returning raw `wp_send_json_*` — that bypasses WP REST's serializer and breaks tooling. Always return `WP_REST_Response` or `WP_Error`.
- ❌ Using `init` hook instead of `rest_api_init` to register routes. `init` fires too early; the REST server isn't ready.
- ❌ Registering routes in an Extension class. REST routes are cross-cutting; they belong in `includes/Core/Rest/`.
