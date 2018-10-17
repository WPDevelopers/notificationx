<?php

class FomoPress_PressBar_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'press_bar';

    public function __construct() {
        parent::__construct();
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public static function display( $settings ){
        require plugin_dir_path( __FILE__ ) . 'press-bar-frontend.php';
    }
}