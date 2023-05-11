<?php
/**
 * GetInstance File
 *
 * @package NotificationX
 */

namespace NotificationX;

/**
 * Base trait make the instances of called class.
 */
trait GetInstance {
    /**
     * Instance of Called Class.
     *
     * @var GetInstance
     */
    protected static $instance = null;

    /**
     * Get the instance of called class.
     *
     */
    public static function get_instance($args = null){
        $class = get_called_class();
        if ( is_null( static::$instance ) || ! static::$instance instanceof self || (strpos($class, "NotificationXPro\\") === 0 && ! static::$instance instanceof $class) ) {
            $class = __CLASS__;
            if(strpos($class, "NotificationX\\") === 0){
                $pro_class = str_replace("NotificationX\\", "NotificationXPro\\", $class);
                if(class_exists($pro_class) && is_subclass_of($pro_class, $class)){
                    $class = $pro_class;
                }
            }

            if(!empty($args)){
                static::$instance = new $class($args);
            }
            else{
                static::$instance = new $class;
            }
        }
        return static::$instance;
    }

    // public function __call($name, $arguments){
    //     $class = __CLASS__ . 'Pro';

    //     if(strpos($class, "NotificationX\\") === 0){
    //         $pro_class = str_replace("NotificationX\\", "NotificationXPro\\", $class);
    //         if(class_exists($pro_class)){
    //             $obj = $pro_class::get_instance();
    //             if($obj && method_exists($obj, $name)){
    //                 $obj->$name($arguments);
    //             }
    //         }
    //     }
    // }

}
