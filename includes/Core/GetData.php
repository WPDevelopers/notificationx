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
        trigger_error('Undefined property: ' . $name);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet ($name){
        if(parent::offsetExists($name)){
            return parent::offsetGet($name);
        }
    }

}
