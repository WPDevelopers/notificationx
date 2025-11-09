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
        /* translators: %s is the undefined property name. */
        trigger_error( sprintf( esc_html__( 'Undefined property: %s', 'notificationx' ), esc_html( $name ) ) );
    }

    #[\ReturnTypeWillChange]
    public function offsetGet ($name){
        if(parent::offsetExists($name)){
            return parent::offsetGet($name);
        }
    }

}
