<?php
/**
 * Base class for every NotificationX MCP "ability".
 *
 * An ability is a single, self-describing capability (list notifications, read
 * analytics, toggle a campaign, …) exposed to AI assistants through the MCP
 * server. The shape here intentionally mirrors the WordPress Abilities API
 * (`wp_register_ability()`): an id, human labels, JSON-Schema input/output, a
 * permission callback and an execute callback. Keeping the same contract means
 * these abilities can be handed to WordPress core's Abilities API (or the Pro
 * add-on) later with no rewrite — the registrar just registers them elsewhere.
 *
 * @package NotificationX\Abilities
 */

namespace NotificationX\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract ability. Concrete abilities live in Abilities/Read and Abilities/Manage.
 */
abstract class AbilityBase {

    /**
     * Fully-qualified ability id, e.g. "notificationx/list-notifications".
     * The part before the slash is the category; the part after becomes the
     * MCP tool name (which cannot contain a slash).
     *
     * @var string
     */
    protected $id = '';

    /**
     * Short human label shown in tooling.
     *
     * @var string
     */
    protected $label = '';

    /**
     * One-line description surfaced to the AI assistant so it knows when to
     * reach for this tool. Keep it specific and action oriented.
     *
     * @var string
     */
    protected $description = '';

    /**
     * WordPress capability the caller must have. Every ability is admin-only
     * by default; the MCP server additionally refuses any grant whose issuing
     * user is not an administrator.
     *
     * @var string
     */
    protected $capability = 'manage_options';

    /**
     * Whether the ability mutates state. Read-only tokens (and the read-only
     * OAuth scope) may call read abilities only. Concrete write abilities set
     * this to true.
     *
     * @var bool
     */
    protected $is_write = false;

    /**
     * Whether repeating the call with the same input has the same effect
     * (used only as an MCP annotation hint for the client).
     *
     * @var bool
     */
    protected $is_idempotent = true;

    /**
     * JSON Schema for the ability input (an object describing accepted args).
     *
     * @return array
     */
    abstract public function input_schema();

    /**
     * JSON Schema for the ability output.
     *
     * @return array
     */
    abstract public function output_schema();

    /**
     * Do the work. Runs only after the permission check has passed and the
     * input has been validated against input_schema().
     *
     * @param array $input Sanitized, schema-validated input.
     * @return array|\WP_Error
     */
    abstract public function execute( $input );

    /**
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function is_write() {
        return (bool) $this->is_write;
    }

    /**
     * The MCP tool name — the ability id with its category prefix stripped
     * ("notificationx/list-notifications" => "list-notifications").
     *
     * @return string
     */
    public function tool_name() {
        $pos = strpos( $this->id, '/' );
        return false === $pos ? $this->id : substr( $this->id, $pos + 1 );
    }

    /**
     * MCP tool annotations — behavioural hints for the client.
     *
     * @return array
     */
    public function annotations() {
        return array(
            'title'           => $this->label,
            'readOnlyHint'    => ! $this->is_write,
            'destructiveHint' => $this->is_write && ! $this->is_idempotent,
            'idempotentHint'  => $this->is_idempotent,
            'openWorldHint'   => false,
        );
    }

    /**
     * Whether the current user may run this ability.
     *
     * @return bool
     */
    public function permission_callback() {
        return current_user_can( $this->capability );
    }

    /**
     * Full run pipeline used by the MCP server: permission check → input
     * validation → execute, wrapped in action hooks so Pro/3rd-party can
     * observe. Returns the ability result or a WP_Error.
     *
     * @param array $input Raw input arguments.
     * @return array|\WP_Error
     */
    public function run( $input = array() ) {
        if ( ! $this->permission_callback() ) {
            return new \WP_Error(
                'nx_mcp_forbidden',
                __( 'You are not allowed to run this ability.', 'notificationx' ),
                array( 'status' => 403 )
            );
        }

        $input = is_array( $input ) ? $input : array();

        $validated = $this->validate_input( $input );
        if ( is_wp_error( $validated ) ) {
            return $validated;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        do_action( 'nx_before_ability_execute', $this->id, $validated );

        $result = $this->execute( $validated );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        do_action( 'nx_after_ability_execute', $this->id, $validated, $result );

        return $result;
    }

    /**
     * Minimal JSON-Schema validation: enforce required keys and primitive
     * types declared in input_schema(). Unknown keys are dropped so an
     * assistant cannot smuggle extra fields into the execute() payload.
     *
     * @param array $input Raw input.
     * @return array|\WP_Error Cleaned input or error.
     */
    protected function validate_input( $input ) {
        $schema = $this->input_schema();
        if ( empty( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
            return array();
        }

        $properties = $schema['properties'];
        $required   = isset( $schema['required'] ) && is_array( $schema['required'] ) ? $schema['required'] : array();
        $clean      = array();

        foreach ( $required as $key ) {
            if ( ! array_key_exists( $key, $input ) || '' === $input[ $key ] || null === $input[ $key ] ) {
                return new \WP_Error(
                    'nx_mcp_missing_param',
                    /* translators: %s: parameter name. */
                    sprintf( __( 'Missing required parameter: %s', 'notificationx' ), $key ),
                    array( 'status' => 400 )
                );
            }
        }

        foreach ( $properties as $key => $definition ) {
            if ( ! array_key_exists( $key, $input ) ) {
                continue;
            }
            $type  = isset( $definition['type'] ) ? $definition['type'] : 'string';
            $value = $input[ $key ];

            switch ( $type ) {
                case 'integer':
                    $value = is_numeric( $value ) ? (int) $value : 0;
                    break;
                case 'number':
                    $value = is_numeric( $value ) ? (float) $value : 0;
                    break;
                case 'boolean':
                    $value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
                    break;
                case 'array':
                    $value = is_array( $value ) ? $value : array();
                    break;
                case 'object':
                    $value = is_array( $value ) ? $value : array();
                    break;
                default:
                    $value = is_scalar( $value ) ? (string) $value : '';
                    break;
            }

            // Enforce enum allow-lists when declared.
            if ( ! empty( $definition['enum'] ) && is_array( $definition['enum'] ) && ! in_array( $value, $definition['enum'], true ) ) {
                return new \WP_Error(
                    'nx_mcp_invalid_param',
                    /* translators: %s: parameter name. */
                    sprintf( __( 'Invalid value for parameter: %s', 'notificationx' ), $key ),
                    array( 'status' => 400 )
                );
            }

            $clean[ $key ] = $value;
        }

        return $clean;
    }

    /**
     * The MCP tool descriptor for tools/list.
     *
     * @return array
     */
    public function to_tool() {
        return array(
            'name'         => $this->tool_name(),
            'description'  => $this->description,
            'inputSchema'  => $this->normalize_schema( $this->input_schema() ),
            'annotations'  => $this->annotations(),
        );
    }

    /**
     * MCP clients expect an object schema; an empty "properties" must be an
     * object ({}) not an array ([]) once JSON encoded. Normalize recursively.
     *
     * @param array $schema Schema fragment.
     * @return array|object
     */
    protected function normalize_schema( $schema ) {
        if ( ! is_array( $schema ) ) {
            return $schema;
        }
        if ( array() === $schema ) {
            return (object) array();
        }
        foreach ( $schema as $key => $value ) {
            if ( is_array( $value ) ) {
                $schema[ $key ] = $this->normalize_schema( $value );
            }
        }
        return $schema;
    }
}
