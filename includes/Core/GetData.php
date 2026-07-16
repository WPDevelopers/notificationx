<?php
/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Core;

use NotificationX\Admin\Settings;
use NotificationX\GetInstance;

/**
 * GetData Class
 */
class GetData extends \ArrayObject {

    public function __get($name) {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }
        // Mirrors PHP's own undefined-property notice for this ArrayAccess wrapper;
        // dropping it would make a mistyped property silently read as null.
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
        trigger_error( esc_html( 'Undefined property: ' . $name ) );
    }

    #[\ReturnTypeWillChange]
    public function offsetGet ($name){
        if(parent::offsetExists($name)){
            return parent::offsetGet($name);
        }
    }

}
