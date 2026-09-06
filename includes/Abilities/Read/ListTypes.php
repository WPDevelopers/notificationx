<?php
/**
 * Ability: list the available NotificationX notification types.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Types\TypeFactory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the notification types registered on this site (Sales, Reviews, …).
 */
class ListTypes extends AbilityBase {

    protected $id          = 'notificationx/list-types';
    protected $label       = 'List notification types';
    protected $description = 'List the notification types available on this site (e.g. Sales Notification, Comments, Reviews, Notification Bar), including whether each is a Pro-only type.';

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
                'count' => array( 'type' => 'integer' ),
                'types' => array( 'type' => 'array' ),
            ),
        );
    }

    public function execute( $input ) {
        $types  = TypeFactory::get_instance()->get_all();
        $result = array();

        if ( is_array( $types ) ) {
            foreach ( $types as $key => $type ) {
                if ( is_object( $type ) ) {
                    $result[] = array(
                        'id'     => isset( $type->id ) ? $type->id : (string) $key,
                        'title'  => isset( $type->dashboard_title ) && $type->dashboard_title ? $type->dashboard_title : ( isset( $type->title ) ? $type->title : '' ),
                        'is_pro' => ! empty( $type->is_pro ),
                    );
                }
            }
        }

        return array(
            'count' => count( $result ),
            'types' => $result,
        );
    }
}
