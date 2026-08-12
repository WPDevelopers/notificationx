<?php
/**
 * Ability: list the available NotificationX data sources (extensions).
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Core\Modules;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the data sources/integrations and whether their module is active.
 */
class ListSources extends AbilityBase {

    protected $id          = 'notificationx/list-sources';
    protected $label       = 'List data sources';
    protected $description = 'List the data sources/integrations (e.g. WooCommerce, Comments, Contact Form) that can power a notification, which notification type each belongs to, whether it is Pro-only, and whether its module is currently enabled.';

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
                'count'   => array( 'type' => 'integer' ),
                'sources' => array( 'type' => 'array' ),
            ),
        );
    }

    public function execute( $input ) {
        $extensions = ExtensionFactory::get_instance()->get_all();
        $modules    = Modules::get_instance();
        $result     = array();

        if ( is_array( $extensions ) ) {
            foreach ( $extensions as $key => $ext ) {
                if ( ! is_object( $ext ) ) {
                    continue;
                }
                $module = isset( $ext->module ) ? $ext->module : '';
                $result[] = array(
                    'id'             => isset( $ext->id ) ? $ext->id : (string) $key,
                    'title'          => isset( $ext->title ) ? $ext->title : '',
                    'type'           => isset( $ext->types ) ? $ext->types : '',
                    'is_pro'         => ! empty( $ext->is_pro ),
                    'module'         => $module,
                    'module_enabled' => $module ? (bool) $modules->is_enabled( $module ) : true,
                );
            }
        }

        return array(
            'count'   => count( $result ),
            'sources' => $result,
        );
    }
}
