<?php
/**
 * Ability: report NotificationX status — versions, edition, key toggles and
 * notification counts — so a client can orient itself in one call.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\NotificationX;
use NotificationX\Admin\Settings;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A one-call overview: Free/Pro versions, whether Pro is active, whether the MCP
 * connector / REST API / analytics are on, and how many notifications exist vs
 * how many are active. Call this first to tell a Free site from a Pro one and to
 * know which features are available before acting.
 */
class GetStatus extends AbilityBase {

    protected $id          = 'notificationx/get-status';
    protected $label       = 'Get status';
    protected $description = 'Get a one-call overview of this NotificationX install: Free and Pro versions, whether Pro is active, whether the MCP connector, REST API and analytics are enabled, and the total vs active notification counts. Use it to tell a Free site from a Pro one and to know what is available before creating or editing.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => (object) array(),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'version'         => array( 'type' => 'string' ),
                'pro_version'     => array( 'type' => 'string' ),
                'is_pro'          => array( 'type' => 'boolean' ),
                'mcp_enabled'     => array( 'type' => 'boolean' ),
                'rest_api_enabled' => array( 'type' => 'boolean' ),
                'analytics_enabled' => array( 'type' => 'boolean' ),
                'notifications'   => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        $settings  = Settings::get_instance();
        $post_type = PostType::get_instance();

        $all    = $post_type->get_posts( array(), 'nx_id' );
        $active = $post_type->get_posts( array( 'enabled' => true ), 'nx_id' );

        return array(
            'version'           => defined( 'NOTIFICATIONX_VERSION' ) ? NOTIFICATIONX_VERSION : '',
            'pro_version'       => defined( 'NOTIFICATIONX_PRO_VERSION' ) ? NOTIFICATIONX_PRO_VERSION : '',
            'is_pro'            => (bool) NotificationX::is_pro(),
            'mcp_enabled'       => (bool) $settings->get( 'settings.enable_mcp' ),
            'rest_api_enabled'  => (bool) $settings->get( 'settings.enable_rest_api', false ),
            'analytics_enabled' => (bool) $settings->get( 'settings.enable_analytics', true ),
            'notifications'     => array(
                'total'  => is_array( $all ) ? count( $all ) : 0,
                'active' => is_array( $active ) ? count( $active ) : 0,
            ),
        );
    }
}
