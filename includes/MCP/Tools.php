<?php
/**
 * Bridge between NotificationX abilities and MCP tools.
 *
 * The tool surface *is* the ability registry — it cannot drift. This class
 * turns registered abilities into `tools/list` entries and routes `tools/call`
 * to the matching ability, enforcing read-only scope before any write runs.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;
use NotificationX\Abilities\Registrar;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static Tools get_instance( $args = null )
 */
class Tools {

    use GetInstance;

    /**
     * When true, only read abilities may be invoked (set from the credential's
     * scope by the server before dispatch).
     *
     * @var bool
     */
    protected $read_only = false;

    /**
     * Set the read-only override for the current request.
     *
     * @param bool $read_only Whether the credential is read-only.
     * @return void
     */
    public function set_read_only( $read_only ) {
        $this->read_only = (bool) $read_only;
    }

    /**
     * Build the tools/list payload.
     *
     * @return array
     */
    public function list_tools() {
        $tools = array();
        foreach ( Registrar::get_instance()->get_all() as $ability ) {
            $tools[] = $ability->to_tool();
        }
        return $tools;
    }

    /**
     * Invoke a tool by its MCP name.
     *
     * @param string $name Tool name (no category prefix).
     * @param array  $args Tool arguments.
     * @return array|\WP_Error Ability result or error.
     */
    public function invoke( $name, $args = array() ) {
        $ability = Registrar::get_instance()->get_by_tool_name( $name );

        if ( ! $ability ) {
            return new \WP_Error(
                'nx_mcp_unknown_tool',
                /* translators: %s: tool name. */
                sprintf( __( 'Unknown tool: %s', 'notificationx' ), $name ),
                array( 'status' => 404 )
            );
        }

        if ( $this->read_only && $ability->is_write() ) {
            return new \WP_Error(
                'nx_mcp_read_only',
                __( 'This connection is read-only and cannot run write tools.', 'notificationx' ),
                array( 'status' => 403 )
            );
        }

        return $ability->run( is_array( $args ) ? $args : array() );
    }
}
